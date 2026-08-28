<?php

namespace App\Services\Catalog;

use App\Models\CatalogCategory;
use App\Models\CatalogCategoryRule;
use App\Models\CatalogPlatform;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CatalogClassifier
{
    /** @var Collection<int, CatalogCategoryRule>|null */
    protected static ?Collection $rulesCache = null;

    /** @var array<string, CatalogPlatform> */
    protected array $platformsBySlug = [];

    /** @var array<string, CatalogCategory> */
    protected array $categoriesByKey = [];

    public function classifyService(Service $service, ?string $providerCategory = null): Service
    {
        $payload = $this->classifyAttributes($service, $providerCategory);

        $service->platform = $payload['platform'];
        $service->catalog_category_id = $payload['catalog_category_id'];
        $service->quality_tier = $payload['quality_tier'];
        $service->is_hot = $payload['is_hot'];
        $service->is_cheap = $payload['is_cheap'];
        $service->start_class = $payload['start_class'];
        $service->refill_days = $payload['refill_days'];
        $service->refill_mode = $payload['refill_mode'];
        $service->country_code = $payload['country_code'];
        $service->audience_gender = $payload['audience_gender'];
        $service->reaction_type = $payload['reaction_type'];
        $service->refill = $payload['refill'];
        $service->dripfeed = $payload['dripfeed'];

        $meta = is_array($service->meta) ? $service->meta : [];
        $meta['facets'] = $payload['facets_meta'] ?? [];
        $service->meta = $meta;

        $service->saveQuietly();

        return $service;
    }

    /**
     * @return array{
     *   platform:string,
     *   catalog_category_id:?int,
     *   quality_tier:string,
     *   is_hot:bool,
     *   is_cheap:bool,
     *   start_class:?string,
     *   refill_days:?int,
     *   refill_mode:string,
     *   country_code:?string,
     *   audience_gender:?string,
     *   reaction_type:?string,
     *   refill:bool,
     *   dripfeed:bool,
     *   facets_meta:array<string, mixed>
     * }
     */
    public function classifyAttributes(Service $service, ?string $providerCategory = null): array
    {
        $providerCategory ??= $service->providerService?->category;
        $platformSlug = $service->platform ?: $this->detectPlatform(($providerCategory ?? '').' '.$service->name);

        $platform = $this->resolvePlatform($platformSlug);
        $platformSlug = $platform?->slug ?? $platformSlug;

        $match = $this->matchRule($platformSlug, (string) $providerCategory, (string) $service->name, (string) $service->description);
        $categorySlug = $match['category_slug'] ?? 'other';
        $ruleTier = $match['quality_tier'] ?? null;
        $qualityTier = $ruleTier ?? $this->inferQualityTier((string) $service->name, (string) $providerCategory, (string) $service->description);

        $speedHint = is_array($service->meta) ? (string) ($service->meta['speed'] ?? '') : '';
        $facets = $this->extractFacets(
            (string) $service->name,
            (string) $service->description,
            $speedHint,
            (bool) ($service->providerService?->refill ?? $service->refill),
            (bool) $service->dripfeed,
        );

        if ($facets['is_cheap'] && $qualityTier !== 'premium') {
            $qualityTier = 'economy';
        } elseif (! $ruleTier && $facets['quality_hint']) {
            $qualityTier = $facets['quality_hint'];
        }

        $category = $this->resolveCategory($platform, $categorySlug, $this->humanizeSlug($categorySlug));
        $audienceGender = $this->inferAudienceGender((string) $service->name, (string) $service->description);
        $reactionType = $this->inferReactionType(
            (string) $service->name,
            (string) $service->description,
            (string) $providerCategory,
        );

        if ($platformSlug === 'facebook') {
            [$categorySlug, $reactionType] = $this->resolveFacebookReactionCategory(
                $categorySlug,
                (string) $providerCategory,
                (string) $service->name,
                (string) $service->description,
                $reactionType,
            );
            $category = $this->resolveCategory($platform, $categorySlug, $this->humanizeSlug($categorySlug));
        }

        return [
            'platform' => $platformSlug,
            'catalog_category_id' => $category?->id,
            'quality_tier' => $qualityTier,
            'is_hot' => $facets['is_hot'],
            'is_cheap' => $facets['is_cheap'],
            'start_class' => $facets['start_class'],
            'refill_days' => $facets['refill_days'],
            'refill_mode' => $facets['refill_mode'],
            'country_code' => $facets['country_code'],
            'audience_gender' => $audienceGender,
            'reaction_type' => $reactionType,
            'refill' => $facets['refill'],
            'dripfeed' => $facets['dripfeed'],
            'facets_meta' => [
                'start_raw' => $facets['start_raw'],
                'refill_raw' => $facets['refill_raw'],
                'country_raw' => $facets['country_raw'],
                'audience_gender' => $audienceGender,
                'reaction_type' => $reactionType,
            ],
        ];
    }

    /**
     * @return array{
     *   is_hot:bool,
     *   is_cheap:bool,
     *   start_class:?string,
     *   start_raw:?string,
     *   refill_days:?int,
     *   refill_mode:string,
     *   refill_raw:?string,
     *   country_code:?string,
     *   country_raw:?string,
     *   refill:bool,
     *   dripfeed:bool,
     *   quality_hint:?string
     * }
     */
    public function extractFacets(
        string $name,
        string $description = '',
        string $speedHint = '',
        bool $existingRefill = false,
        bool $existingDripfeed = false,
    ): array {
        $raw = $name.' '.$description.' '.$speedHint;
        $hay = mb_strtolower($this->normalizeFancyText($raw));

        $isHot = str_contains($raw, '🔥')
            || str_contains($hay, 'top best')
            || str_contains($hay, 'best seller')
            || str_contains($hay, 'top service');

        $isCheap = str_contains($hay, 'cheap')
            || str_contains($hay, 'cheapest')
            || str_contains($hay, 'may face some issues')
            || str_contains($hay, 'low quality')
            || preg_match('/\blq\b/', $hay) === 1;

        $dripfeed = $existingDripfeed || str_contains($raw, '💧') || str_contains($hay, 'drip');

        $refillFromEmoji = str_contains($raw, '♻') || str_contains($raw, '♻️');
        $refillDays = null;
        $refillMode = 'none';
        $refillRaw = null;

        if (preg_match('/\bar\s*(\d{1,3})\b/iu', $hay, $m)) {
            $refillDays = (int) $m[1];
            $refillMode = 'auto';
            $refillRaw = 'AR'.$refillDays;
        } elseif (preg_match('/\bauto\s*refill[^0-9]{0,12}(\d{1,3})\s*d?\b/iu', $hay, $m)) {
            $refillDays = (int) $m[1];
            $refillMode = 'auto';
            $refillRaw = 'AR'.$refillDays;
        } elseif (preg_match('/\br\s*(\d{1,3})\b/iu', $hay, $m)) {
            $refillDays = (int) $m[1];
            $refillMode = 'manual';
            $refillRaw = 'R'.$refillDays;
        } elseif (preg_match('/refill[^0-9]{0,16}(\d{1,3})\s*d(?:ays?)?\b/iu', $hay, $m)) {
            $refillDays = (int) $m[1];
            $refillMode = 'manual';
            $refillRaw = 'R'.$refillDays;
        } elseif (preg_match('/(\d{1,3})\s*days?\s*(?:♻️|♻|refill)/iu', $hay, $m)) {
            $refillDays = (int) $m[1];
            $refillMode = 'manual';
            $refillRaw = 'R'.$refillDays;
        } elseif (str_contains($hay, 'lifetime') && (str_contains($hay, 'refill') || $refillFromEmoji)) {
            $refillMode = 'lifetime';
            $refillRaw = 'lifetime';
        } elseif ($refillFromEmoji || str_contains($hay, 'refill')) {
            if (! str_contains($hay, 'no refill') && ! str_contains($hay, 'not refill') && ! str_contains($hay, 'non refill')) {
                $refillMode = 'manual';
                $refillRaw = 'refill';
            }
        }

        $hasRefillSignal = $refillMode !== 'none' || $refillFromEmoji;
        $refill = $existingRefill;
        if (! $refill) {
            $refillMode = 'none';
            $refillDays = null;
        } elseif ($refillMode === 'none') {
            $refillMode = 'manual';
        }

        [$startClass, $startRaw] = $this->inferStartClass($raw, $hay, $speedHint);
        [$countryCode, $countryRaw] = $this->inferCountryCode($raw, $hay);

        $qualityHint = null;
        if (preg_match('/\bhq\b/', $hay) || str_contains($hay, 'high quality') || str_contains($hay, 'real')) {
            $qualityHint = 'premium';
        }
        if ($isCheap || preg_match('/\blq\b/', $hay) || str_contains($hay, 'bot')) {
            $qualityHint = 'economy';
        }

        return [
            'is_hot' => $isHot,
            'is_cheap' => $isCheap,
            'start_class' => $startClass,
            'start_raw' => $startRaw,
            'refill_days' => $refillDays,
            'refill_mode' => $refillMode,
            'refill_raw' => $refillRaw,
            'country_code' => $countryCode,
            'country_raw' => $countryRaw,
            'refill' => $refill,
            'dripfeed' => $dripfeed,
            'quality_hint' => $qualityHint,
        ];
    }

    /**
     * @return array{0:?string, 1:?string}
     */
    protected function inferStartClass(string $raw, string $hay, string $speedHint): array
    {
        $blob = mb_strtolower($this->normalizeFancyText($raw.' '.$speedHint));

        if (str_contains($blob, 'instant') || str_contains($blob, '0-0') || preg_match('/start(?:\s*time)?\s*[:=]?\s*instant/i', $blob)) {
            return ['instant', 'instant'];
        }

        if (preg_match('/(?:start(?:\s*time)?|starts?)[^0-9]{0,12}(\d{1,2})\s*[-–to]{1,3}\s*(\d{1,2})\s*h/i', $blob, $m)) {
            $maxH = max((int) $m[1], (int) $m[2]);
            $rawMatch = $m[0];

            return [$this->hoursToStartClass($maxH), $rawMatch];
        }

        if (preg_match('/(?:start(?:\s*time)?|starts?)[^0-9]{0,12}(\d{1,2})\s*h\b/i', $blob, $m)) {
            $hours = (int) $m[1];

            return [$this->hoursToStartClass($hours), $m[0]];
        }

        if (preg_match('/\b(\d{1,2})\s*h\b/', $blob, $m) && (str_contains($blob, 'start') || str_contains($blob, 'speed'))) {
            return [$this->hoursToStartClass((int) $m[1]), $m[0]];
        }

        if (str_contains($blob, 'fast') || str_contains($blob, '0-1') || str_contains($blob, '0 - 1')) {
            return ['fast', 'fast'];
        }

        if (str_contains($blob, 'slow') || str_contains($blob, '24h') || str_contains($blob, '48h') || str_contains($blob, '72h')) {
            return ['slow', 'slow'];
        }

        return ['normal', null];
    }

    /**
     * Detect male / female / mixed audience targeting from service naming.
     */
    protected function inferAudienceGender(string $name, string $description = ''): ?string
    {
        $hay = mb_strtolower($this->normalizeFancyText($name.' '.$description));

        $hasFemale = preg_match('/\b(women|woman|females?|female)\b/u', $hay) === 1;
        $hasMale = preg_match('/\b(men|males?|male)\b/u', $hay) === 1;
        $hasMixed = preg_match('/\b(mixed|both)\b/u', $hay) === 1;

        if ($hasMixed || ($hasMale && $hasFemale)) {
            return 'mixed';
        }

        if ($hasFemale) {
            return 'female';
        }

        if ($hasMale) {
            return 'male';
        }

        return null;
    }

    /**
     * Detect Facebook reaction subtype from service naming.
     */
    protected function inferReactionType(string $name, string $description = '', string $category = ''): ?string
    {
        $hay = mb_strtolower($this->normalizeFancyText($name.' '.$description.' '.$category));

        if (preg_match('/\b(love|❤|😍)\b/u', $hay) === 1) {
            return 'love';
        }
        if (preg_match('/\b(wow|😲)\b/u', $hay) === 1) {
            return 'wow';
        }
        if (preg_match('/\b(haha|😀|😂)\b/u', $hay) === 1) {
            return 'haha';
        }
        if (preg_match('/\b(sad|😢)\b/u', $hay) === 1) {
            return 'sad';
        }
        if (preg_match('/\b(angry|😡)\b/u', $hay) === 1) {
            return 'angry';
        }

        if (
            preg_match('/\b(likes?|thumb|👍)\b/u', $hay) === 1
            && ! str_contains($hay, 'page like')
        ) {
            return 'like';
        }

        return null;
    }

    /**
     * @return array{0:string, 1:?string}
     */
    protected function resolveFacebookReactionCategory(
        string $categorySlug,
        string $providerCategory,
        string $name,
        string $description,
        ?string $reactionType,
    ): array {
        $hay = mb_strtolower($this->normalizeFancyText($providerCategory.' '.$name.' '.$description));
        $isStory = str_contains($hay, 'story');

        $legacyReactionSlugs = [
            'reaction_love' => 'love',
            'reaction_wow' => 'wow',
            'reaction_haha' => 'haha',
            'reaction_sad' => 'sad',
            'reaction_angry' => 'angry',
        ];

        if (isset($legacyReactionSlugs[$categorySlug])) {
            $reactionType = $reactionType ?? $legacyReactionSlugs[$categorySlug];
            $categorySlug = $isStory ? 'stories' : 'likes';
        } elseif (in_array($categorySlug, ['likes', 'stories'], true) && $reactionType === null) {
            if ($categorySlug === 'likes' && preg_match('/\b(likes?|thumb|👍)\b/u', $hay) === 1 && ! str_contains($hay, 'page like')) {
                $reactionType = 'like';
            }
        }

        return [$categorySlug, $reactionType];
    }

    /**
     * @return array{0:?string, 1:?string}
     */
    protected function inferCountryCode(string $raw, string $hay): array
    {
        if (
            str_contains($hay, 'worldwide')
            || str_contains($hay, 'world wide')
            || preg_match('/\bglobal\b/', $hay)
            || str_contains($raw, '🌎')
            || str_contains($raw, '🌍')
            || str_contains($raw, '🌏')
        ) {
            return ['worldwide', 'worldwide'];
        }

        $flagMap = [
            '🇺🇸' => 'us', '🇧🇷' => 'br', '🇮🇳' => 'in', '🇮🇩' => 'id', '🇹🇷' => 'tr',
            '🇷🇺' => 'ru', '🇫🇷' => 'fr', '🇩🇪' => 'de', '🇪🇬' => 'eg', '🇩🇿' => 'dz',
            '🇬🇧' => 'gb', '🇦🇷' => 'ar', '🇲🇽' => 'mx', '🇮🇹' => 'it', '🇪🇸' => 'es',
            '🇨🇦' => 'ca', '🇦🇺' => 'au', '🇯🇵' => 'jp', '🇰🇷' => 'kr', '🇨🇳' => 'cn',
            '🇸🇦' => 'sa', '🇦🇪' => 'ae', '🇲🇦' => 'ma', '🇹🇳' => 'tn', '🇳🇬' => 'ng',
            '🇵🇰' => 'pk', '🇧🇩' => 'bd', '🇵🇭' => 'ph', '🇻🇳' => 'vn', '🇹🇭' => 'th',
            '🇵🇱' => 'pl', '🇺🇦' => 'ua', '🇳🇱' => 'nl', '🇸🇪' => 'se', '🇳🇴' => 'no',
            '🇵🇹' => 'pt', '🇬🇷' => 'gr', '🇷🇴' => 'ro', '🇿🇦' => 'za', '🇨🇴' => 'co',
            '🇨🇱' => 'cl', '🇵🇪' => 'pe', '🇲🇾' => 'my', '🇸🇬' => 'sg', '🇮🇱' => 'il',
            '🇮🇶' => 'iq', '🇮🇷' => 'ir', '🇰🇼' => 'kw', '🇶🇦' => 'qa', '🇧🇭' => 'bh',
            '🇯🇴' => 'jo', '🇱🇧' => 'lb', '🇸🇾' => 'sy', '🇾🇪' => 'ye', '🇴🇲' => 'om',
            '🇰🇿' => 'kz', '🇺🇿' => 'uz', '🇦🇿' => 'az', '🇬🇪' => 'ge', '🇦🇲' => 'am',
        ];

        foreach ($flagMap as $emoji => $code) {
            if (str_contains($raw, $emoji)) {
                return [$code, $emoji];
            }
        }

        if (preg_match('/location\s*[:=]\s*([a-z][a-z\s\/\-]{1,40})/iu', $hay, $m)) {
            $resolved = $this->resolveCountryLabel(trim($m[1]));
            if ($resolved) {
                return [$resolved, trim($m[1])];
            }
        }

        if (preg_match('/\[([a-z]{2,3}(?:\s*\/\s*[a-z]{2,3})?)[^\]]*\]/iu', $hay, $m)) {
            $resolved = $this->resolveCountryLabel(trim($m[1]));
            if ($resolved) {
                return [$resolved, trim($m[1])];
            }
        }

        // Longer / more specific labels first.
        $labels = [
            'united states' => 'us', 'united kingdom' => 'gb', 'saudi arabia' => 'sa',
            'united arab emirates' => 'ae', 'south korea' => 'kr', 'south africa' => 'za',
            'czech republic' => 'cz', 'new zealand' => 'nz', 'hong kong' => 'hk',
            'usa' => 'us', 'u.s.a' => 'us', 'u.s.' => 'us', 'america' => 'us',
            'uk' => 'gb', 'england' => 'gb', 'britain' => 'gb',
            'brazil' => 'br', 'indonesia' => 'id', 'india' => 'in', 'turkey' => 'tr', 'türkiye' => 'tr',
            'russia' => 'ru', 'france' => 'fr', 'germany' => 'de', 'egypt' => 'eg', 'algeria' => 'dz',
            'argentina' => 'ar', 'mexico' => 'mx', 'italy' => 'it', 'spain' => 'es', 'canada' => 'ca',
            'australia' => 'au', 'japan' => 'jp', 'china' => 'cn', 'korea' => 'kr',
            'morocco' => 'ma', 'tunisia' => 'tn', 'nigeria' => 'ng', 'pakistan' => 'pk',
            'bangladesh' => 'bd', 'philippines' => 'ph', 'vietnam' => 'vn', 'thailand' => 'th',
            'poland' => 'pl', 'ukraine' => 'ua', 'netherlands' => 'nl', 'sweden' => 'se',
            'norway' => 'no', 'portugal' => 'pt', 'greece' => 'gr', 'romania' => 'ro',
            'colombia' => 'co', 'chile' => 'cl', 'peru' => 'pe', 'malaysia' => 'my',
            'singapore' => 'sg', 'israel' => 'il', 'iraq' => 'iq', 'iran' => 'ir',
            'kuwait' => 'kw', 'qatar' => 'qa', 'bahrain' => 'bh', 'jordan' => 'jo',
            'lebanon' => 'lb', 'syria' => 'sy', 'yemen' => 'ye', 'oman' => 'om',
            'kazakhstan' => 'kz', 'uzbekistan' => 'uz', 'azerbaijan' => 'az',
            'georgia' => 'ge', 'armenia' => 'am', 'arab' => 'sa',
        ];

        foreach ($labels as $label => $code) {
            if (preg_match('/\b'.preg_quote($label, '/').'\b/u', $hay)) {
                return [$code, $label];
            }
        }

        return [null, null];
    }

    protected function resolveCountryLabel(string $label): ?string
    {
        $value = mb_strtolower(trim(preg_replace('/\s+/', ' ', $label) ?? ''));
        $value = explode('/', $value)[0] ?? $value;
        $value = trim($value);

        $aliases = [
            'usa' => 'us', 'us' => 'us', 'u.s.a' => 'us', 'america' => 'us', 'united states' => 'us',
            'uk' => 'gb', 'gb' => 'gb', 'england' => 'gb', 'britain' => 'gb', 'united kingdom' => 'gb',
            'brazil' => 'br', 'br' => 'br', 'indonesia' => 'id', 'id' => 'id', 'india' => 'in', 'in' => 'in',
            'turkey' => 'tr', 'tr' => 'tr', 'russia' => 'ru', 'ru' => 'ru', 'france' => 'fr', 'fr' => 'fr',
            'germany' => 'de', 'de' => 'de', 'egypt' => 'eg', 'eg' => 'eg', 'algeria' => 'dz', 'dz' => 'dz',
            'argentina' => 'ar', 'ar' => 'ar', 'mexico' => 'mx', 'mx' => 'mx', 'italy' => 'it', 'it' => 'it',
            'spain' => 'es', 'es' => 'es', 'canada' => 'ca', 'ca' => 'ca', 'australia' => 'au', 'au' => 'au',
            'japan' => 'jp', 'jp' => 'jp', 'korea' => 'kr', 'kr' => 'kr', 'china' => 'cn', 'cn' => 'cn',
            'saudi' => 'sa', 'saudi arabia' => 'sa', 'sa' => 'sa', 'uae' => 'ae', 'ae' => 'ae',
            'morocco' => 'ma', 'ma' => 'ma', 'tunisia' => 'tn', 'tn' => 'tn', 'nigeria' => 'ng', 'ng' => 'ng',
            'pakistan' => 'pk', 'pk' => 'pk', 'bangladesh' => 'bd', 'bd' => 'bd', 'philippines' => 'ph', 'ph' => 'ph',
            'vietnam' => 'vn', 'vn' => 'vn', 'thailand' => 'th', 'th' => 'th', 'poland' => 'pl', 'pl' => 'pl',
            'ukraine' => 'ua', 'ua' => 'ua', 'netherlands' => 'nl', 'nl' => 'nl', 'sweden' => 'se', 'se' => 'se',
            'norway' => 'no', 'no' => 'no', 'portugal' => 'pt', 'pt' => 'pt', 'greece' => 'gr', 'gr' => 'gr',
            'romania' => 'ro', 'ro' => 'ro', 'colombia' => 'co', 'co' => 'co', 'chile' => 'cl', 'cl' => 'cl',
            'peru' => 'pe', 'pe' => 'pe', 'malaysia' => 'my', 'my' => 'my', 'singapore' => 'sg', 'sg' => 'sg',
            'israel' => 'il', 'il' => 'il', 'iraq' => 'iq', 'iq' => 'iq', 'iran' => 'ir', 'ir' => 'ir',
            'kuwait' => 'kw', 'kw' => 'kw', 'qatar' => 'qa', 'qa' => 'qa', 'bahrain' => 'bh', 'bh' => 'bh',
            'jordan' => 'jo', 'jo' => 'jo', 'lebanon' => 'lb', 'lb' => 'lb', 'syria' => 'sy', 'sy' => 'sy',
            'yemen' => 'ye', 'ye' => 'ye', 'oman' => 'om', 'om' => 'om', 'worldwide' => 'worldwide',
            'global' => 'worldwide', 'arab' => 'sa',
        ];

        return $aliases[$value] ?? null;
    }

    protected function hoursToStartClass(int $hours): string
    {
        if ($hours <= 0) {
            return 'instant';
        }
        if ($hours <= 2) {
            return 'fast';
        }
        if ($hours <= 12) {
            return 'normal';
        }

        return 'slow';
    }

    protected function normalizeFancyText(string $value): string
    {
        // Convert common mathematical bold/italic latin letters to ASCII where possible.
        $map = [];
        foreach (range(0, 25) as $i) {
            $map[mb_chr(0x1D400 + $i)] = chr(65 + $i); // bold A-Z
            $map[mb_chr(0x1D41A + $i)] = chr(97 + $i); // bold a-z
            $map[mb_chr(0x1D5D4 + $i)] = chr(65 + $i); // sans bold A-Z
            $map[mb_chr(0x1D5EE + $i)] = chr(97 + $i); // sans bold a-z
        }

        return strtr($value, $map);
    }

    /**
     * @return array{category_slug:?string, quality_tier:?string}|null
     */
    public function matchRule(string $platformSlug, string $category, string $name, string $description = ''): ?array
    {
        $hayCategory = mb_strtolower($category);
        $hayName = mb_strtolower($name.' '.$description);

        foreach ($this->rules() as $rule) {
            if ($rule->platform_slug !== '*' && $rule->platform_slug !== $platformSlug) {
                continue;
            }

            $pattern = $rule->pattern;
            $matched = match ($rule->match_type) {
                'category_equals' => $hayCategory === mb_strtolower($pattern),
                'category_contains' => str_contains($hayCategory, mb_strtolower($pattern)),
                'name_contains' => str_contains($hayName, mb_strtolower($pattern)),
                'name_regex' => (bool) @preg_match($pattern, $name.' '.$description),
                default => false,
            };

            if ($matched) {
                return [
                    'category_slug' => $rule->category_slug,
                    'quality_tier' => $rule->quality_tier,
                ];
            }
        }

        return [
            'category_slug' => $this->fallbackCategorySlug($hayCategory, $hayName),
            'quality_tier' => null,
        ];
    }

    public function inferQualityTier(string $name, string $category = '', string $description = ''): string
    {
        $hay = mb_strtolower($this->normalizeFancyText($name.' '.$category.' '.$description));

        if (str_contains($hay, 'not guaranteed') || str_contains($hay, 'no refill') || str_contains($hay, 'cheap') || str_contains($hay, 'bot')) {
            return 'economy';
        }

        if (
            str_contains($hay, 'guaranteed')
            || str_contains($hay, 'premium')
            || str_contains($hay, 'best')
            || preg_match('/\bhq\b/', $hay)
            || str_contains($hay, 'high quality')
            || str_contains($hay, 'refill 30')
            || str_contains($hay, 'refill 60')
            || str_contains($hay, 'refill 90')
            || str_contains($hay, 'lifetime')
        ) {
            return 'premium';
        }

        return 'standard';
    }

    public function detectPlatform(string $label): string
    {
        $value = strtolower($label);

        foreach ([
            'instagram' => 'instagram',
            'tiktok' => 'tiktok',
            'facebook' => 'facebook',
            'youtube' => 'youtube',
            'twitter' => 'twitter',
            'threads' => 'threads',
            'telegram' => 'telegram',
            'spotify' => 'spotify',
            'linkedin' => 'linkedin',
        ] as $needle => $platform) {
            if (str_contains($value, $needle)) {
                return $platform;
            }
        }

        return 'other';
    }

    public function resolvePlatform(string $slug): ?CatalogPlatform
    {
        if (isset($this->platformsBySlug[$slug])) {
            return $this->platformsBySlug[$slug];
        }

        $platform = CatalogPlatform::query()->where('slug', $slug)->first();

        if (! $platform) {
            $platform = CatalogPlatform::query()->create([
                'slug' => $slug,
                'name' => $this->humanizeSlug($slug),
                'icon_key' => $slug,
                'sort_order' => 999,
                'is_active' => true,
            ]);
        }

        return $this->platformsBySlug[$slug] = $platform;
    }

    public function resolveCategory(?CatalogPlatform $platform, string $slug, ?string $name = null): ?CatalogCategory
    {
        if (! $platform) {
            return null;
        }

        $key = $platform->id.':'.$slug;
        if (isset($this->categoriesByKey[$key])) {
            return $this->categoriesByKey[$key];
        }

        $category = CatalogCategory::query()->firstOrCreate(
            [
                'platform_id' => $platform->id,
                'slug' => $slug,
            ],
            [
                'name' => $name ?: $this->humanizeSlug($slug),
                'sort_order' => 500,
                'is_active' => true,
            ],
        );

        return $this->categoriesByKey[$key] = $category;
    }

    protected function rules(): Collection
    {
        if (static::$rulesCache !== null) {
            return static::$rulesCache;
        }

        static::$rulesCache = CatalogCategoryRule::query()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        return static::$rulesCache;
    }

    public static function clearCache(): void
    {
        static::$rulesCache = null;
        Cache::forget('catalog.platforms_with_counts');
    }

    protected function humanizeSlug(string $slug): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $slug));
    }

    protected function fallbackCategorySlug(string $hayCategory, string $hayName): string
    {
        $hay = $hayCategory.' '.$hayName;

        if (str_contains($hay, 'reaction') && (str_contains($hay, 'love') || str_contains($hay, '❤') || str_contains($hay, '😍'))) {
            return str_contains($hay, 'story') ? 'stories' : 'likes';
        }
        if (str_contains($hay, 'reaction') && (str_contains($hay, 'wow') || str_contains($hay, '😲'))) {
            return str_contains($hay, 'story') ? 'stories' : 'likes';
        }
        if (str_contains($hay, 'reaction') && (str_contains($hay, 'haha') || str_contains($hay, '😀') || str_contains($hay, '😂'))) {
            return str_contains($hay, 'story') ? 'stories' : 'likes';
        }
        if (str_contains($hay, 'reaction') && (str_contains($hay, 'sad') || str_contains($hay, '😢'))) {
            return str_contains($hay, 'story') ? 'stories' : 'likes';
        }
        if (str_contains($hay, 'reaction') && (str_contains($hay, 'angry') || str_contains($hay, '😡'))) {
            return str_contains($hay, 'story') ? 'stories' : 'likes';
        }
        if (str_contains($hay, 'page like') || str_contains($hay, 'page likes')) {
            return 'page_likes';
        }
        if (preg_match('/\bgroup\s*members?\b/iu', $hay) || (str_contains($hay, 'group') && str_contains($hay, 'member'))) {
            return 'members';
        }
        if (preg_match('/\bfriends?\b/iu', $hay) && ! str_contains($hay, 'follower')) {
            return 'friends';
        }
        if (str_contains($hay, 'follower') || str_contains($hay, 'subscriber')) {
            return 'followers';
        }
        if (str_contains($hay, 'like')) {
            return 'likes';
        }
        if (str_contains($hay, 'view')) {
            return 'views';
        }
        if (str_contains($hay, 'comment')) {
            return 'comments';
        }
        if (str_contains($hay, 'share')) {
            return 'shares';
        }
        if (str_contains($hay, 'story')) {
            return 'stories';
        }
        if (str_contains($hay, 'save')) {
            return 'saves';
        }
        if (str_contains($hay, 'reach')) {
            return 'reach';
        }
        if (str_contains($hay, 'impression')) {
            return 'impressions';
        }
        if (str_contains($hay, 'engagement')) {
            return 'engagement';
        }
        if (str_contains($hay, 'member')) {
            return 'members';
        }
        if (str_contains($hay, 'traffic')) {
            return 'traffic';
        }

        return 'other';
    }
}

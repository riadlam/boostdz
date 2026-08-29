<?php

namespace Database\Seeders;

use App\Models\StorefrontReviewsSettings;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        StorefrontReviewsSettings::current();

        $items = [
            [
                'name' => 'John Mitchell',
                'avatar_path' => '/assets/testimonials/1.webp',
                'quote' => [
                    'en' => 'I was skeptical at first, but BOOSTDZ delivered exactly what they promised. My Instagram posts went from 50 likes to over 500 in a week, and the followers are real accounts — I checked. Game changer for my small business.',
                    'fr' => "J'étais sceptique au début, mais BOOSTDZ a livré exactement ce qu'ils promettaient. Mes publications Instagram sont passées de 50 likes à plus de 500 en une semaine, et les abonnés sont de vrais comptes — j'ai vérifié. Un vrai changement pour ma petite entreprise.",
                    'ar' => 'كنت متشككًا في البداية، لكن BOOSTDZ قدم بالضبط ما وعدوا به. منشوراتي على إنستغرام قفزت من 50 إعجابًا إلى أكثر من 500 في أسبوع، والمتابعون حسابات حقيقية — تحققت من ذلك. تغيير جذري لعملي الصغير.',
                ],
                'role' => [
                    'en' => 'Founder @ Northbay Goods',
                    'fr' => 'Fondateur @ Northbay Goods',
                    'ar' => 'المؤسس @ Northbay Goods',
                ],
            ],
            [
                'name' => 'Sarah Chen',
                'avatar_path' => '/assets/testimonials/2.webp',
                'quote' => [
                    'en' => 'Got my first 1k followers in days. Real ones. BOOSTDZ made posting feel good again — engagement that actually sticks.',
                    'fr' => "J'ai obtenu mes 1 000 premiers abonnés en quelques jours. De vrais. BOOSTDZ m'a redonné envie de publier — un engagement qui dure vraiment.",
                    'ar' => 'حصلت على أول 1000 متابع في أيام. حقيقيون. BOOSTDZ جعل النشر ممتعًا مرة أخرى — تفاعل يدوم فعلًا.',
                ],
                'role' => [
                    'en' => 'Content Creator',
                    'fr' => 'Créatrice de contenu',
                    'ar' => 'صانعة محتوى',
                ],
            ],
            [
                'name' => 'Marcus Webb',
                'avatar_path' => '/assets/testimonials/3.webp',
                'quote' => [
                    'en' => 'We run 12 client accounts. BOOSTDZ is the only panel that delivers consistently without drop-offs. Our agency workflow depends on it.',
                    'fr' => "Nous gérons 12 comptes clients. BOOSTDZ est le seul panel qui livre de façon constante sans chutes. Notre flux de travail d'agence en dépend.",
                    'ar' => 'ندير 12 حسابًا للعملاء. BOOSTDZ هو اللوحة الوحيدة التي تسلم باستمرار دون انخفاضات. يعتمد سير عمل وكالتنا عليه.',
                ],
                'role' => [
                    'en' => 'Agency Director',
                    'fr' => "Directeur d'agence",
                    'ar' => 'مدير وكالة',
                ],
            ],
            [
                'name' => 'Elena Rodriguez',
                'avatar_path' => '/assets/testimonials/4.webp',
                'quote' => [
                    'en' => 'TikTok views landed within minutes. My FYP reach doubled in two weeks. Best investment for a new creator.',
                    'fr' => 'Les vues TikTok sont arrivées en quelques minutes. Ma portée FYP a doublé en deux semaines. Le meilleur investissement pour un nouveau créateur.',
                    'ar' => 'مشاهدات TikTok وصلت خلال دقائق. تضاعف وصول FYP خلال أسبوعين. أفضل استثمار لمبدع جديد.',
                ],
                'role' => [
                    'en' => 'TikTok Creator',
                    'fr' => 'Créatrice TikTok',
                    'ar' => 'مبدعة TikTok',
                ],
            ],
            [
                'name' => 'David Park',
                'avatar_path' => '/assets/testimonials/5.webp',
                'quote' => [
                    'en' => 'Support answered in under 5 minutes when I had a question. That alone makes BOOSTDZ worth it for our brand team.',
                    'fr' => "Le support a répondu en moins de 5 minutes quand j'avais une question. Cela à lui seul vaut BOOSTDZ pour notre équipe de marque.",
                    'ar' => 'أجاب الدعم في أقل من 5 دقائق عندما كان لدي سؤال. هذا وحده يجعل BOOSTDZ يستحق لفرقة علامتنا التجارية.',
                ],
                'role' => [
                    'en' => 'Brand Manager',
                    'fr' => 'Responsable de marque',
                    'ar' => 'مدير العلامة التجارية',
                ],
            ],
        ];

        foreach ($items as $index => $item) {
            Testimonial::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'quote' => $item['quote'],
                    'role' => $item['role'],
                    'avatar_path' => $item['avatar_path'],
                    'sort_order' => $index + 1,
                    'is_published' => true,
                ],
            );
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    /** @var list<string> */
    protected const SUPPORTED = ['en', 'fr', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolveLocale($request));

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header === '') {
            return (string) config('app.locale', 'en');
        }

        foreach (explode(',', $header) as $part) {
            $tag = trim(explode(';', $part)[0]);
            $code = strtolower(substr($tag, 0, 2));

            if (in_array($code, self::SUPPORTED, true)) {
                return $code;
            }
        }

        return (string) config('app.fallback_locale', 'en');
    }
}

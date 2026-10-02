<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    /**
     * Set the locale for the current API request from the client's language.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userLocale = $request->user()?->getAttribute('language')
            ?? $request->user()?->getAttribute('locale');
        $requestedLocale = $userLocale ?: $request->header('Accept-Language');

        app()->setLocale($this->resolveLocale($requestedLocale));

        return $next($request);
    }

    private function resolveLocale(?string $acceptLanguage): string
    {
        $supportedLocales = ['ar', 'en'];
        $languages = [];

        foreach (explode(',', (string) $acceptLanguage) as $preference) {
            [$language, $quality] = array_pad(explode(';q=', trim($preference), 2), 2, null);
            $locale = strtolower(str_replace('_', '-', trim($language)));
            $locale = explode('-', $locale)[0];
            $quality = $quality === null ? 1.0 : (float) $quality;

            if (in_array($locale, $supportedLocales, true) && $quality > 0) {
                $languages[] = ['locale' => $locale, 'quality' => $quality];
            }
        }

        usort($languages, static fn (array $a, array $b): int => $b['quality'] <=> $a['quality']);

        if ($languages !== []) {
            return $languages[0]['locale'];
        }

        $fallback = strtolower((string) config('app.locale', 'en'));

        return in_array($fallback, $supportedLocales, true) ? $fallback : 'en';
    }
}
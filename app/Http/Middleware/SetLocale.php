<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_keys(config('app.supported_locales', [
            'id' => ['name' => 'Bahasa Indonesia'],
            'en' => ['name' => 'English'],
        ]));

        $locale = null;

        // 1. Authenticated user preference
        if (auth()->check() && ! empty(auth()->user()->locale) && in_array(auth()->user()->locale, $supportedLocales, true)) {
            $locale = auth()->user()->locale;
        }

        // 2. Session preference (overrides if explicitly switched in current session or for guests)
        if (! $locale && session()->has('locale') && in_array(session('locale'), $supportedLocales, true)) {
            $locale = session('locale');
        }

        // 3. Fallback to application default
        if (! $locale) {
            $locale = config('app.locale', 'id');
        }

        if (! in_array($locale, $supportedLocales, true)) {
            $locale = config('app.fallback_locale', 'id');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}

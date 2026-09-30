<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch application locale and persist to session & user profile.
     */
    public function switch(Request $request, string $lang): RedirectResponse
    {
        $supportedLocales = array_keys(config('app.supported_locales', [
            'id' => ['name' => 'Bahasa Indonesia'],
            'en' => ['name' => 'English'],
        ]));

        if (in_array($lang, $supportedLocales, true)) {
            session(['locale' => $lang]);

            if (auth()->check()) {
                auth()->user()->update(['locale' => $lang]);
            }
        }

        return redirect()->back();
    }
}

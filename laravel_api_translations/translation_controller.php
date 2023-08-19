<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->input('locale');

        if (empty($locale)) {
            return response()->json(['error' => 'Please provide a locale in the request body'], 400);
        }

        $translations = $this->getTranslationsForLocale($locale);

        if ($translations === null) {
            return response()->json(['error' => "No translations found for locale: $locale"], 404);
        }

        return response()->json($translations);
    }

    public function listLocales()
    {
        $locales = $this->getAvailableLocales();

        $localesWithVersion = [];
        foreach ($locales as $locale) {
            $translations = $this->getTranslationsForLocale($locale);
            $version = isset($translations['@@version']) ? $translations['@@version'] : 'unknown';
            $localesWithVersion[] = [
                'locale' => $locale,
                'locale_version' => $version,
            ];
        }

        return response()->json($localesWithVersion);
    }

    protected function replaceParameters($translations)
    {
        array_walk_recursive($translations, function (&$value) {
            $value = preg_replace('/:([a-zA-Z_]+)/', '@$1', $value);
        });

        return $translations;
    }

    protected function getAvailableLocales()
    {
        $localeFiles = File::files(resource_path('lang'));

        $locales = [];
        foreach ($localeFiles as $file) {
            $locales[] = pathinfo($file, PATHINFO_FILENAME);
        }

        return $locales;
    }

    protected function getTranslationsForLocale($locale)
    {
        $localeFilePath = resource_path("lang/{$locale}.json");

        if (!File::exists($localeFilePath)) {
            return null;
        }

        $translations = json_decode(file_get_contents($localeFilePath), true);
        $translations = $this->replaceParameters($translations);

        return $translations;
    }
}

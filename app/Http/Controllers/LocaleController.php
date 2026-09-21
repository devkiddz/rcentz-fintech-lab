<?php

namespace App\Http\Controllers;

use App\Services\LocalizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LocaleController extends Controller
{
    public function update(Request $request, LocalizationService $localization): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'string', 'max:16']]);
        $code = $data['locale'];

        if (! $localization->isEnabled($code)) {
            return back()->with('error', 'That language is not enabled for this platform.');
        }

        session(['locale' => $code]);
        app()->setLocale($code);

        if ($request->user() && Schema::hasColumn('users', 'locale')) {
            $request->user()->forceFill(['locale' => $code])->save();
        }

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerPreferenceController extends Controller
{
    public function updateCurrency(Request $request)
    {
        $data = $request->validate([
            'currency' => ['required', Rule::in(['USD','EUR','GBP','NGN','JPY','AUD','CAD','CHF','CNY','INR','ZAR','SGD'])],
        ]);

        // Currency is a display preference. Base financial records remain USD.
        $request->user()->update(['currency' => $data['currency']]);

        return back()->with('success', 'Display currency updated to '.$data['currency'].'.');
    }
}

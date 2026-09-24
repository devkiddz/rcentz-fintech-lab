<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateMarketReference;
use App\Models\PublicInvestmentBaseAsset;

class BaseReferenceController extends Controller
{
    public function index()
    {
        $private = PrivateMarketReference::query();
        $public = PublicInvestmentBaseAsset::query();

        $stats = [
            'private_total' => (clone $private)->count(),
            'private_active' => (clone $private)->where('status', 'active')->count(),
            'public_total' => (clone $public)->count(),
            'public_active' => (clone $public)->where('status', 'active')->count(),
        ];

        return view('admin.investment-base-assets.index', compact('stats'));
    }
}

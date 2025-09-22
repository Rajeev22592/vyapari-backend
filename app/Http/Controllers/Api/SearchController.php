<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\BusinessListing;
use App\Models\TraderContact;
use App\Models\Market;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['results' => []]);
        }

        $commodities = Commodity::query()
            ->where('name', 'like', "%{$q}%")
            ->orWhereJsonContains('aliases', $q)
            ->limit(10)
            ->get(['id','name','slug','segment']);

        $markets = Market::query()
            ->where('name', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id','name','slug']);

        $listings = BusinessListing::query()
            ->where('name', 'like', "%{$q}%")
            ->orWhere('category', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id','name','category','product_segment']);

        $traders = TraderContact::query()
            ->where('name', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id','name','segment','is_verified']);

        return response()->json([
            'commodities' => $commodities,
            'markets' => $markets,
            'listings' => $listings,
            'traders' => $traders,
        ]);
    }
}

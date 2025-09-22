<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Price;
use App\Models\NewsPost;
use App\Models\Advertisement;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $stateId = $request->integer('state_id');
        $districtId = $request->integer('district_id');

        $highlights = Price::query()
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->whereDate('date', today())
            ->with('commodity:id,name,segment,unit')
            ->latest('updated_at')
            ->limit(20)
            ->get(['id','commodity_id','modal_price','trend_change','state_id','district_id','market_id']);

        $news = NewsPost::query()
            ->where('visibility', 'public')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get(['id','title','slug','excerpt','published_at','media']);

        $ads = Advertisement::query()
            ->where('is_active', true)
            ->where('placement', 'home_banner')
            ->orderByDesc('starts_at')
            ->limit(5)
            ->get(['id','title','media_url','link_url']);

        return response()->json([
            'highlights' => $highlights,
            'news' => $news,
            'ads' => $ads,
        ]);
    }
}

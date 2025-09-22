<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\Market;
use App\Models\Commodity;
use App\Models\Price;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function overview()
    {
        $today = today();
        $yesterday = today()->subDay();
        
        return response()->json([
            'totalRegisteredMandis' => Market::count(),
            'totalStatesAndUTs' => State::count(),
            'liveMarketsToday' => Price::whereDate('date', $today)->distinct('market_id')->count('market_id'),
            'commoditiesTraded' => Commodity::count(),
            
            // Additional useful stats
            'totalDistricts' => \App\Models\District::count(),
            'totalPricesToday' => Price::whereDate('date', $today)->count(),
            'totalPricesYesterday' => Price::whereDate('date', $yesterday)->count(),
            'priceChangePercentage' => $this->calculatePriceChangePercentage($today, $yesterday),
            'topCommodities' => $this->getTopCommodities($today),
            'topStates' => $this->getTopStates($today),
        ]);
    }

    private function calculatePriceChangePercentage($today, $yesterday)
    {
        $todayAvg = Price::whereDate('date', $today)->avg('modal_price');
        $yesterdayAvg = Price::whereDate('date', $yesterday)->avg('modal_price');
        
        if ($yesterdayAvg == 0) return 0;
        
        return round((($todayAvg - $yesterdayAvg) / $yesterdayAvg) * 100, 2);
    }

    private function getTopCommodities($date, $limit = 5)
    {
        return Price::whereDate('date', $date)
            ->join('commodities', 'prices.commodity_id', '=', 'commodities.id')
            ->selectRaw('commodities.name, commodities.segment, COUNT(*) as price_count, AVG(modal_price) as avg_price')
            ->groupBy('commodities.id', 'commodities.name', 'commodities.segment')
            ->orderByDesc('price_count')
            ->limit($limit)
            ->get();
    }

    private function getTopStates($date, $limit = 5)
    {
        return Price::whereDate('date', $date)
            ->join('states', 'prices.state_id', '=', 'states.id')
            ->selectRaw('states.name, COUNT(*) as price_count, AVG(modal_price) as avg_price')
            ->groupBy('states.id', 'states.name')
            ->orderByDesc('price_count')
            ->limit($limit)
            ->get();
    }
}

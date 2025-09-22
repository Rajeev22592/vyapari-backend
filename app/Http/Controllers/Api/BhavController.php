<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Price;
use Illuminate\Http\Request;

class BhavController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'bhav:'.md5(json_encode($request->query()));
        if ($cached = cache()->get($cacheKey)) {
            return $cached;
        }
        $stateId = $request->integer('state');
        $districtId = $request->integer('district');
        $marketId = $request->integer('market');
        $commodityId = $request->integer('commodity');
        $segment = $request->string('segment')->toString() ?: null; // grains|pulses|oils|spices|dry-fruits|rice
        $date = $request->date('date') ?? today();

        $q = Price::query()
            ->when($stateId, fn($x) => $x->where('state_id', $stateId))
            ->when($districtId, fn($x) => $x->where('district_id', $districtId))
            ->when($marketId, fn($x) => $x->where('market_id', $marketId))
            ->when($commodityId, fn($x) => $x->where('commodity_id', $commodityId))
            ->when($segment, function ($x) use ($segment) {
                $x->whereHas('commodity', fn($c) => $c->where('segment', $segment));
            })
            ->whereDate('date', $date)
            ->with(['commodity:id,name,unit,segment','market:id,name']);

        $data = $q->orderBy('commodity_id')->paginate($request->integer('perPage') ?: 50);

        // Trend vs yesterday
        $yesterday = $date->copy()->subDay();
        $ids = collect($data->items())->pluck('commodity_id')->unique()->values();
        $yMap = Price::query()
            ->whereIn('commodity_id', $ids)
            ->when($stateId, fn($x) => $x->where('state_id', $stateId))
            ->when($districtId, fn($x) => $x->where('district_id', $districtId))
            ->when($marketId, fn($x) => $x->where('market_id', $marketId))
            ->whereDate('date', $yesterday)
            ->get(['commodity_id','modal_price'])
            ->keyBy('commodity_id');

        $data->getCollection()->transform(function ($row) use ($yMap) {
            $prev = $yMap[$row->commodity_id]->modal_price ?? null;
            $change = $prev !== null ? round(($row->modal_price - $prev), 2) : null;
            $row->trend_change = $change;
            return $row;
        });

        cache()->put($cacheKey, $data, now()->addMinutes(15));
        return $data;
    }
}

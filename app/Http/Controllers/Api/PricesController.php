<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Price;
use Illuminate\Support\Facades\DB;

class PricesController extends Controller
{
    public function daily(Request $request)
    {
        $stateId = $request->integer('state_id');
        $districtId = $request->integer('district_id');
        $marketId = $request->integer('market_id');
        $commodityId = $request->integer('commodity_id');
        $date = $request->date('date') ?? today();

        $query = Price::query()
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->when($marketId, fn($q) => $q->where('market_id', $marketId))
            ->when($commodityId, fn($q) => $q->where('commodity_id', $commodityId))
            ->whereDate('date', $date)
            ->with(['commodity:id,name,segment,unit','market:id,name']);

        return $query->orderBy('commodity_id')->paginate(50);
    }

    public function highlights(Request $request)
    {
        $stateId = $request->integer('state_id');
        $districtId = $request->integer('district_id');

        $query = Price::query()
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->whereDate('date', today())
            ->with('commodity:id,name,segment,unit')
            ->orderByDesc('trend_change');

        $data = $query->limit(20)->get(['id','commodity_id','modal_price','trend_change']);
        $etag = '"'.md5($data->toJson()).'"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return response('')->setStatusCode(304)->header('ETag', $etag);
        }
        return response()->json($data)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=60');
    }

    public function history(Request $request)
    {
        $commodityId = $request->integer('commodityId');
        $marketId = $request->integer('marketId');
        $interval = $request->string('interval')->toString() ?: 'day'; // day|week|month
        $limit = max(1, min(365, (int) $request->integer('limit') ?: 30));

        $q = Price::query()
            ->when($commodityId, fn($x) => $x->where('commodity_id', $commodityId))
            ->when($marketId, fn($x) => $x->where('market_id', $marketId));

        switch ($interval) {
            case 'month':
                $q->selectRaw('DATE_FORMAT(date, "%Y-%m-01") as date, AVG(min_price) as min, AVG(max_price) as max, AVG(modal_price) as modal')
                  ->groupByRaw('DATE_FORMAT(date, "%Y-%m-01")')
                  ->orderBy('date', 'desc');
                break;
            case 'week':
                $q->selectRaw('STR_TO_DATE(CONCAT(YEARWEEK(date, 3), " Monday"), "%X%V %W") as date, AVG(min_price) as min, AVG(max_price) as max, AVG(modal_price) as modal')
                  ->groupByRaw('YEARWEEK(date, 3)')
                  ->orderBy('date', 'desc');
                break;
            default:
                $q->selectRaw('DATE(date) as date, AVG(min_price) as min, AVG(max_price) as max, AVG(modal_price) as modal')
                  ->groupByRaw('DATE(date)')
                  ->orderBy('date', 'desc');
        }

        $rows = $q->limit($limit)->get();
        $rows = $rows->reverse()->values();

        $stats = [
            'avg' => (float) round($rows->avg('modal'), 2),
            'min' => (float) ($rows->min('modal') ?? 0),
            'max' => (float) ($rows->max('modal') ?? 0),
        ];
        $stats['volatility'] = (float) (($stats['max'] - $stats['min']) ?: 0);
        $stats['momPct'] = isset($rows[0], $rows[count($rows)-1]) && $rows[0]['modal']
            ? (float) round((($rows[count($rows)-1]['modal'] - $rows[0]['modal']) / $rows[0]['modal']) * 100, 2)
            : 0.0;

        $payload = [ 'series' => $rows, 'stats' => $stats ];
        $etag = '"'.md5(json_encode($payload)).'"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return response('')->setStatusCode(304)->header('ETag', $etag);
        }
        return response()->json($payload)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Markets with downward price movement vs yesterday (avg modal price).
     * Filters: state_id, district_id, segment, date. Pagination supported via perPage.
     */
    public function marketsDown(Request $request)
    {
        $stateId = $request->integer('state_id');
        $districtId = $request->integer('district_id');
        $segment = $request->string('segment')->toString() ?: null; // grains|oils|...
        $date = $request->date('date') ?? today();
        $perPage = max(1, min(200, (int) ($request->integer('perPage') ?: 50)));

        $yesterday = $date->copy()->subDay();

        // Aggregate today's average modal per market
        $todayAgg = Price::query()
            ->select('market_id', DB::raw('AVG(modal_price) as today_modal'), DB::raw('COUNT(*) as items'))
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->when($segment, function ($q) use ($segment) {
                $q->whereHas('commodity', fn($c) => $c->where('segment', $segment));
            })
            ->whereDate('date', $date)
            ->groupBy('market_id');

        // Aggregate yesterday's average modal per market
        $yAgg = Price::query()
            ->select('market_id', DB::raw('AVG(modal_price) as y_modal'))
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->when($segment, function ($q) use ($segment) {
                $q->whereHas('commodity', fn($c) => $c->where('segment', $segment));
            })
            ->whereDate('date', $yesterday)
            ->groupBy('market_id');

        $query = DB::query()
            ->fromSub($todayAgg, 't')
            ->leftJoinSub($yAgg, 'y', 'y.market_id', '=', 't.market_id')
            ->join('markets', 'markets.id', '=', 't.market_id')
            ->leftJoin('districts','districts.id','=','markets.district_id')
            ->leftJoin('states','states.id','=','markets.state_id')
            ->select([
                't.market_id as market_id',
                'markets.name as market',
                DB::raw('COALESCE(districts.name, "") as district_name'),
                DB::raw('COALESCE(states.name, "") as state_name'),
                // representative commodity for the market (today's highest modal)
                DB::raw('(SELECT c.name FROM prices p JOIN commodities c ON c.id = p.commodity_id WHERE p.market_id = t.market_id AND DATE(p.date) = "'.$date->toDateString().'" ORDER BY p.modal_price DESC LIMIT 1) as commodity'),
                DB::raw('ROUND(t.today_modal, 2) as today_modal'),
                DB::raw('ROUND(COALESCE(y.y_modal, t.today_modal), 2) as yesterday_modal'),
                DB::raw('ROUND(t.today_modal - COALESCE(y.y_modal, t.today_modal), 2) as delta'),
                't.items as items',
            ])
            ->orderBy('delta', 'asc'); // most negative first

        // Only keep markets with a negative change
        $query->whereRaw('(t.today_modal - COALESCE(y.y_modal, t.today_modal)) < 0');

        return $query->paginate($perPage);
    }

    /**
     * Markets with upward price movement vs yesterday (avg modal price).
     * Same filters as marketsDown.
     */
    public function marketsUp(Request $request)
    {
        $stateId = $request->integer('state_id');
        $districtId = $request->integer('district_id');
        $segment = $request->string('segment')->toString() ?: null;
        $date = $request->date('date') ?? today();
        $perPage = max(1, min(200, (int) ($request->integer('perPage') ?: 50)));

        $yesterday = $date->copy()->subDay();

        $todayAgg = Price::query()
            ->select('market_id', DB::raw('AVG(modal_price) as today_modal'), DB::raw('COUNT(*) as items'))
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->when($segment, function ($q) use ($segment) {
                $q->whereHas('commodity', fn($c) => $c->where('segment', $segment));
            })
            ->whereDate('date', $date)
            ->groupBy('market_id');

        $yAgg = Price::query()
            ->select('market_id', DB::raw('AVG(modal_price) as y_modal'))
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->when($segment, function ($q) use ($segment) {
                $q->whereHas('commodity', fn($c) => $c->where('segment', $segment));
            })
            ->whereDate('date', $yesterday)
            ->groupBy('market_id');

        $query = DB::query()
            ->fromSub($todayAgg, 't')
            ->leftJoinSub($yAgg, 'y', 'y.market_id', '=', 't.market_id')
            ->join('markets', 'markets.id', '=', 't.market_id')
            ->leftJoin('districts','districts.id','=','markets.district_id')
            ->leftJoin('states','states.id','=','markets.state_id')
            ->select([
                't.market_id as market_id',
                'markets.name as market',
                DB::raw('COALESCE(districts.name, "") as district_name'),
                DB::raw('COALESCE(states.name, "") as state_name'),
                DB::raw('(SELECT c.name FROM prices p JOIN commodities c ON c.id = p.commodity_id WHERE p.market_id = t.market_id AND DATE(p.date) = "'.$date->toDateString().'" ORDER BY p.modal_price DESC LIMIT 1) as commodity'),
                DB::raw('ROUND(t.today_modal, 2) as today_modal'),
                DB::raw('ROUND(COALESCE(y.y_modal, t.today_modal), 2) as yesterday_modal'),
                DB::raw('ROUND(t.today_modal - COALESCE(y.y_modal, t.today_modal), 2) as delta'),
                't.items as items',
            ])
            ->orderBy('delta', 'desc');

        $query->whereRaw('(t.today_modal - COALESCE(y.y_modal, t.today_modal)) > 0');

        return $query->paginate($perPage);
    }

    /**
     * List all mandis (markets) with optional filters.
     * Filters: state_id, district_id, search, perPage.
     */
    public function mandis(Request $request)
    {
        $stateId = $request->integer('state_id');
        $districtId = $request->integer('district_id');
        $search = $request->string('search')->toString() ?: null;
        $perPage = max(1, min(200, (int) ($request->integer('perPage') ?: 20)));

        $query = DB::table('markets')
            ->leftJoin('districts', 'districts.id', '=', 'markets.district_id')
            ->leftJoin('states', 'states.id', '=', 'markets.state_id')
            ->select([
                'markets.id as market_id',
                'markets.name as market',
                'markets.slug as market_slug',
                'markets.type as market_type',
                'markets.lat',
                'markets.lng',
                'districts.name as district_name',
                'states.name as state_name',
                'states.code as state_code',
            ])
            ->when($stateId, fn($q) => $q->where('markets.state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('markets.district_id', $districtId))
            ->when($search, fn($q) => $q->where(function($query) use ($search) {
                $query->where('markets.name', 'like', "%{$search}%")
                      ->orWhere('districts.name', 'like', "%{$search}%")
                      ->orWhere('states.name', 'like', "%{$search}%");
            }))
            ->orderBy('states.name')
            ->orderBy('districts.name')
            ->orderBy('markets.name');

        return $query->paginate($perPage);
    }
}

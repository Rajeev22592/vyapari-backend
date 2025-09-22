<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\FiltersController;
use App\Http\Controllers\Api\PricesController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\AdsController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BhavController;
use App\Http\Controllers\Api\AlertsController;
use App\Services\AgmarknetIngestService;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\DB;

Route::prefix('v1')->group(function () {
    // Auth (Sanctum tokens)
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
    });

    Route::get('home', [HomeController::class, 'index']);
    Route::get('stats/overview', [StatsController::class, 'overview']);
    Route::get('bhav', [BhavController::class, 'index']);

    Route::get('filters/states', [FiltersController::class, 'states']);
    Route::get('filters/districts', [FiltersController::class, 'districts']);
    Route::get('filters/markets', [FiltersController::class, 'markets']);
    Route::get('filters/segments', [FiltersController::class, 'segments']);
    Route::get('filters/segments-with-commodities', [FiltersController::class, 'segmentsWithCommodities']);
    Route::get('filters/commodities', [FiltersController::class, 'commodities']);

    Route::get('prices/daily', [PricesController::class, 'daily']);
    Route::get('prices/highlights', [PricesController::class, 'highlights']);
    Route::get('prices/history', [PricesController::class, 'history']);
    Route::get('prices/markets-down', [PricesController::class, 'marketsDown']);
    Route::get('prices/markets-up', [PricesController::class, 'marketsUp']);
    Route::get('mandis', [PricesController::class, 'mandis']);

    Route::get('news', [NewsController::class, 'index']);
    Route::get('ads', [AdsController::class, 'index']);
    Route::get('directory', [DirectoryController::class, 'index']);

    Route::get('search', [SearchController::class, 'index']);

    // Alerts (public or authenticated)
    Route::post('alerts', [AlertsController::class, 'store']);

    // Admin protected routes example
    Route::middleware(['auth:sanctum','role:admin'])->prefix('admin')->group(function () {
        Route::post('prices/import', function () {
            return response()->json(['ok' => true]);
        });
        Route::post('ads', function () {
            return response()->json(['ok' => true]);
        });

        Route::post('ingest/agmarknet', function (\Illuminate\Http\Request $request, AgmarknetIngestService $svc) {
            $apiKey = config('services.data_gov.api_key');
            $date = $request->string('date')->toString() ?: null; // YYYY-MM-DD
            $res = $svc->ingest($apiKey, $date);
            return response()->json($res);
        });

        Route::get('i18n/en', function () {
            $states = DB::table('states')
                ->orderBy('name')
                ->get(['slug','name']);

            $districts = DB::table('districts')
                ->join('states','districts.state_id','=','states.id')
                ->orderBy('states.slug')
                ->orderBy('districts.name')
                ->get([
                    'states.slug as state_slug',
                    'districts.name as name',
                ]);

            $markets = DB::table('markets')
                ->join('districts','markets.district_id','=','districts.id')
                ->join('states','markets.state_id','=','states.id')
                ->orderBy('states.slug')
                ->orderBy('districts.name')
                ->orderBy('markets.name')
                ->get([
                    'states.slug as state_slug',
                    'districts.name as district',
                    'markets.name as name',
                ]);

            $commodities = DB::table('commodities')
                ->orderBy('segment')
                ->orderBy('name')
                ->get(['segment','name']);

            return response()->json([
                'states' => $states,
                'districts' => $districts,
                'markets' => $markets,
                'commodities' => $commodities,
            ]);
        });
    });
});


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\District;
use App\Models\Market;
use Illuminate\Http\Request;

class FiltersController extends Controller
{
    public function states()
    {
        $lang = request()->header('Accept-Language', 'en');
        $cols = ['id','slug','code','name'];
        if ($lang === 'hi') { $cols[] = 'name_hi as name_local'; }
        return State::query()->orderBy('name')->get($cols);
    }

    public function districts(Request $request)
    {
        $stateId = $request->integer('state_id');
        $lang = $request->header('Accept-Language', 'en');
        $cols = ['id','state_id','slug','name'];
        if ($lang === 'hi') { $cols[] = 'name_hi as name_local'; }
        return District::query()
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->orderBy('name')
            ->get($cols);
    }

    public function markets(Request $request)
    {
        $stateId = $request->integer('state_id');
        $districtId = $request->integer('district_id');
        $lang = $request->header('Accept-Language', 'en');
        $cols = ['id','state_id','district_id','slug','name','type'];
        if ($lang === 'hi') { $cols[] = 'name_hi as name_local'; }
        return Market::query()
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->orderBy('name')
            ->get($cols);
    }

    public function segments()
    {
        $lang = request()->header('Accept-Language', 'en');
        
        $segments = [
            'grains' => [
                'name' => $lang === 'hi' ? 'अनाज' : 'Grains',
                'icon' => 'wheat',
                'description' => $lang === 'hi' ? 'गेहूं, चावल, बाजरा' : 'Wheat, Rice, Bajra',
                'examples' => ['Wheat', 'Rice', 'Bajra', 'Jowar', 'Maize', 'Barley', 'Oats']
            ],
            'pulses' => [
                'name' => $lang === 'hi' ? 'दालें' : 'Pulses', 
                'icon' => 'beans',
                'description' => $lang === 'hi' ? 'चना, अरहर, मूंग' : 'Chana, Arhar, Moong',
                'examples' => ['Chana', 'Arhar', 'Moong', 'Masoor', 'Urad', 'Gram']
            ],
            'oils' => [
                'name' => $lang === 'hi' ? 'तेल' : 'Oils',
                'icon' => 'barrel',
                'description' => $lang === 'hi' ? 'सरसों, मूंगफली, सूरजमुखी' : 'Mustard, Groundnut, Sunflower',
                'examples' => ['Mustard Oil', 'Groundnut Oil', 'Sunflower Oil', 'Soybean Oil', 'Sesame Oil']
            ],
            'spices' => [
                'name' => $lang === 'hi' ? 'मसाले' : 'Spices',
                'icon' => 'chili',
                'description' => $lang === 'hi' ? 'हल्दी, धनिया, जीरा' : 'Turmeric, Coriander, Cumin',
                'examples' => ['Turmeric', 'Coriander', 'Cumin', 'Red Chilli', 'Cardamom', 'Fennel']
            ],
            'dry-fruits' => [
                'name' => $lang === 'hi' ? 'ड्राई फ्रूट्स' : 'Dry Fruits',
                'icon' => 'basket',
                'description' => $lang === 'hi' ? 'बादाम, काजू, खजूर' : 'Almonds, Cashews, Dates',
                'examples' => ['Almonds', 'Cashews', 'Raisins', 'Walnuts', 'Dates', 'Pistachios']
            ],
            'rice' => [
                'name' => $lang === 'hi' ? 'चावल' : 'Rice',
                'icon' => 'rice-bowl',
                'description' => $lang === 'hi' ? 'बासमती, नॉन-बासमती, टूटा' : 'Basmati, Non-Basmati, Broken',
                'examples' => ['Basmati Rice', 'Non-Basmati Rice', 'Broken Rice', 'Pusa Rice', 'Sarbati Rice']
            ]
        ];

        return response()->json($segments);
    }

    public function commodities(Request $request)
    {
        $segment = $request->string('segment')->toString();
        $lang = $request->header('Accept-Language', 'en');
        $cols = ['id','slug','name','segment','unit'];
        if ($lang === 'hi') { $cols[] = 'name_hi as name_local'; }
        return \App\Models\Commodity::query()
            ->when($segment, fn($q) => $q->where('segment', $segment))
            ->orderBy('name')
            ->get($cols);
    }

    public function segmentsWithCommodities()
    {
        $lang = request()->header('Accept-Language', 'en');
        
        // Get all commodities grouped by segment
        $commoditiesBySegment = \App\Models\Commodity::query()
            ->select(['segment', 'name', 'slug', 'unit'])
            ->when($lang === 'hi', function($q) {
                $q->addSelect(['name_hi as name_local']);
            })
            ->orderBy('name')
            ->get()
            ->groupBy('segment');

        // Define segment metadata
        $segmentMeta = [
            'grains' => [
                'name' => $lang === 'hi' ? 'अनाज' : 'Grains',
                'icon' => 'wheat',
                'description' => $lang === 'hi' ? 'गेहूं, चावल, बाजरा' : 'Wheat, Rice, Bajra'
            ],
            'pulses' => [
                'name' => $lang === 'hi' ? 'दालें' : 'Pulses',
                'icon' => 'beans', 
                'description' => $lang === 'hi' ? 'चना, अरहर, मूंग' : 'Chana, Arhar, Moong'
            ],
            'oils' => [
                'name' => $lang === 'hi' ? 'तेल' : 'Oils',
                'icon' => 'barrel',
                'description' => $lang === 'hi' ? 'सरसों, मूंगफली, सूरजमुखी' : 'Mustard, Groundnut, Sunflower'
            ],
            'spices' => [
                'name' => $lang === 'hi' ? 'मसाले' : 'Spices',
                'icon' => 'chili',
                'description' => $lang === 'hi' ? 'हल्दी, धनिया, जीरा' : 'Turmeric, Coriander, Cumin'
            ],
            'dry-fruits' => [
                'name' => $lang === 'hi' ? 'ड्राई फ्रूट्स' : 'Dry Fruits',
                'icon' => 'basket',
                'description' => $lang === 'hi' ? 'बादाम, काजू, खजूर' : 'Almonds, Cashews, Dates'
            ],
            'rice' => [
                'name' => $lang === 'hi' ? 'चावल' : 'Rice',
                'icon' => 'rice-bowl',
                'description' => $lang === 'hi' ? 'बासमती, नॉन-बासमती, टूटा' : 'Basmati, Non-Basmati, Broken'
            ]
        ];

        // Build response with actual commodities
        $result = [];
        foreach ($segmentMeta as $segmentKey => $meta) {
            $commodities = $commoditiesBySegment->get($segmentKey, collect());
            
            $result[$segmentKey] = array_merge($meta, [
                'commodities' => $commodities->take(10)->values(), // Top 10 commodities
                'total_commodities' => $commodities->count(),
                'has_more' => $commodities->count() > 10
            ]);
        }

        return response()->json($result);
    }
}

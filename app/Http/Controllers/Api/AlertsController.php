<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertsController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'commodity_id' => 'required|exists:commodities,id',
            'market_id' => 'nullable|exists:markets,id',
            'threshold_type' => 'in:above,below,change_pct',
            'threshold_value' => 'nullable|numeric',
            'channel' => 'in:sms,whatsapp,push,email',
        ]);

        $data['user_id'] = optional($request->user())->id;
        $alert = Alert::create($data);

        return response()->json($alert, 201);
    }
}

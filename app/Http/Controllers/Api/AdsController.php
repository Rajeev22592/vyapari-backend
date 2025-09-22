<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advertisement;

class AdsController extends Controller
{
    public function index(Request $request)
    {
        $placement = $request->string('placement')->toString();
        return Advertisement::query()
            ->where('is_active', true)
            ->when($placement, fn($q) => $q->where('placement', $placement))
            ->orderByDesc('starts_at')
            ->paginate(20, ['id','title','media_url','link_url','placement']);
    }
}

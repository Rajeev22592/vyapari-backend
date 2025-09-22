<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusinessListing;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        $stateId = $request->integer('state_id');
        $districtId = $request->integer('district_id');
        $category = $request->string('category')->toString();
        $segment = $request->string('segment')->toString();

        $query = BusinessListing::query()
            ->when($stateId, fn($q) => $q->where('state_id', $stateId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($segment, fn($q) => $q->where('product_segment', $segment))
            ->orderBy('name');

        return $query->paginate(20, ['id','name','category','product_segment','phone','whatsapp','address','state_id','district_id']);
    }
}

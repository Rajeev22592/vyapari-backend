<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NewsPost;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $visibility = $request->string('visibility')->toString() ?: 'public';
        return NewsPost::query()
            ->when($visibility, fn($q) => $q->where('visibility', $visibility))
            ->orderByDesc('published_at')
            ->paginate(20, ['id','title','slug','excerpt','published_at','media']);
    }
}

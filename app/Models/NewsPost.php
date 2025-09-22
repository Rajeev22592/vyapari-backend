<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsPost extends Model
{
    /** @use HasFactory<\Database\Factories\NewsPostFactory> */
    use HasFactory;

    protected $fillable = ['title','slug','excerpt','content','visibility','published_at','media'];
    protected $casts = [
        'published_at' => 'datetime',
        'media' => 'array',
    ];
}

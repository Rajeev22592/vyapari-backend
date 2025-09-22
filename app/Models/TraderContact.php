<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraderContact extends Model
{
    /** @use HasFactory<\Database\Factories\TraderContactFactory> */
    use HasFactory;

    protected $fillable = ['name','segment','phone','whatsapp','is_verified','meta'];
    protected $casts = [
        'is_verified' => 'boolean',
        'meta' => 'array',
    ];
}

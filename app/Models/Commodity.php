<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commodity extends Model
{
    /** @use HasFactory<\Database\Factories\CommodityFactory> */
    use HasFactory;

    protected $fillable = ['name','slug','segment','unit','aliases'];
    protected $casts = [
        'aliases' => 'array',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}

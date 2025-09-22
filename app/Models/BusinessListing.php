<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessListing extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessListingFactory> */
    use HasFactory;

    protected $fillable = ['name','category','product_segment','phone','whatsapp','email','address','state_id','district_id','meta'];
    protected $casts = [
        'meta' => 'array',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}

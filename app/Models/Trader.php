<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trader extends Model
{
    /** @use HasFactory<\Database\Factories\TraderFactory> */
    use HasFactory;

    protected $fillable = ['user_id','name','business','firm_name','gstin','address','city','state','rating','verified','phone','specialities','avatar_url','about','kyc_status'];
    protected $casts = [
        'verified' => 'boolean',
        'rating' => 'decimal:2',
        'specialities' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

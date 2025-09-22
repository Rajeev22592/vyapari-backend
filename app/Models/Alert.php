<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    /** @use HasFactory<\Database\Factories\AlertFactory> */
    use HasFactory;

    protected $fillable = ['user_id','commodity_id','market_id','threshold_type','threshold_value','channel','is_active','meta'];
    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function commodity(): BelongsTo { return $this->belongsTo(Commodity::class); }
    public function market(): BelongsTo { return $this->belongsTo(Market::class); }
}

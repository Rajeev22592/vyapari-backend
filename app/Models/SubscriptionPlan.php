<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionPlanFactory> */
    use HasFactory;

    protected $fillable = ['name','code','amount_inr','interval','duration_days','is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}

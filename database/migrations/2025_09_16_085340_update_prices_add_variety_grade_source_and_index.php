<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->string('variety')->nullable()->after('commodity_id');
            $table->string('grade')->nullable()->after('variety');
            $table->string('source')->nullable()->after('currency');
            $table->index(['market_id','commodity_id','date'], 'prices_market_commodity_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropIndex('prices_market_commodity_date_idx');
            $table->dropColumn(['variety','grade','source']);
        });
    }
};

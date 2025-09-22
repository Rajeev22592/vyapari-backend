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
        Schema::table('traders', function (Blueprint $table) {
            $table->string('firm_name')->nullable()->after('business');
            $table->string('gstin', 15)->nullable()->unique()->after('firm_name');
            $table->text('address')->nullable()->after('city');
            $table->string('kyc_status')->default('pending')->after('phone'); // pending|approved|rejected
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traders', function (Blueprint $table) {
            $table->dropUnique(['gstin']);
            $table->dropColumn(['firm_name','gstin','address','kyc_status']);
        });
    }
};

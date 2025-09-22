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
        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('title');
            $table->foreignId('target_state_id')->nullable()->after('image_url')->constrained('states')->nullOnDelete();
            $table->string('target_segment')->nullable()->after('target_state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_state_id');
            $table->dropColumn(['image_url','target_segment']);
        });
    }
};

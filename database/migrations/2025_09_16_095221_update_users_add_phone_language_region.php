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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('language', 5)->default('hi')->after('role');
            $table->foreignId('state_id')->nullable()->after('language')->constrained('states')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('state_id')->constrained('districts')->nullOnDelete();
            $table->unique('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('state_id');
            $table->dropColumn(['language','phone']);
        });
    }
};

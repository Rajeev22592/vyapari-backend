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
        Schema::create('traders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('business')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->boolean('verified')->default(false);
            $table->string('phone')->nullable();
            $table->json('specialities')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('about')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 8)->nullable()->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 16)->nullable();
            $table->string('slug');
            $table->timestamps();

            $table->unique(['country_id', 'slug']);
            $table->index(['country_id', 'name']);
            $table->index('code');
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['country_id', 'state_id', 'slug'], 'cities_unique_combination');
            $table->index(['country_id', 'name']);
            $table->index('name');
        });

        Schema::create('destinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('label');
            $table->string('slug')->unique();
            $table->timestamps();

            $table->unique('city_id');
            $table->index(['country_id', 'state_id']);
            $table->index('label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};

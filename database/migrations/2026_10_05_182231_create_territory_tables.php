<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->char('ibge_code', 2)->unique();
            $table->char('abbreviation', 2)->unique();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->boolean('active')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->restrictOnDelete();
            $table->char('ibge_code', 7)->unique();
            $table->string('name', 150);
            $table->string('slug', 160);
            $table->boolean('active')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['state_id', 'slug']);
            $table->index(['state_id', 'name', 'id']);
        });
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 160)->unique();
            $table->enum('kind', ['cultural', 'geographic']);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('municipality_region', function (Blueprint $table) {
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->primary(['municipality_id', 'region_id']);
            $table->index(['region_id', 'municipality_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_region');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('states');
    }
};

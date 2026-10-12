<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_feature_locks', function (Blueprint $table) {
            $table->string('scope', 40);
            $table->unsignedSmallInteger('position');
            $table->primary(['scope', 'position']);
        });

        Schema::create('publication_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('curator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['region_id', 'position', 'starts_at', 'ends_at'], 'feature_schedule');
            $table->index(['publication_id', 'starts_at', 'ends_at'], 'publication_feature_period');
        });

        DB::statement('ALTER TABLE publication_features ADD CONSTRAINT publication_feature_position_check CHECK (position BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE publication_features ADD CONSTRAINT publication_feature_period_check CHECK (ends_at IS NULL OR ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_features');
        Schema::dropIfExists('publication_feature_locks');
    }
};

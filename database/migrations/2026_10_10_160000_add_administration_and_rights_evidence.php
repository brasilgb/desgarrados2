<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('revision_rights_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_revision_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 40)->default('rights');
            $table->string('path', 500)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->enum('status', ['current', 'superseded'])->default('current');
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();
            $table->index(['publication_revision_id', 'status', 'id']);
        });

        Schema::create('administration_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['target_user_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administration_audits');
        Schema::dropIfExists('revision_rights_evidence');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};

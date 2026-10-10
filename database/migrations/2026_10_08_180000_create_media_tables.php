<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mariadb' || ! in_array(DB::connection()->getDatabaseName(), ['desgarrados2', 'desgarrados2_test'], true)) {
            throw new RuntimeException('Media migrations require the dedicated MariaDB databases.');
        }
        Schema::create('media_assets', function (Blueprint $t) {
            $t->id();
            // Random public identifier: numeric ids never appear in URLs.
            $t->uuid('uuid')->unique();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->char('sha256', 64);
            $t->string('original_path', 255);
            $t->string('original_name', 200)->nullable();
            $t->enum('mime', ['image/jpeg', 'image/png', 'image/webp']);
            $t->unsignedInteger('width');
            $t->unsignedInteger('height');
            $t->unsignedInteger('size_bytes');
            $t->enum('processing_status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $t->string('processing_error', 255)->nullable();
            $t->json('variants')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->enum('status', ['active', 'blocked'])->default('active');
            $t->string('blocked_reason', 500)->nullable();
            $t->enum('rights_type', ['own', 'authorized', 'licensed', 'creative_commons', 'public_domain'])->nullable();
            $t->string('rights_holder', 200)->nullable();
            $t->string('license', 120)->nullable();
            $t->string('rights_notes', 1000)->nullable();
            $t->timestamps();
            // The same file uploaded again by the same owner is reused, not duplicated.
            $t->unique(['owner_id', 'sha256']);
            $t->index(['processing_status', 'updated_at']);
            $t->index(['owner_id', 'created_at', 'id']);
        });
        DB::statement('ALTER TABLE media_assets ADD CONSTRAINT media_dimensions_check CHECK (width > 0 AND height > 0 AND size_bytes > 0)');
        DB::statement("ALTER TABLE media_assets ADD CONSTRAINT media_ready_check CHECK (processing_status <> 'ready' OR (variants IS NOT NULL AND processed_at IS NOT NULL))");
        DB::statement("ALTER TABLE media_assets ADD CONSTRAINT media_blocked_reason_check CHECK (status <> 'blocked' OR blocked_reason IS NOT NULL)");

        Schema::create('revision_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('publication_revision_id')->constrained()->restrictOnDelete();
            $t->foreignId('media_asset_id')->constrained()->restrictOnDelete();
            $t->enum('purpose', ['cover', 'content']);
            $t->unsignedSmallInteger('position')->default(0);
            $t->string('alt_text', 250);
            $t->string('caption', 300)->nullable();
            $t->string('credit', 200);
            // At most one cover per revision, enforced by the database.
            $t->unsignedBigInteger('cover_revision_id')->nullable()->storedAs("case when purpose = 'cover' then publication_revision_id else null end");
            $t->unique('cover_revision_id');
            $t->unique(['publication_revision_id', 'media_asset_id', 'purpose'], 'revision_media_unique_use');
            $t->index(['publication_revision_id', 'purpose', 'position', 'id'], 'revision_media_order');
            $t->index(['media_asset_id', 'publication_revision_id']);
            $t->timestamps();
        });
        DB::statement('ALTER TABLE revision_media ADD CONSTRAINT revision_media_text_check CHECK (CHAR_LENGTH(TRIM(alt_text)) > 0 AND CHAR_LENGTH(TRIM(credit)) > 0)');

        // Media operations without a publication (upload, block, deletion) are audited too.
        Schema::table('audit_entries', function (Blueprint $t) {
            $t->foreignId('publication_id')->nullable()->change();
            $t->foreignId('media_asset_id')->nullable()->after('publication_revision_id')->constrained()->nullOnDelete();
            $t->index(['media_asset_id', 'created_at', 'id']);
        });
        DB::statement('ALTER TABLE audit_entries ADD CONSTRAINT audit_subject_check CHECK (publication_id IS NOT NULL OR action LIKE \'media\\\\_%\')');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE audit_entries DROP CONSTRAINT audit_subject_check');
        Schema::table('audit_entries', function (Blueprint $t) {
            $t->dropIndex(['media_asset_id', 'created_at', 'id']);
            $t->dropConstrainedForeignId('media_asset_id');
        });
        Schema::dropIfExists('revision_media');
        Schema::dropIfExists('media_assets');
    }
};

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
            throw new RuntimeException('Editorial migrations require the dedicated MariaDB databases.');
        }
        foreach (['categories', 'tags'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name', 80);
                $t->string('slug', 100)->unique();
                $t->timestamps();
            });
        }
        Schema::create('publications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('municipality_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $t->enum('type', ['article', 'news', 'chronicle', 'causo', 'cultural_history', 'personal_story', 'migration_story', 'memory']);
            $t->enum('status', ['draft', 'in_review', 'scheduled', 'published', 'hidden', 'archived'])->default('draft');
            $t->enum('visibility', ['private', 'public'])->default('private');
            $t->unsignedBigInteger('published_revision_id')->nullable();
            $t->enum('published_revision_status', ['draft', 'in_review', 'approved', 'rejected'])->nullable();
            $t->unsignedBigInteger('scheduled_revision_id')->nullable();
            $t->enum('scheduled_revision_status', ['draft', 'in_review', 'approved', 'rejected'])->nullable();
            $t->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('scheduled_for')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamp('archived_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->index(['status', 'visibility', 'deleted_at', 'published_at', 'id'], 'publication_feed');
            $t->index(['municipality_id', 'status', 'visibility', 'deleted_at', 'published_at', 'id'], 'municipality_feed');
            $t->index(['scheduled_for', 'id']);
            $t->index(['author_id', 'created_at', 'id']);
        });
        Schema::create('publication_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('publication_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->enum('status', ['draft', 'in_review', 'approved', 'rejected'])->default('draft');
            $t->string('title', 200);
            $t->string('summary', 500)->nullable();
            $t->longText('body');
            $t->string('public_byline', 120)->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_note')->nullable();
            $t->timestamps();
            $t->unique(['publication_id', 'version']);
            $t->unique(['publication_id', 'id'], 'revision_owner');
            $t->unique(['publication_id', 'id', 'status'], 'revision_owner_status');
            $t->index(['publication_id', 'status', 'version']);
        });
        Schema::table('publications', function (Blueprint $t) {
            $t->foreign(['id', 'published_revision_id'], 'published_revision_owner_fk')
                ->references(['publication_id', 'id'])->on('publication_revisions')->restrictOnDelete();
            $t->foreign(['id', 'published_revision_id', 'published_revision_status'], 'published_approved_revision_fk')
                ->references(['publication_id', 'id', 'status'])->on('publication_revisions')->restrictOnDelete();
            $t->foreign(['id', 'scheduled_revision_id', 'scheduled_revision_status'], 'scheduled_approved_revision_fk')
                ->references(['publication_id', 'id', 'status'])->on('publication_revisions')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE publications ADD CONSTRAINT published_pointer_check CHECK ((published_revision_id IS NULL AND published_revision_status IS NULL) OR (published_revision_id IS NOT NULL AND published_revision_status IS NOT NULL AND published_revision_status = 'approved'))");
        DB::statement("ALTER TABLE publications ADD CONSTRAINT scheduled_pointer_check CHECK ((scheduled_revision_id IS NULL AND scheduled_revision_status IS NULL AND scheduled_for IS NULL) OR (scheduled_revision_id IS NOT NULL AND scheduled_revision_status IS NOT NULL AND scheduled_revision_status = 'approved' AND scheduled_for IS NOT NULL))");
        DB::statement("ALTER TABLE publications ADD CONSTRAINT published_state_check CHECK (status <> 'published' OR (published_revision_id IS NOT NULL AND published_at IS NOT NULL))");
        Schema::create('publication_slugs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('publication_id')->constrained()->restrictOnDelete();
            $t->string('slug', 180)->unique();
            $t->boolean('is_current')->default(true);
            $t->unsignedBigInteger('current_publication_id')->nullable()->storedAs('case when is_current = 1 then publication_id else null end');
            $t->unique('current_publication_id');
            $t->timestamp('retired_at')->nullable();
            $t->timestamps();
        });
        foreach (['tag', 'region'] as $relation) {
            Schema::create('publication_'.$relation, function (Blueprint $t) use ($relation) {
                $t->foreignId('publication_id')->constrained()->restrictOnDelete();
                $t->foreignId($relation.'_id')->constrained()->restrictOnDelete();
                $t->primary(['publication_id', $relation.'_id']);
                $t->index([$relation.'_id', 'publication_id']);
            });
        }
        Schema::create('revision_sources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('publication_revision_id')->constrained()->restrictOnDelete();
            $t->string('title', 200);
            $t->string('url', 2048)->nullable();
            $t->string('attribution', 250)->nullable();
            $t->date('accessed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('audit_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('publication_id')->constrained()->restrictOnDelete();
            $t->foreignId('publication_revision_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('action', 50);
            $t->json('changes')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['publication_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_entries');
        Schema::dropIfExists('revision_sources');
        Schema::dropIfExists('publication_region');
        Schema::dropIfExists('publication_tag');
        Schema::dropIfExists('publication_slugs');
        Schema::table('publications', function (Blueprint $t) {
            $t->dropForeign('published_revision_owner_fk');
            $t->dropForeign('published_approved_revision_fk');
            $t->dropForeign('scheduled_approved_revision_fk');
        });
        Schema::dropIfExists('publication_revisions');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};

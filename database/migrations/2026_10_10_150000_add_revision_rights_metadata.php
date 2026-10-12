<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publication_revisions', function (Blueprint $table) {
            $table->enum('rights_status', ['original', 'authorized', 'public_domain', 'licensed', 'unverified'])
                ->default('unverified')->after('public_byline');
            $table->string('rights_author', 200)->nullable()->after('rights_status');
            $table->string('rights_holder', 200)->nullable()->after('rights_author');
            $table->string('rights_basis', 500)->nullable()->after('rights_holder');
            $table->string('rights_reference', 2048)->nullable()->after('rights_basis');
            $table->text('rights_restrictions')->nullable()->after('rights_reference');
        });
    }

    public function down(): void
    {
        Schema::table('publication_revisions', function (Blueprint $table) {
            $table->dropColumn(['rights_status', 'rights_author', 'rights_holder', 'rights_basis', 'rights_reference', 'rights_restrictions']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 80);
            $table->string('native_name', 120);
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr');
            $table->boolean('is_enabled')->default(false)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_major')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(100);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('language_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('group', 64)->default('ui');
            $table->string('key', 190);
            $table->text('source_text');
            $table->text('translated_text')->nullable();
            $table->enum('status', ['missing', 'generated', 'reviewed'])->default('missing')->index();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(['language_id', 'group', 'key'], 'language_translation_identity');
            $table->index(['group', 'key']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 16)->nullable()->after('currency')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });

        Schema::dropIfExists('language_translations');
        Schema::dropIfExists('languages');
    }
};

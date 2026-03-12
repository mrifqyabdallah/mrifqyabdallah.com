<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('source_file')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('creator');
            $table->text('excerpt');
            $table->longText('content');
            $table->jsonb('tags');
            $table->string('status')->default('published');
            $table->date('published_at');
            $table->timestamps();

            $table->index('status');
            $table->index('published_at');
        });

        // full-text search across title, excerpt, content
        DB::statement("
            CREATE INDEX blogs_fulltext_idx ON blogs
            USING GIN (
                to_tsvector(
                    'english',
                    coalesce(title, '') || ' ' ||
                    coalesce(excerpt, '') || ' ' ||
                    coalesce(content, '')
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};

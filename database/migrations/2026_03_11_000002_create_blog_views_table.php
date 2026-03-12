<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash')->nullable();
            $table->date('date');
            $table->timestampTz('created_at')->useCurrent();

            // Unique daily view per authenticated user
            $table->unique(['blog_id', 'user_id', 'date'], 'unique_auth_view');

            // Unique daily view per guest session
            $table->unique(['blog_id', 'visitor_hash', 'date'], 'unique_guest_view');

            $table->index('blog_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_views');
    }
};

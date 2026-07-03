<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('original_url');
            $table->string('code')->unique();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamps();

            // Dashboard lists a user's links newest-first:
            // WHERE user_id = ? ORDER BY created_at DESC.
            // Composite index covers both the filter and the sort; its leftmost
            // column also serves plain user_id lookups (and the FK).
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};

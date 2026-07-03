<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            // Statistics screen lists a link's clicks newest-first:
            // WHERE link_id = ? ORDER BY created_at DESC.
            // Composite index covers filter + sort; leftmost column serves the FK.
            $table->index(['link_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};

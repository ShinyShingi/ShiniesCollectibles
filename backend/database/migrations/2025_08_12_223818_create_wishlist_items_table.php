<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->decimal('target_price', 10, 2)->nullable(); // Price alert threshold
            $table->text('notes')->nullable();
            $table->integer('priority')->default(1); // 1-5 priority
            $table->timestamps();
            
            $table->unique(['wishlist_id', 'item_id']);
            $table->index(['wishlist_id', 'priority']);
            $table->index(['item_id', 'target_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};

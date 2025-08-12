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
        Schema::create('price_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->string('source'); // 'abebooks', 'zvab', 'rebuy', etc.
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('isbn')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->storedAs('price + shipping_cost');
            $table->string('condition')->nullable();
            $table->string('seller_name')->nullable();
            $table->string('seller_location')->nullable();
            $table->text('description')->nullable();
            $table->string('url');
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_available')->default(true);
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['item_id', 'checked_at']);
            $table->index(['item_id', 'total_cost']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_checks');
    }
};

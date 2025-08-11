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
        Schema::create('item_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('contributor_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['author', 'artist', 'label', 'publisher', 'producer', 'composer', 'performer']);
            $table->timestamps();
            
            $table->unique(['item_id', 'contributor_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_contributors');
    }
};

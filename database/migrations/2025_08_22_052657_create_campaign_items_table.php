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
        Schema::create('campaign_items', function (Blueprint $table) {
            $table->id();
            $table->string('range', 100);
            $table->float('subtotal', 8, 2);
            $table->enum('provider_status', ['Accepted', 'Rejected'])->nullable();
            $table->float('price_per_day', 8, 2);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('campaign_id')->constrained();
            $table->foreignId('media_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_items');
    }
};

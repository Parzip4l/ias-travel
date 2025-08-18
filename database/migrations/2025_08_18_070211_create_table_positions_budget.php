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
        Schema::create('positions_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('budget_categories')->cascadeOnDelete();
            $table->string('type');
            $table->decimal('max_budget', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions_budgets');
    }
};

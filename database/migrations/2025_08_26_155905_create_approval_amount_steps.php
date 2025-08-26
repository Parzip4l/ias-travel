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
        Schema::create('approval_amount_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_amount_flow_id');
            $table->integer('step_order');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->foreign('approval_amount_flow_id')->references('id')->on('approval_amount_flows')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_amount_steps');
    }
};

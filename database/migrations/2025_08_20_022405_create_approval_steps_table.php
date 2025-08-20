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
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')->constrained()->cascadeOnDelete();

            $table->integer('step_order'); // urutan step

            // Approval bisa berdasarkan user spesifik
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();

            // Approval bisa berdasarkan divisi
            $table->foreignId('division_id')->nullable()->constrained()->cascadeOnDelete();

            // Approval bisa berdasarkan jabatan (position)
            $table->foreignId('position_id')->nullable()->constrained()->cascadeOnDelete();

            // Kalau mau tetap ada "role" generik (misal admin, finance, director global)
            $table->string('role')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};

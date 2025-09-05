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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppd_id')->constrained('sppds')->onDelete('cascade'); 
            $table->string('external_id')->unique();  
            $table->string('invoice_id')->nullable();
            $table->decimal('amount', 15, 0);
            $table->string('status')->default('PENDING');
            $table->string('payer_email')->nullable();
            $table->string('invoice_url')->nullable();
            $table->json('raw_response')->nullable(); 
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

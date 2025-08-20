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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignid('company_type_id')->nullable();
            $table->foreign('company_type_id')->references('id')->on('company_types');
            $table->string('customer_id')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('image')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('zipcode')->nullable();
            $table->boolean('is_pkp')->default(false);
            $table->string('npwp_number')->nullable();
            $table->string('npwp_file')->nullable();
            $table->string('sppkp_file')->nullable();
            $table->string('skt_file')->nullable();
            $table->enum('environment', ['sandbox', 'live'])->default('sandbox');
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

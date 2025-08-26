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
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable()->after('place_of_birth');
            $table->string('national_id')->nullable()->after('marital_status');
            $table->string('tax_number')->nullable()->after('national_id');
            $table->string('phone_number')->nullable()->after('tax_number');
            $table->text('address')->nullable()->after('phone_number');
            $table->string('kontak_darurat')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            //
        });
    }
};

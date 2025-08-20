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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Relasi ke company
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Relasi ke user (opsional, kalau karyawan punya akun login)
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            // Nomor karyawan unik per company (bukan global unik)
            $table->string('employee_number'); // NIP / ID Karyawan
            $table->unique(['company_id', 'employee_number']); // Kombinasi unik per company

            // Informasi karyawan
            $table->string('name'); // nama lengkap
            $table->foreignId('division_id')->nullable()->constrained('divisions')->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->cascadeOnDelete();
            $table->date('join_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('employment_status', ['permanent', 'contract', 'intern'])->default('permanent'); 
            $table->string('grade_level')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

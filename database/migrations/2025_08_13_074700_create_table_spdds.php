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
        Schema::create('sppds', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sppd', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('tujuan');
            $table->string('lokasi_tujuan', 255);
            $table->date('tanggal_berangkat');
            $table->date('tanggal_pulang');
            $table->string('transportasi', 100)->nullable();
            $table->decimal('biaya_estimasi', 15, 2)->default(0);
            $table->decimal('biaya_realisasi', 15, 2)->nullable();
            $table->enum('status', ['Draft', 'Pending', 'Approved', 'Rejected', 'Completed'])->default('Draft');
            $table->timestamps();
        });

        // 2. Tabel sppd_approvals
        Schema::create('sppd_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppd_id')->constrained('sppds')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->string('role', 50);
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->text('catatan')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // 3. Tabel sppd_files
        Schema::create('sppd_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppd_id')->constrained('sppds')->onDelete('cascade');
            $table->string('jenis_file', 50);
            $table->text('file_path');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        // 4. Tabel sppd_histories
        Schema::create('sppd_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppd_id')->constrained('sppds')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status_awal', ['Draft', 'Pending', 'Approved', 'Rejected', 'Completed'])->nullable();
            $table->enum('status_akhir', ['Draft', 'Pending', 'Approved', 'Rejected', 'Completed']);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // 5. Tabel sppd_expenses
        Schema::create('sppd_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppd_id')->constrained('sppds')->onDelete('cascade');
            $table->string('kategori', 50);
            $table->text('deskripsi')->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->text('bukti_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sppd_expenses');
        Schema::dropIfExists('sppd_histories');
        Schema::dropIfExists('sppd_files');
        Schema::dropIfExists('sppd_approvals');
        Schema::dropIfExists('sppds');
    }
};

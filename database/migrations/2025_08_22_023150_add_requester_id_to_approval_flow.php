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
        Schema::table('approval_flows', function (Blueprint $table) {
            $table->unsignedBigInteger('requester_position_id')->nullable()->after('company_id');

            // Jika posisi disimpan di tabel positions
            $table->foreign('requester_position_id')
                ->references('id')
                ->on('positions')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_flows', function (Blueprint $table) {
            $table->dropForeign(['requester_position_id']);
            $table->dropColumn('requester_position_id');
        });
    }
};

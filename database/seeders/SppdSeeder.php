<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\Sppd\Model\Sppd;
use App\Services\Sppd\Model\SppdApproval;
use App\Services\Sppd\Model\SppdFile;
use App\Services\Sppd\Model\SppdHistory;
use App\Services\Sppd\Model\SppdExpense;
use Illuminate\Support\Facades\DB;

class SppdSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil 3 user random sebagai contoh
        $users = User::limit(3)->get();

        foreach ($users as $user) {

            // 1. Buat SPPD
            $sppd = Sppd::create([
                'nomor_sppd' => 'SPPD-' . Str::upper(Str::random(6)),
                'user_id' => $user->id,
                'tujuan' => 'Kunjungan kantor cabang',
                'lokasi_tujuan' => 'Jakarta',
                'tanggal_berangkat' => now()->addDays(2)->toDateString(),
                'tanggal_pulang' => now()->addDays(5)->toDateString(),
                'transportasi' => 'Pesawat',
                'biaya_estimasi' => 3000000,
                'status' => 'Pending'
            ]);

            // 2. Buat Approval dummy (Kadiv + Finance)
            $approvers = User::inRandomOrder()->limit(2)->get();
            foreach ($approvers as $approver) {
                SppdApproval::create([
                    'sppd_id' => $sppd->id,
                    'approver_id' => $approver->id,
                    'role' => $approver->id % 2 == 0 ? 'Kadiv' : 'Finance',
                    'status' => 'Pending',
                    'catatan' => null
                ]);
            }

            // 3. Buat File dummy (Tiket Pesawat & Hotel)
            SppdFile::create([
                'sppd_id' => $sppd->id,
                'jenis_file' => 'Tiket Pesawat',
                'file_path' => 'storage/tiket_pesawat_dummy.pdf',
                'uploaded_by' => $user->id,
                'uploaded_at' => now()
            ]);

            SppdFile::create([
                'sppd_id' => $sppd->id,
                'jenis_file' => 'Tiket Hotel',
                'file_path' => 'storage/tiket_hotel_dummy.pdf',
                'uploaded_by' => $user->id,
                'uploaded_at' => now()
            ]);

            // 4. Buat History
            SppdHistory::create([
                'sppd_id' => $sppd->id,
                'user_id' => $user->id,
                'status_awal' => 'Draft',
                'status_akhir' => 'Pending',
                'catatan' => 'Pengajuan awal'
            ]);

            // 5. Buat Expenses
            SppdExpense::create([
                'sppd_id' => $sppd->id,
                'kategori' => 'Transportasi',
                'deskripsi' => 'Tiket pesawat pergi-pulang',
                'jumlah' => 1500000,
                'bukti_file' => 'storage/tiket_pesawat_dummy.pdf'
            ]);

            SppdExpense::create([
                'sppd_id' => $sppd->id,
                'kategori' => 'Akomodasi',
                'deskripsi' => 'Hotel selama 3 malam',
                'jumlah' => 1500000,
                'bukti_file' => 'storage/tiket_hotel_dummy.pdf'
            ]);
        }
    }
}

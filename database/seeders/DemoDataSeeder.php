<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Booking\Model\District;
use App\Services\Booking\Model\Province;
use App\Services\Booking\Model\Regency;
use App\Services\Booking\Model\Village;
use App\Services\Company\Model\Company;
use App\Services\Company\Model\CompanyType;
use App\Services\Employee\Model\Employee;
use App\Services\Payment\Model\Payment;
use App\Services\Reimbursement\Model\Reimbursement;
use App\Services\Reimbursement\Model\ReimbursementApproval;
use App\Services\Reimbursement\Model\ReimbursementCategory;
use App\Services\Reimbursement\Model\ReimbursementFile;
use App\Services\Sppd\Model\ApprovalFlow;
use App\Services\Sppd\Model\ApprovalStep;
use App\Services\Sppd\Model\Sppd;
use App\Services\Sppd\Model\SppdApproval;
use App\Services\Sppd\Model\SppdExpense;
use App\Services\Sppd\Model\SppdFile;
use App\Services\Sppd\Model\SppdHistory;
use App\Services\Sppd\Model\SppdWilayah;
use App\Services\User\Model\BudgetCategory;
use App\Services\User\Model\Departement;
use App\Services\User\Model\Permission;
use App\Services\User\Model\Position;
use App\Services\User\Model\PositionsBudget;
use App\Services\User\Model\Role;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Province::count() === 0) {
            if (class_exists(\AzisHapidin\IndoRegion\RawDataGetter::class)) {
                $this->call(IndoRegionSeeder::class);
            } else {
                $this->seedFallbackRegions();
            }
        }

        DB::transaction(function () {
            $this->seedRolesAndPermissions();

            $companyTypes = $this->seedCompanyTypes();
            $companies = $this->seedCompanies($companyTypes);
            $divisions = $this->seedDivisions($companies);
            $positions = $this->seedPositions($companies);
            $budgetCategories = $this->seedBudgetCategories();
            $this->seedPositionBudgets($positions, $budgetCategories);
            $users = $this->seedUsers($companies, $divisions);
            $this->assignDivisionHeads($divisions, $users);
            $this->seedEmployees($users, $companies, $divisions, $positions);
            $this->seedApprovalFlow($companies, $positions, $divisions, $users);
            $reimbursementCategories = $this->seedReimbursementCategories($companies);
            $this->seedOperationalData($users, $reimbursementCategories);
        });
    }

    private function seedRolesAndPermissions(): void
    {
        foreach (['admin', 'finance', 'user'] as $roleName) {
            Role::query()->updateOrCreate(['name' => $roleName], ['name' => $roleName]);
        }

        $permissions = [
            ['module' => 'dashboard', 'action' => 'read'],
            ['module' => 'sppd', 'action' => 'write'],
            ['module' => 'finance', 'action' => 'approve'],
            ['module' => 'reimbursement', 'action' => 'write'],
            ['module' => 'company', 'action' => 'manage'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::query()->updateOrCreate($permissionData, $permissionData);
        }
    }

    private function seedFallbackRegions(): void
    {
        $regions = [
            [
                'province' => ['id' => '31', 'name' => 'DKI JAKARTA'],
                'regency' => ['id' => '3171', 'name' => 'JAKARTA PUSAT'],
                'district' => ['id' => '3171010', 'name' => 'MENTENG'],
                'village' => ['id' => '3171010001', 'name' => 'MENTENG'],
            ],
            [
                'province' => ['id' => '32', 'name' => 'JAWA BARAT'],
                'regency' => ['id' => '3273', 'name' => 'KOTA BANDUNG'],
                'district' => ['id' => '3273010', 'name' => 'ANDIR'],
                'village' => ['id' => '3273010001', 'name' => 'CAMPAKA'],
            ],
            [
                'province' => ['id' => '33', 'name' => 'JAWA TENGAH'],
                'regency' => ['id' => '3374', 'name' => 'KOTA SEMARANG'],
                'district' => ['id' => '3374010', 'name' => 'SEMARANG TENGAH'],
                'village' => ['id' => '3374010001', 'name' => 'KRANGGAN'],
            ],
            [
                'province' => ['id' => '35', 'name' => 'JAWA TIMUR'],
                'regency' => ['id' => '3578', 'name' => 'KOTA SURABAYA'],
                'district' => ['id' => '3578010', 'name' => 'TEGALSARI'],
                'village' => ['id' => '3578010001', 'name' => 'KEDUNGDORO'],
            ],
            [
                'province' => ['id' => '51', 'name' => 'BALI'],
                'regency' => ['id' => '5171', 'name' => 'KOTA DENPASAR'],
                'district' => ['id' => '5171010', 'name' => 'DENPASAR SELATAN'],
                'village' => ['id' => '5171010001', 'name' => 'SANUR KAJA'],
            ],
        ];

        foreach ($regions as $region) {
            Province::query()->updateOrCreate(
                ['id' => $region['province']['id']],
                ['name' => $region['province']['name']]
            );

            Regency::query()->updateOrCreate(
                ['id' => $region['regency']['id']],
                [
                    'province_id' => $region['province']['id'],
                    'name' => $region['regency']['name'],
                ]
            );

            District::query()->updateOrCreate(
                ['id' => $region['district']['id']],
                [
                    'regency_id' => $region['regency']['id'],
                    'name' => $region['district']['name'],
                ]
            );

            Village::query()->updateOrCreate(
                ['id' => $region['village']['id']],
                [
                    'district_id' => $region['district']['id'],
                    'name' => $region['village']['name'],
                ]
            );
        }
    }

    private function seedCompanyTypes(): array
    {
        return [
            'holding' => CompanyType::query()->updateOrCreate(
                ['name' => 'Holding'],
                ['description' => 'Perusahaan induk perjalanan dinas', 'is_active' => true]
            ),
            'subsidiary' => CompanyType::query()->updateOrCreate(
                ['name' => 'Subsidiary'],
                ['description' => 'Perusahaan operasional cabang', 'is_active' => true]
            ),
        ];
    }

    private function seedCompanies(array $companyTypes): array
    {
        return [
            'ias' => Company::query()->updateOrCreate(
                ['customer_id' => 'CUST-IAS-001'],
                [
                    'name' => 'IAS Travel Corporate',
                    'email' => 'corporate@ias-travel.test',
                    'company_type_id' => $companyTypes['holding']->id,
                    'phone' => '0215550101',
                    'address' => 'Jakarta Pusat',
                    'zipcode' => '10110',
                    'environment' => 'sandbox',
                    'is_active' => true,
                    'verified_at' => now(),
                ]
            ),
            'ops' => Company::query()->updateOrCreate(
                ['customer_id' => 'CUST-IAS-OPS-001'],
                [
                    'name' => 'IAS Travel Operations',
                    'email' => 'ops@ias-travel.test',
                    'company_type_id' => $companyTypes['subsidiary']->id,
                    'phone' => '0215550102',
                    'address' => 'Bandung',
                    'zipcode' => '40111',
                    'environment' => 'sandbox',
                    'is_active' => true,
                    'verified_at' => now(),
                ]
            ),
        ];
    }

    private function seedDivisions(array $companies): array
    {
        return [
            'finance' => Departement::query()->updateOrCreate(
                ['name' => 'Finance'],
                ['company_id' => $companies['ias']->id]
            ),
            'operations' => Departement::query()->updateOrCreate(
                ['name' => 'Operations'],
                ['company_id' => $companies['ops']->id]
            ),
            'hr' => Departement::query()->updateOrCreate(
                ['name' => 'Human Capital'],
                ['company_id' => $companies['ias']->id]
            ),
        ];
    }

    private function seedPositions(array $companies): array
    {
        return [
            'director' => Position::query()->updateOrCreate(
                ['name' => 'Director'],
                ['company_id' => $companies['ias']->id]
            ),
            'finance_manager' => Position::query()->updateOrCreate(
                ['name' => 'Finance Manager'],
                ['company_id' => $companies['ias']->id]
            ),
            'ops_manager' => Position::query()->updateOrCreate(
                ['name' => 'Operations Manager'],
                ['company_id' => $companies['ops']->id]
            ),
            'staff' => Position::query()->updateOrCreate(
                ['name' => 'Staff'],
                ['company_id' => $companies['ops']->id]
            ),
        ];
    }

    private function seedBudgetCategories(): array
    {
        return [
            'transport' => BudgetCategory::query()->updateOrCreate(['name' => 'Transportasi']),
            'lodging' => BudgetCategory::query()->updateOrCreate(['name' => 'Akomodasi']),
            'meal' => BudgetCategory::query()->updateOrCreate(['name' => 'Konsumsi']),
        ];
    }

    private function seedPositionBudgets(array $positions, array $categories): void
    {
        $budgets = [
            [$positions['staff']->id, $categories['transport']->id, 'per_trip', 2500000],
            [$positions['staff']->id, $categories['lodging']->id, 'per_trip', 1800000],
            [$positions['ops_manager']->id, $categories['transport']->id, 'per_trip', 4000000],
            [$positions['ops_manager']->id, $categories['lodging']->id, 'per_trip', 2500000],
            [$positions['finance_manager']->id, $categories['meal']->id, 'per_trip', 800000],
        ];

        foreach ($budgets as [$positionId, $categoryId, $type, $maxBudget]) {
            PositionsBudget::query()->updateOrCreate(
                [
                    'position_id' => $positionId,
                    'category_id' => $categoryId,
                ],
                [
                    'type' => $type,
                    'max_budget' => $maxBudget,
                ]
            );
        }
    }

    private function seedUsers(array $companies, array $divisions): array
    {
        return [
            'superadmin' => $this->upsertUser([
                'name' => 'Super Admin',
                'email' => 'superadmin@ias-travel.test',
                'role' => 'admin',
                'password' => 'superadmin123',
                'company_id' => $companies['ias']->id,
                'divisi_id' => $divisions['finance']->id,
            ]),
            'finance' => $this->upsertUser([
                'name' => 'Fiona Finance',
                'email' => 'finance@ias-travel.test',
                'role' => 'finance',
                'password' => 'finance123',
                'company_id' => $companies['ias']->id,
                'divisi_id' => $divisions['finance']->id,
            ]),
            'ops_manager' => $this->upsertUser([
                'name' => 'Oscar Operations',
                'email' => 'ops.manager@ias-travel.test',
                'role' => 'admin',
                'password' => 'manager123',
                'company_id' => $companies['ops']->id,
                'divisi_id' => $divisions['operations']->id,
            ]),
            'staff_1' => $this->upsertUser([
                'name' => 'Alya Staff',
                'email' => 'alya@ias-travel.test',
                'role' => 'user',
                'password' => 'user12345',
                'company_id' => $companies['ops']->id,
                'divisi_id' => $divisions['operations']->id,
            ]),
            'staff_2' => $this->upsertUser([
                'name' => 'Bima Staff',
                'email' => 'bima@ias-travel.test',
                'role' => 'user',
                'password' => 'user12345',
                'company_id' => $companies['ops']->id,
                'divisi_id' => $divisions['operations']->id,
            ]),
            'staff_3' => $this->upsertUser([
                'name' => 'Citra Staff',
                'email' => 'citra@ias-travel.test',
                'role' => 'user',
                'password' => 'user12345',
                'company_id' => $companies['ias']->id,
                'divisi_id' => $divisions['hr']->id,
            ]),
            'demo_user' => $this->upsertUser([
                'name' => 'User Demo',
                'email' => 'user@ias-travel.test',
                'role' => 'user',
                'password' => 'user12345',
                'company_id' => $companies['ops']->id,
                'divisi_id' => $divisions['operations']->id,
            ]),
        ];
    }

    private function assignDivisionHeads(array $divisions, array $users): void
    {
        $divisions['finance']->head_id = $users['finance']->id;
        $divisions['finance']->save();

        $divisions['operations']->head_id = $users['ops_manager']->id;
        $divisions['operations']->save();

        $divisions['hr']->head_id = $users['superadmin']->id;
        $divisions['hr']->save();
    }

    private function seedEmployees(array $users, array $companies, array $divisions, array $positions): void
    {
        $employees = [
            [$users['superadmin'], $companies['ias'], $divisions['finance'], $positions['director'], 'EMP-001'],
            [$users['finance'], $companies['ias'], $divisions['finance'], $positions['finance_manager'], 'EMP-002'],
            [$users['ops_manager'], $companies['ops'], $divisions['operations'], $positions['ops_manager'], 'EMP-003'],
            [$users['staff_1'], $companies['ops'], $divisions['operations'], $positions['staff'], 'EMP-004'],
            [$users['staff_2'], $companies['ops'], $divisions['operations'], $positions['staff'], 'EMP-005'],
            [$users['staff_3'], $companies['ias'], $divisions['hr'], $positions['staff'], 'EMP-006'],
            [$users['demo_user'], $companies['ops'], $divisions['operations'], $positions['staff'], 'EMP-007'],
        ];

        foreach ($employees as [$user, $company, $division, $position, $employeeNumber]) {
            Employee::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_number' => $employeeNumber,
                ],
                [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'division_id' => $division->id,
                    'position_id' => $position->id,
                    'join_date' => now()->subYears(2)->toDateString(),
                    'employment_status' => 'permanent',
                    'grade_level' => 'G' . $position->id,
                    'gender' => 'male',
                    'date_of_birth' => '1995-01-01',
                    'place_of_birth' => 'Jakarta',
                    'marital_status' => 'single',
                    'national_id' => '3174' . str_pad((string) $user->id, 12, '0', STR_PAD_LEFT),
                    'tax_number' => '09.' . str_pad((string) $user->id, 3, '0', STR_PAD_LEFT) . '.111.8-000.000',
                    'phone_number' => '08123' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
                    'address' => 'Alamat demo untuk ' . $user->name,
                    'kontak_darurat' => 'Kontak keluarga ' . $user->name,
                ]
            );
        }
    }

    private function seedApprovalFlow(array $companies, array $positions, array $divisions, array $users): void
    {
        $flow = ApprovalFlow::query()->updateOrCreate(
            [
                'company_id' => $companies['ops']->id,
                'name' => 'SPPD Operations Approval',
            ],
            [
                'requester_position_id' => $positions['staff']->id,
                'is_active' => true,
                'approval_type' => 'hierarchy',
            ]
        );

        ApprovalStep::query()->updateOrCreate(
            [
                'approval_flow_id' => $flow->id,
                'step_order' => 1,
            ],
            [
                'user_id' => $users['ops_manager']->id,
                'division_id' => $divisions['operations']->id,
                'position_id' => $positions['ops_manager']->id,
                'role' => 'Ops Manager',
                'is_final' => false,
            ]
        );

        ApprovalStep::query()->updateOrCreate(
            [
                'approval_flow_id' => $flow->id,
                'step_order' => 2,
            ],
            [
                'user_id' => $users['finance']->id,
                'division_id' => $divisions['finance']->id,
                'position_id' => $positions['finance_manager']->id,
                'role' => 'Finance',
                'is_final' => true,
            ]
        );
    }

    private function seedReimbursementCategories(array $companies): array
    {
        return [
            'transport' => ReimbursementCategory::query()->updateOrCreate(
                ['code' => 'TRN'],
                ['name' => 'Transport Lokal', 'company_id' => $companies['ops']->id]
            ),
            'meal' => ReimbursementCategory::query()->updateOrCreate(
                ['code' => 'MLS'],
                ['name' => 'Makan Perjalanan', 'company_id' => $companies['ops']->id]
            ),
        ];
    }

    private function seedOperationalData(array $users, array $reimbursementCategories): void
    {
        $regionCombos = $this->regionCombos();
        $sppdBlueprints = [
            ['SPPD-DEMO-001', $users['staff_1'], $regionCombos[0], now()->subDays(3), now()->addDays(1), 'Pending', 1850000],
            ['SPPD-DEMO-002', $users['staff_2'], $regionCombos[1], now()->subDays(6), now()->subDays(2), 'Approved', 2750000],
            ['SPPD-DEMO-003', $users['demo_user'], $regionCombos[2], now()->subDays(9), now()->subDays(4), 'Rejected', 2100000],
            ['SPPD-DEMO-004', $users['staff_3'], $regionCombos[3], now()->subDays(12), now()->subDays(6), 'Completed', 3200000],
            ['SPPD-DEMO-005', $users['staff_1'], $regionCombos[4], now()->subMonth()->addDays(2), now()->subMonth()->addDays(5), 'Approved', 2450000],
            ['SPPD-DEMO-006', $users['staff_2'], $regionCombos[0], now()->subMonths(2)->addDays(3), now()->subMonths(2)->addDays(8), 'Completed', 4100000],
            ['SPPD-DEMO-007', $users['demo_user'], $regionCombos[1], now()->subMonths(3)->addDays(4), now()->subMonths(3)->addDays(7), 'Approved', 2950000],
            ['SPPD-DEMO-008', $users['staff_3'], $regionCombos[2], now()->subMonths(4)->addDays(6), now()->subMonths(4)->addDays(10), 'Rejected', 1800000],
            ['SPPD-DEMO-009', $users['staff_1'], $regionCombos[3], now()->subMonths(5)->addDays(1), now()->subMonths(5)->addDays(4), 'Completed', 3600000],
            ['SPPD-DEMO-010', $users['staff_2'], $regionCombos[4], now()->subMonths(6)->addDays(2), now()->subMonths(6)->addDays(6), 'Approved', 2800000],
        ];

        foreach ($sppdBlueprints as $index => [$number, $user, $region, $departureDate, $returnDate, $status, $estimatedCost]) {
            $createdAt = Carbon::parse($departureDate)->subDays(7);
            $sppd = Sppd::query()->firstOrNew(['nomor_sppd' => $number]);
            $sppd->forceFill([
                'user_id' => $user->id,
                'tujuan' => 'Perjalanan dinas ke ' . $region['province']->name,
                'lokasi_tujuan' => $region['regency']->name,
                'tanggal_berangkat' => Carbon::parse($departureDate)->toDateString(),
                'tanggal_pulang' => Carbon::parse($returnDate)->toDateString(),
                'transportasi' => $index % 2 === 0 ? 'Pesawat' : 'Kereta',
                'biaya_estimasi' => $estimatedCost,
                'biaya_realisasi' => in_array($status, ['Approved', 'Completed'], true) ? $estimatedCost - 150000 : null,
                'status' => $status,
                'keperluan' => 'Kunjungan kerja dan koordinasi cabang',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $sppd->save();

            $this->seedSppdRelations($sppd, $user, $region, $status, $estimatedCost, $createdAt);

            if (in_array($status, ['Approved', 'Completed'], true)) {
                $this->seedPayment($sppd, $user, $status, $estimatedCost, $createdAt, $index);
            }

            if ($status === 'Completed') {
                $this->seedReimbursement($sppd, $user, $reimbursementCategories, $createdAt, $index);
            }
        }
    }

    private function seedSppdRelations(Sppd $sppd, User $requester, array $region, string $status, int $estimatedCost, Carbon $createdAt): void
    {
        SppdWilayah::query()->updateOrCreate(
            ['sppd_id' => $sppd->id],
            [
                'province_id' => (int) $region['province']->id,
                'regency_id' => (int) $region['regency']->id,
                'district_id' => (int) $region['district']->id,
                'village_id' => (int) $region['village']->id,
                'full_address' => 'Alamat tujuan demo ' . $region['regency']->name,
                'latitude' => -6.2 + ($sppd->id / 100),
                'longitude' => 106.8 + ($sppd->id / 100),
            ]
        );

        SppdExpense::query()->updateOrCreate(
            ['sppd_id' => $sppd->id, 'kategori' => 'Transportasi'],
            [
                'deskripsi' => 'Tiket perjalanan pulang pergi',
                'jumlah' => round($estimatedCost * 0.6, 2),
                'bukti_file' => 'storage/demo/transport.pdf',
            ]
        );

        SppdExpense::query()->updateOrCreate(
            ['sppd_id' => $sppd->id, 'kategori' => 'Akomodasi'],
            [
                'deskripsi' => 'Hotel dan kebutuhan menginap',
                'jumlah' => round($estimatedCost * 0.4, 2),
                'bukti_file' => 'storage/demo/hotel.pdf',
            ]
        );

        SppdFile::query()->updateOrCreate(
            ['sppd_id' => $sppd->id, 'jenis_file' => 'Surat Tugas'],
            [
                'file_path' => 'storage/demo/surat_tugas_' . strtolower($sppd->nomor_sppd) . '.pdf',
                'uploaded_by' => $requester->id,
                'uploaded_at' => $createdAt,
            ]
        );

        $pendingHistory = SppdHistory::query()->firstOrNew([
            'sppd_id' => $sppd->id,
            'status_akhir' => 'Pending',
        ]);
        $pendingHistory->forceFill([
            'user_id' => $requester->id,
            'status_awal' => 'Draft',
            'catatan' => 'Pengajuan awal perjalanan dinas',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        if ($status !== 'Pending') {
            $finalHistory = SppdHistory::query()->firstOrNew([
                'sppd_id' => $sppd->id,
                'status_akhir' => $status,
            ]);
            $finalHistory->forceFill([
                'user_id' => $requester->id,
                'status_awal' => 'Pending',
                'catatan' => 'Status diperbarui menjadi ' . $status,
                'created_at' => $createdAt->copy()->addDays(1),
                'updated_at' => $createdAt->copy()->addDays(1),
            ])->save();
        }

        $opsManager = User::query()->where('email', 'ops.manager@ias-travel.test')->first();
        $finance = User::query()->where('email', 'finance@ias-travel.test')->first();

        $firstApprovalStatus = $status === 'Rejected' ? 'Rejected' : ($status === 'Pending' ? 'Pending' : 'Approved');
        $secondApprovalStatus = $status === 'Pending' || $status === 'Rejected' ? 'Pending' : 'Approved';

        if ($opsManager) {
            SppdApproval::query()->updateOrCreate(
                ['sppd_id' => $sppd->id, 'approver_id' => $opsManager->id],
                [
                    'role' => 'Ops Manager',
                    'status' => $firstApprovalStatus,
                    'catatan' => $firstApprovalStatus === 'Rejected' ? 'Perlu revisi jadwal dan biaya.' : 'Sudah ditinjau.',
                    'approved_at' => $firstApprovalStatus === 'Pending' ? null : $createdAt->copy()->addDay(),
                ]
            );
        }

        if ($finance) {
            SppdApproval::query()->updateOrCreate(
                ['sppd_id' => $sppd->id, 'approver_id' => $finance->id],
                [
                    'role' => 'Finance',
                    'status' => $secondApprovalStatus,
                    'catatan' => $secondApprovalStatus === 'Approved' ? 'Budget tersedia.' : null,
                    'approved_at' => $secondApprovalStatus === 'Approved' ? $createdAt->copy()->addDays(2) : null,
                ]
            );
        }
    }

    private function seedPayment(Sppd $sppd, User $requester, string $status, int $estimatedCost, Carbon $createdAt, int $index): void
    {
        $paymentStatus = match (true) {
            $status === 'Completed' => 'PAID',
            $index % 3 === 0 => 'WAITING_INVOICE',
            default => 'PAID',
        };

        $payment = Payment::query()->firstOrNew([
            'external_id' => 'PAY-' . $sppd->nomor_sppd,
        ]);
        $payment->forceFill([
            'sppd_id' => $sppd->id,
            'invoice_id' => 'INV-' . $sppd->nomor_sppd,
            'amount' => $estimatedCost,
            'status' => $paymentStatus,
            'payer_email' => $requester->email,
            'invoice_url' => 'https://example.test/invoices/' . strtolower($sppd->nomor_sppd),
            'raw_response' => ['source' => 'demo-seeder', 'status' => $paymentStatus],
            'payment_type' => $index % 2 === 0 ? 'digital' : 'invoicing',
            'created_at' => $createdAt->copy()->addDays(2),
            'updated_at' => $createdAt->copy()->addDays(2),
        ])->save();
    }

    private function seedReimbursement(Sppd $sppd, User $requester, array $categories, Carbon $createdAt, int $index): void
    {
        $category = $index % 2 === 0 ? $categories['transport'] : $categories['meal'];
        $status = $index % 3 === 0 ? 'PAID' : 'APPROVED';

        $reimbursement = Reimbursement::query()->firstOrNew([
            'sppd_id' => $sppd->id,
            'title' => 'Reimbursement ' . $sppd->nomor_sppd,
        ]);
        $reimbursement->forceFill([
            'category_id' => $category->id,
            'user_id' => $requester->id,
            'description' => 'Klaim reimbursement untuk perjalanan ' . $sppd->nomor_sppd,
            'amount' => 350000 + ($index * 25000),
            'status' => $status,
            'notes' => 'Data demo reimbursement',
            'created_at' => $createdAt->copy()->addDays(4),
            'updated_at' => $createdAt->copy()->addDays(4),
        ])->save();

        $finance = User::query()->where('email', 'finance@ias-travel.test')->first();
        if ($finance) {
            $approval = ReimbursementApproval::query()->firstOrNew([
                'reimbursement_id' => $reimbursement->id,
                'approved_by' => $finance->id,
            ]);
            $approval->forceFill([
                'status' => 'APPROVED',
                'notes' => 'Disetujui finance',
                'created_at' => $createdAt->copy()->addDays(5),
                'updated_at' => $createdAt->copy()->addDays(5),
            ])->save();
        }

        $file = ReimbursementFile::query()->firstOrNew([
            'reimbursement_id' => $reimbursement->id,
            'file_path' => 'storage/demo/reimbursement_' . strtolower($sppd->nomor_sppd) . '.pdf',
        ]);
        $file->forceFill([
            'file_type' => 'application/pdf',
            'uploaded_by' => $requester->id,
            'created_at' => $createdAt->copy()->addDays(4),
            'updated_at' => $createdAt->copy()->addDays(4),
        ])->save();
    }

    private function upsertUser(array $attributes): User
    {
        $user = User::query()->firstOrNew(['email' => $attributes['email']]);
        $user->forceFill([
            'name' => $attributes['name'],
            'role' => $attributes['role'],
            'password' => Hash::make($attributes['password']),
            'company_id' => $attributes['company_id'] ?? null,
            'divisi_id' => $attributes['divisi_id'] ?? null,
            'email_verified_at' => now(),
        ])->save();

        return $user->fresh();
    }

    private function regionCombos(): array
    {
        $provinceIds = ['31', '32', '33', '35', '51'];
        $combos = [];

        foreach ($provinceIds as $provinceId) {
            $province = Province::query()->find($provinceId) ?? Province::query()->first();
            if (!$province) {
                continue;
            }

            $regency = Regency::query()->where('province_id', $province->id)->first();
            $district = $regency ? District::query()->where('regency_id', $regency->id)->first() : null;
            $village = $district ? Village::query()->where('district_id', $district->id)->first() : null;

            if ($province && $regency && $district && $village) {
                $combos[] = compact('province', 'regency', 'district', 'village');
            }
        }

        if ($combos === []) {
            throw new \RuntimeException('Data wilayah tidak tersedia untuk demo seeder.');
        }

        return $combos;
    }
}

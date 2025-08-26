<?php

namespace App\Services\Company\Imports;

use App\Services\Company\Model\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CompanyImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            // hanya proses sheet pertama
            0 => new CompanySheetImport(),
        ];
    }
}

class CompanySheetImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // skip header
        $rows->skip(1)->each(function ($row) {
            if (!isset($row[0]) || empty($row[0])) {
                return; // skip baris kosong
            }

            Company::create([
                'name'            => $row[0] ?? null,
                'customer_id'     => $row[1] ?? null,
                'email'           => $row[2] ?? null,
                'company_type_id' => $row[3] ?? null,
                'phone'           => $row[4] ?? null,
                'address'         => $row[5] ?? null,
                'zipcode'         => $row[6] ?? null,
                'is_pkp'          => isset($row[7]) ? (bool)$row[7] : false,
                'npwp_number'     => $row[8] ?? null,
                'is_active'       => isset($row[9]) ? (bool)$row[9] : true,
            ]);
        });
    }
}

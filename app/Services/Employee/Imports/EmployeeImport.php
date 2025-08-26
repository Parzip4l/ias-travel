<?php

namespace App\Services\Employee\Imports;

use App\Services\Employee\Model\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
           
            0 => new EmployeeSheetImport(),
        ];
    }
}

class EmployeeSheetImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // skip header
        $rows->skip(1)->each(function ($row) {
            if (!isset($row[0]) || empty($row[0])) {
                return; 
            }

            Employee::create([
                'company_id'        => $row[0] ?? null,
                'employee_number'   => $row[1] ?? null,
                'name'              => $row[2] ?? null,
                'division_id'       => $row[3] ?? null,
                'position_id'       => $row[4] ?? null,
                'join_date'         => $row[5] ?? null,
                'end_date'          => $row[6] ?? null,
                'employment_status' => $row[7] ?? null,
                'grade_level'       => $row[8] ?? null,

                'gender'            => $row[9] ?? null,
                'date_of_birth'     => $row[10] ?? null,
                'place_of_birth'    => $row[11] ?? null,
                'marital_status'    => $row[12] ?? null,
                'national_id'       => $row[13] ?? null,
                'tax_number'        => $row[14] ?? null,
                'phone_number'      => $row[15] ?? null,
                'address'           => $row[16] ?? null,
                'kontak_darurat'    => $row[17] ?? null,
            ]);
        });
    }
}

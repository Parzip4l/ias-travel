<?php

namespace App\Services\Company\Exports;

use App\Services\Company\Model\Company;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompanyExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Company::with('companyType')
            ->get()
            ->map(function ($company) {
                return [
                    'id'             => $company->id,
                    'name'           => $company->name,
                    'customer_id'    => $company->customer_id,
                    'email'          => $company->email,
                    'company_type'   => $company->companyType->name ?? null,
                    'phone'          => $company->phone,
                    'address'        => $company->address,
                    'zipcode'        => $company->zipcode,
                    'is_pkp'         => $company->is_pkp ? 'Yes' : 'No',
                    'npwp_number'    => $company->npwp_number,
                    'is_active'      => $company->is_active ? 'Active' : 'Inactive',
                    'created_at'     => $company->created_at,
                    'updated_at'     => $company->updated_at,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Customer ID',
            'Email',
            'Company Type',
            'Phone',
            'Address',
            'Zipcode',
            'Is PKP',
            'NPWP Number',
            'Is Active',
            'Created At',
            'Updated At',
        ];
    }
}

<?php

namespace App\Services\Company\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class CompanyTemplateExport implements FromCollection
{
    protected $types;

    public function __construct($types)
    {
        $this->types = $types;
    }

    public function collection()
    {
        // Header kolom utama
        $header = [
            'name',
            'customer_id',
            'email',
            'company_type_id', // user isi pakai id
            'phone',
            'address',
            'zipcode',
            'is_pkp',
            'npwp_number',
            'is_active'
        ];

        $data = collect([$header]);

        // Tambahkan referensi company type
        $ref = collect([
            ['Company Types (gunakan id)'],
            ['id', 'name'],
        ])->merge($this->types->map(fn ($t) => [$t->id, $t->name]));

        return $data->merge($ref);
    }
}

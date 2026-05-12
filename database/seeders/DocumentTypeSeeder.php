<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'name' => 'Insurance',
                'applicable_for' => 'vehicle',
                'is_mandatory' => true,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'RC Book',
                'applicable_for' => 'vehicle',
                'is_mandatory' => true,
                'is_expiry_required' => false,
            ],
            [
                'name' => 'Driving License',
                'applicable_for' => 'driver',
                'is_mandatory' => true,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'Aadhaar',
                'applicable_for' => 'driver',
                'is_mandatory' => true,
                'is_expiry_required' => false,
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $documentType = DocumentType::firstOrCreate(
                    ['name' => $record['name']],
                    [
                        'applicable_for' => $record['applicable_for'],
                        'is_active' => true,
                        'is_mandatory' => $record['is_mandatory'],
                        'is_expiry_required' => $record['is_expiry_required'],
                    ]
                );

                if (! $documentType->code) {
                    $documentType->code = generate_code('Document Type Module', $documentType->id, 3, 'DOCT');
                    $documentType->save();
                }
            }
        });
    }
}

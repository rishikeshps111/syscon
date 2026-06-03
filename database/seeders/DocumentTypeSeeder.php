<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        DocumentType::where('name', 'RC Book')
            ->where('applicable_for', 'vehicle')
            ->update(['name' => 'RC']);

        $records = [
            [
                'name' => 'RC',
                'applicable_for' => 'vehicle',
                'is_mandatory' => true,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'Insurance',
                'applicable_for' => 'vehicle',
                'is_mandatory' => true,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'PUC',
                'applicable_for' => 'vehicle',
                'is_mandatory' => true,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'Permit',
                'applicable_for' => 'vehicle',
                'is_mandatory' => true,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'Fitness',
                'applicable_for' => 'vehicle',
                'is_mandatory' => true,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'Tax Certificate',
                'applicable_for' => 'vehicle',
                'is_mandatory' => false,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'Battery Warranty',
                'applicable_for' => 'vehicle',
                'is_mandatory' => false,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'GPS Installation Certificate',
                'applicable_for' => 'vehicle',
                'is_mandatory' => false,
                'is_expiry_required' => false,
            ],
            [
                'name' => 'OEM Registration Certificate',
                'applicable_for' => 'oem',
                'is_mandatory' => true,
                'is_expiry_required' => false,
            ],
            [
                'name' => 'GST Certificate',
                'applicable_for' => 'oem',
                'is_mandatory' => true,
                'is_expiry_required' => false,
            ],
            [
                'name' => 'PAN Card',
                'applicable_for' => 'oem',
                'is_mandatory' => true,
                'is_expiry_required' => false,
            ],
            [
                'name' => 'Trade License',
                'applicable_for' => 'oem',
                'is_mandatory' => false,
                'is_expiry_required' => true,
            ],
            [
                'name' => 'Bank Mandate',
                'applicable_for' => 'oem',
                'is_mandatory' => false,
                'is_expiry_required' => false,
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $documentType = DocumentType::updateOrCreate(
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

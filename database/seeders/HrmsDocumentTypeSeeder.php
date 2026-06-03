<?php

namespace Database\Seeders;

use App\Models\HrmsDocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HrmsDocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'name' => 'Aadhaar Card',
                'category' => 'Identity Proof',
                'applicable_for' => 'all',
                'allowed_file_types' => 'pdf',
                'is_mandatory' => true,
                'is_expiry_required' => false,
                'description' => 'Government identity proof',
                'is_active' => true,
            ],
            [
                'name' => 'Driving License',
                'category' => 'Identity Proof',
                'applicable_for' => 'driver',
                'allowed_file_types' => 'pdf',
                'is_mandatory' => true,
                'is_expiry_required' => true,
                'description' => 'Driver license document',
                'is_active' => true,
            ],
            [
                'name' => 'Bank Passbook',
                'category' => 'Financial',
                'applicable_for' => 'all',
                'allowed_file_types' => 'jpg',
                'is_mandatory' => false,
                'is_expiry_required' => false,
                'description' => 'Bank account proof',
                'is_active' => true,
            ],
            [
                'name' => 'Education Certificate',
                'category' => 'Educational',
                'applicable_for' => 'supervisor',
                'allowed_file_types' => 'pdf',
                'is_mandatory' => false,
                'is_expiry_required' => false,
                'description' => 'Qualification certificate',
                'is_active' => true,
            ],
            [
                'name' => 'Employment Agreement',
                'category' => 'Legal',
                'applicable_for' => 'controller',
                'allowed_file_types' => 'doc',
                'is_mandatory' => true,
                'is_expiry_required' => false,
                'description' => 'Signed employment agreement',
                'is_active' => true,
            ],
            [
                'name' => 'Rental Agreement',
                'category' => 'Address Proof',
                'applicable_for' => 'all',
                'allowed_file_types' => 'pdf',
                'is_mandatory' => false,
                'is_expiry_required' => true,
                'description' => 'Current address proof',
                'is_active' => false,
            ],
            [
                'name' => 'PAN Card',
                'category' => 'Identity Proof',
                'applicable_for' => 'all',
                'allowed_file_types' => 'pdf',
                'is_mandatory' => true,
                'is_expiry_required' => false,
                'description' => 'Permanent Account Number card',
                'is_active' => true,
            ],
            [
                'name' => 'Staff ID Card',
                'category' => 'Identity Proof',
                'applicable_for' => 'staff',
                'allowed_file_types' => 'png',
                'is_mandatory' => true,
                'is_expiry_required' => false,
                'description' => 'Staff identification card',
                'is_active' => true,
            ],
            [
                'name' => 'Police Verification',
                'category' => 'Legal',
                'applicable_for' => 'driver',
                'allowed_file_types' => 'pdf',
                'is_mandatory' => true,
                'is_expiry_required' => true,
                'description' => 'Driver police verification certificate',
                'is_active' => true,
            ],
            [
                'name' => 'Medical Fitness Certificate',
                'category' => 'Identity Proof',
                'applicable_for' => 'driver',
                'allowed_file_types' => 'pdf',
                'is_mandatory' => true,
                'is_expiry_required' => true,
                'description' => 'Medical fitness certificate for driving duty',
                'is_active' => true,
            ],
            [
                'name' => 'Experience Certificate',
                'category' => 'Educational',
                'applicable_for' => 'all',
                'allowed_file_types' => 'pdf',
                'is_mandatory' => false,
                'is_expiry_required' => false,
                'description' => 'Previous employment experience proof',
                'is_active' => true,
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $documentType = HrmsDocumentType::firstOrCreate(
                    ['name' => $record['name']],
                    [
                        'category' => $record['category'],
                        'applicable_for' => $record['applicable_for'],
                        'allowed_file_types' => $record['allowed_file_types'],
                        'is_mandatory' => $record['is_mandatory'],
                        'is_expiry_required' => $record['is_expiry_required'],
                        'description' => $record['description'],
                        'is_active' => $record['is_active'],
                    ]
                );

                if (! $documentType->code) {
                    $documentType->code = generate_code('HRMS Document Type Module', $documentType->id, 3, 'HDT');
                    $documentType->save();
                }
            }
        });
    }
}

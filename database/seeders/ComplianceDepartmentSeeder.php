<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ComplianceDepartment;

class ComplianceDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'HRD', 'head_name' => 'Ibu Bernadett', 'code' => 'HRD'],
            ['name' => 'Business', 'head_name' => 'Ibu Tina', 'code' => 'BUS'],
            ['name' => 'Accounting', 'head_name' => 'Bp. Benny', 'code' => 'ACC'],
            ['name' => 'IT', 'head_name' => 'Bp. Timotius', 'code' => 'IT'],
            ['name' => 'Purchasing', 'head_name' => 'Ibu Korin', 'code' => 'PUR'],
            ['name' => 'QA', 'head_name' => 'Bp. Saepuloh', 'code' => 'QA'],
            ['name' => 'QC', 'head_name' => 'Bp. Saepuloh', 'code' => 'QC'],
            ['name' => 'PE', 'head_name' => 'Bp. Suhirwan', 'code' => 'PE'],
            ['name' => 'PC', 'head_name' => 'Bp. Budiman', 'code' => 'PC'],
            ['name' => 'Plastic Injection', 'head_name' => 'Bp. Hermawan', 'code' => 'PI'],
            ['name' => 'Plastic Injection - K', 'head_name' => 'Bp. Pawarid', 'code' => 'PIK'],
            ['name' => 'Second Process', 'head_name' => 'Bp. Wiji', 'code' => 'SP'],
            ['name' => 'Assembly', 'head_name' => 'Bp. Dedi A', 'code' => 'ASM'],
            ['name' => 'Design Mould', 'head_name' => 'Bp. Fang', 'code' => 'DM'],
            ['name' => 'Moulding', 'head_name' => 'Bp. Ong', 'code' => 'MLD'],
            ['name' => 'Maintenance Tooling & Machine', 'head_name' => 'Bp. Arifin', 'code' => 'MTM'],
            ['name' => 'Maintenance Utility & Infrastructure', 'head_name' => 'Bp. Slamet', 'code' => 'MUI'],
            ['name' => 'Store', 'head_name' => 'Bp. Agus', 'code' => 'STR'],
            ['name' => 'Logistic', 'head_name' => 'Bp. Agus', 'code' => 'LOG'],
        ];

        foreach ($departments as $dept) {
            ComplianceDepartment::updateOrCreate(
                ['name' => $dept['name']],
                ['head_name' => $dept['head_name'], 'code' => $dept['code']]
            );
        }
    }
}

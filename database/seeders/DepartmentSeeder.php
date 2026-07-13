<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // The seven departments (PRD §1.4) [LOCKED]
        $departments = [
            ['code' => 'SHE', 'name' => 'Safety, Health & Environment', 'alias' => null],
            ['code' => 'PLANT', 'name' => 'Plant', 'alias' => null],
            ['code' => 'HCGA', 'name' => 'Human Capital & General Affairs', 'alias' => null],
            ['code' => 'FWA', 'name' => 'Finance, Warehouse & Accounting', 'alias' => 'FALOG'],
            ['code' => 'ICTMD', 'name' => 'ICT & Management Development', 'alias' => null],
            ['code' => 'PRODUKSI', 'name' => 'Produksi', 'alias' => null],
            ['code' => 'ENGINEERING', 'name' => 'Engineering', 'alias' => null],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(['code' => $dept['code']], $dept);
        }
    }
}

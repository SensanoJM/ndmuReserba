<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            'College of Engineering Architecture and Computing',
            'College of Arts and Science',
            'College of Education',
            'College of Business Administration',
        ];

        foreach ($departments as $department) {
            Department::create(['name' => $department]);
        }
    }
}
<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\MailCategory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach(['Administration','Finance','Operations'] as $name) Department::firstOrCreate(['name'=>$name]);
        foreach(['General','Finance','Legal','HR','Procurement'] as $name) MailCategory::firstOrCreate(['name'=>$name]);
    }
}

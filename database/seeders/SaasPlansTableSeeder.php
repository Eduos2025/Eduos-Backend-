<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaasPlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->delete();

        DB::table('plans')->insert([
            [
                'id' => 1,
                'name' => 'Basic Plan',
                'slug' => 'basic',
                'monthly_price' => 10000.00,
                'yearly_price' => 100000.00,
                'trial_days' => 14,
                'max_students' => 150,
                'max_staff' => 20,
                'max_branches' => 1,
                'active' => true,
                'features' => json_encode(['exams', 'attendance']),
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Standard Plan',
                'slug' => 'standard',
                'monthly_price' => 25000.00,
                'yearly_price' => 250000.00,
                'trial_days' => 14,
                'max_students' => 500,
                'max_staff' => 50,
                'max_branches' => 1,
                'active' => true,
                'features' => json_encode(['exams', 'attendance', 'timetables', 'messaging']),
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Premium Plan',
                'slug' => 'premium',
                'monthly_price' => 50000.00,
                'yearly_price' => 500000.00,
                'trial_days' => 14,
                'max_students' => 2000,
                'max_staff' => 150,
                'max_branches' => 3,
                'active' => true,
                'features' => json_encode(['exams', 'attendance', 'timetables', 'messaging', 'payments', 'tickets']),
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}

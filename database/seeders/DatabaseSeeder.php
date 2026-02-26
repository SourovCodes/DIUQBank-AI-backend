<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ExamType;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Department::factory()
            ->count(5)
            ->hasCourses(6)
            ->create();

        Semester::factory()->count(8)->create();
        ExamType::factory()->count(5)->create();
    }
}

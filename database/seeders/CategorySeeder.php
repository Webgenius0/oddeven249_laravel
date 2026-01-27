<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Natural',
            'Dancing',
            'Advancer',
            'Education',
            'Sport',
            'Cultural',
            'Business'
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category], 
                ['status' => 'active'] 
            );
        }
    }
}

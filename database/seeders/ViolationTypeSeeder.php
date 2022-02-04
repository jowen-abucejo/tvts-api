<?php

namespace Database\Seeders;

use App\Models\ViolationType;
use Illuminate\Database\Seeder;

class ViolationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $violation_types = [
            [
                'type' => 'Minor',
                'vehicle_type' => '2-3-wheel',
                'penalties' => '250,350,500'
            ],
            [
                'type' => 'Minor',
                'vehicle_type' => '4-wheel',
                'penalties' => '300,500,1000'
            ],
            [
                'type' => 'Major',
                'vehicle_type' => '2-3-wheel',
                'penalties' => '2500'
            ],
            [
                'type' => 'Major',
                'vehicle_type' => '4-wheel',
                'penalties' => '2500'
            ],
        ];
        foreach ($violation_types as $violation_type) {
            ViolationType::create($violation_type);
        }
    }
}

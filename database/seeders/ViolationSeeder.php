<?php

namespace Database\Seeders;

use App\Models\Violation;
use Illuminate\Database\Seeder;

class ViolationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $minor_violations = [
            [
                'violation' => 'Disregarding Traffic Sign/Office/MO',
                'violation_code' => 'V1',
            ],
        ];
        $major_violations = [
            [
                'violation' => 'Colorum',
                'violation_code' => 'V2',
            ]
        ];
        foreach ($minor_violations as $violation) {
            $violation = Violation::create($violation);
            $violation->violation_types()->attach([1,2]);
        }
        foreach ($major_violations as $violation) {
            $violation = Violation::create($violation);
            $violation->violation_types()->attach([3,4]);
        }
    }
}

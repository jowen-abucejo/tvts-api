<?php

namespace Database\Seeders;

use App\Models\AssignTypes;
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
            // $violation = Violation::create($violation);
            $assign = AssignTypes::create(['violation_id' => 1, 'violation_type_id' => 1]);
            $assign->delete();
            AssignTypes::create(['violation_id' => 1, 'violation_type_id' => 2]);
        }
        foreach ($major_violations as $violation) {
            // $violation = Violation::create($violation);
            AssignTypes::create(['violation_id' => 2, 'violation_type_id' => 3]);
            AssignTypes::create(['violation_id' => 2, 'violation_type_id' => 4]);
        }
    }
}

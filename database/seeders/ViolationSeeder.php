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
            [
                'violation' => 'Unregistered Motor Vehicle',
                'violation_code' => 'V2',
            ],
            [
                'violation' => 'Unlicensed Driver',
                'violation_code' => 'V3',
            ],
            [
                'violation' => 'Failure to carry/surrender Driver\'s License',
                'violation_code' => 'V4',
            ],
            [
                'violation' => 'Invalid/Revoked/Expired OR/CR#/DL',
                'violation_code' => 'V5',
            ],
            [
                'violation' => 'OR/CR not carried',
                'violation_code' => 'V6',
            ],
            [
                'violation' => 'Student Driver not acc. by Prof. Driver',
                'violation_code' => 'V7',
            ],
            [
                'violation' => 'Stalled Vehicle',
                'violation_code' => 'V8',
            ],
            [
                'violation' => 'Loading/Unloading in prohibited zone',
                'violation_code' => 'V9',
            ],
            [
                'violation' => 'No front panel route',
                'violation_code' => 'V10',
            ],
            [
                'violation' => 'Out of Line',
                'violation_code' => 'V11',
            ],
            [
                'violation' => 'Unauthorized wearing of slippers/shirt',
                'violation_code' => 'V12',
            ],
            [
                'violation' => 'Reckless Driving',
                'violation_code' => 'V13',
            ],
            [
                'violation' => 'Illegal Parking/Illegal Terminal',
                'violation_code' => 'V14',
            ],
            [
                'violation' => 'Overloading',
                'violation_code' => 'V15',
            ],
            [
                'violation' => 'Refusal to convey passenger/Trip Cutting',
                'violation_code' => 'V16',
            ],
            [
                'violation' => 'Colorum/Unfranchised Operation',
                'violation_code' => 'V17',
            ],
            [
                'violation' => 'No Fare Matrix Display',
                'violation_code' => 'V18',
            ],
            [
                'violation' => 'No Garbage Basket/Can',
                'violation_code' => 'V19',
            ],
            [
                'violation' => 'Open Muffler',
                'violation_code' => 'V20',
            ],
            [
                'violation' => 'Coding',
                'violation_code' => 'V21',
            ],
        ];
        $major_violations = [
            [
                'violation' => 'Colorum',
                'violation_code' => 'V22',
            ]
        ];
        foreach ($minor_violations as $violation) {
            $violation = Violation::create($violation);
            $assign = AssignTypes::create(['violation_id' => $violation->id, 'violation_type_id' => 1]);
            // $assign->delete();
            AssignTypes::create(['violation_id' => $violation->id, 'violation_type_id' => 2]);
        }
        foreach ($major_violations as $violation) {
            $violation = Violation::create($violation);
            AssignTypes::create(['violation_id' => $violation->id, 'violation_type_id' => 3]);
            AssignTypes::create(['violation_id' => $violation->id, 'violation_type_id' => 4]);
        }
    }
}

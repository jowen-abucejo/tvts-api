<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ExtraPropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $properties = [
            //* violators default extra properties
            [
                "property" => "address",
                "property_owner" => "violator",
                "text_label" => "Address",
                "data_type" => "string",
                "is_required" =>  true,
            ],
            [
                "property" => "parent_and_license",
                "property_owner" => "violator",
                "text_label" => "Parent's Name and License Number (For minors only)",
                "data_type" => "string",
                "is_required" =>  false,
            ],
            [
                "property" => "mobile_number",
                "property_owner" => "violator",
                "text_label" => "10-Digit Mobile Number",
                "data_type" => "mobile",
                "is_required" =>  false,
            ],
            [
                "property" => "email_address",
                "property_owner" => "violator",
                "text_label" => "Email",
                "data_type" => "email",
                "is_required" =>  false,
            ],

            //* tickets default extra properties
            [
                "property" => "plate_number",
                "property_owner" => "ticket",
                "text_label" => "Plate Number",
                "data_type" => "string",
                "is_required" =>  false,
            ],
            [
                "property" => "vehicle_owner",
                "property_owner" => "ticket",
                "text_label" => "Vehicle Owner",
                "data_type" => "string",
                "is_required" =>  false,
            ],
            [
                "property" => "owner_address",
                "property_owner" => "ticket",
                "text_label" => "Owner Address",
                "data_type" => "email",
                "is_required" =>  false,
            ],
            [
                "property" => "place_of_apprehension",
                "property_owner" => "ticket",
                "text_label" => "Place of Apprehension",
                "data_type" => "string",
                "is_required" =>  false,
            ],
            [
                "property" => "vehicle_is_impounded",
                "property_owner" => "ticket",
                "text_label" => "Vehicle is impounded?",
                "data_type" => "boolean",
                "is_required" =>  true,
            ],
            [
                "property" => "is_under_protest",
                "property_owner" => "ticket",
                "text_label" => "Driver is under protest?",
                "data_type" => "boolean",
                "is_required" =>  true,
            ],
            [
                "property" => "license_is_confiscated",
                "property_owner" => "ticket",
                "text_label" => "License is confiscated?",
                "data_type" => "boolean",
                "is_required" =>  true,
            ],
            [
                "property" => "document_signature",
                "property_owner" => "ticket",
                "text_label" => "Driver's ID",
                "data_type" => "image",
                "is_required" =>  true,
            ],
            [
                "property" => "gender",
                "property_owner" => "violator",
                "text_label" => "Gender",
                "data_type" => "selection",
                "options" => "Male;Female",
                "is_multiple_select" => false,
                "is_required" =>  true,
            ],
        ];
        foreach ($properties as $property) {
            \App\Models\ExtraProperty::insert($property);
        }
    }
}

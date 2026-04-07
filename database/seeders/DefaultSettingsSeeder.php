<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        

        // $cities = [
        //     ['name' => 'New York', 'status' => 1],
        //     ['name' => 'Los Angeles', 'status' => 1],
        //     ['name' => 'Chicago', 'status' => 1],
        //     ['name' => 'Houston', 'status' => 1],
        //     ['name' => 'Phoenix', 'status' => 1],
        //     ['name' => 'Philadelphia', 'status' => 1],
        //     ['name' => 'San Antonio', 'status' => 1],
        //     ['name' => 'San Diego', 'status' => 1],
        //     ['name' => 'Dallas', 'status' => 1],
        //     ['name' => 'San Jose', 'status' => 1],
        //     ['name' => 'Austin', 'status' => 1],
        //     ['name' => 'Jacksonville', 'status' => 1],
        //     ['name' => 'Fort Worth', 'status' => 1],
        //     ['name' => 'Columbus', 'status' => 1],
        //     ['name' => 'Charlotte', 'status' => 1],
        //     ['name' => 'San Francisco', 'status' => 1],
        //     ['name' => 'Indianapolis', 'status' => 1],
        //     ['name' => 'Seattle', 'status' => 1],
        //     ['name' => 'Denver', 'status' => 1],
        //     ['name' => 'Washington', 'status' => 1],
        // ];

        // \DB::table('cities')->insert($cities);


        
        $states = [
            ['name' => 'Alabama', 'status' => 1],
            ['name' => 'Alaska', 'status' => 1],
            ['name' => 'Arizona', 'status' => 1],
            ['name' => 'Arkansas', 'status' => 1],
            ['name' => 'California', 'status' => 1],
            ['name' => 'Colorado', 'status' => 1],
            ['name' => 'Connecticut', 'status' => 1],
            ['name' => 'Delaware', 'status' => 1],
            ['name' => 'Florida', 'status' => 1],
            ['name' => 'Georgia', 'status' => 1],
            ['name' => 'Hawaii', 'status' => 1],
            ['name' => 'Idaho', 'status' => 1],
            ['name' => 'Illinois', 'status' => 1],
            ['name' => 'Indiana', 'status' => 1],
            ['name' => 'Iowa', 'status' => 1],
            ['name' => 'Kansas', 'status' => 1],
            ['name' => 'Kentucky', 'status' => 1],
            ['name' => 'Louisiana', 'status' => 1],
            ['name' => 'Maine', 'status' => 1],
            ['name' => 'Maryland', 'status' => 1],
            ['name' => 'Massachusetts', 'status' => 1],
            ['name' => 'Michigan', 'status' => 1],
            ['name' => 'Minnesota', 'status' => 1],
            ['name' => 'Mississippi', 'status' => 1],
            ['name' => 'Missouri', 'status' => 1],
            ['name' => 'Montana', 'status' => 1],
            ['name' => 'Nebraska', 'status' => 1],
            ['name' => 'Nevada', 'status' => 1],
            ['name' => 'New Hampshire', 'status' => 1],
            ['name' => 'New Jersey', 'status' => 1],
            ['name' => 'New Mexico', 'status' => 1],
            ['name' => 'New York', 'status' => 1],
            ['name' => 'North Carolina', 'status' => 1],
            ['name' => 'North Dakota', 'status' => 1],
            ['name' => 'Ohio', 'status' => 1],
            ['name' => 'Oklahoma', 'status' => 1],
            ['name' => 'Oregon', 'status' => 1],
            ['name' => 'Pennsylvania', 'status' => 1],
            ['name' => 'Rhode Island', 'status' => 1],
            ['name' => 'South Carolina', 'status' => 1],
            ['name' => 'South Dakota', 'status' => 1],
            ['name' => 'Tennessee', 'status' => 1],
            ['name' => 'Texas', 'status' => 1],
            ['name' => 'Utah', 'status' => 1],
            ['name' => 'Vermont', 'status' => 1],
            ['name' => 'Virginia', 'status' => 1],
            ['name' => 'Washington', 'status' => 1],
            ['name' => 'West Virginia', 'status' => 1],
            ['name' => 'Wisconsin', 'status' => 1],
            ['name' => 'Wyoming', 'status' => 1],
            ['name' => 'District of Columbia', 'status' => 1],
            ['name' => 'Puerto Rico', 'status' => 1],
            ['name' => 'Guam', 'status' => 1],
            ['name' => 'American Samoa', 'status' => 1],
            ['name' => 'U.S. Virgin Islands', 'status' => 1],
            ['name' => 'Northern Mariana Islands', 'status' => 1],
        ];
        

        \DB::table('states')->insert($states);

        $languages = [
            ['name' => 'Afrikaans', 'status' => 1],
            ['name' => 'Amharic', 'status' => 1],
            ['name' => 'Arabic', 'status' => 1],
            ['name' => 'Assamese', 'status' => 1],
            ['name' => 'Bengali', 'status' => 1],
            ['name' => 'Chinese', 'status' => 1],
            ['name' => 'Dutch', 'status' => 1],
            ['name' => 'English', 'status' => 1],
            ['name' => 'French', 'status' => 1],
            ['name' => 'German', 'status' => 1],
            ['name' => 'Greek', 'status' => 1],
            ['name' => 'Gujarati', 'status' => 1],
            ['name' => 'Hebrew', 'status' => 1],
            ['name' => 'Hindi', 'status' => 1],
            ['name' => 'Italian', 'status' => 1],
            ['name' => 'Japanese', 'status' => 1],
            ['name' => 'Kannada', 'status' => 1],
            ['name' => 'Korean', 'status' => 1],
            ['name' => 'Malay', 'status' => 1],
            ['name' => 'Malayalam', 'status' => 1],
            ['name' => 'Maithili', 'status' => 1],
            ['name' => 'Marathi', 'status' => 1],
            ['name' => 'Nepali', 'status' => 1],
            ['name' => 'Odia', 'status' => 1],
            ['name' => 'Pashto', 'status' => 1],
            ['name' => 'Persian', 'status' => 1],
            ['name' => 'Polish', 'status' => 1],
            ['name' => 'Portuguese', 'status' => 1],
            ['name' => 'Punjabi', 'status' => 1],
            ['name' => 'Russian', 'status' => 1],
            ['name' => 'Sinhala', 'status' => 1],
            ['name' => 'Somali', 'status' => 1],
            ['name' => 'Spanish', 'status' => 1],
            ['name' => 'Swahili', 'status' => 1],
            ['name' => 'Tamil', 'status' => 1],
            ['name' => 'Telugu', 'status' => 1],
            ['name' => 'Thai', 'status' => 1],
            ['name' => 'Turkish', 'status' => 1],
            ['name' => 'Urdu', 'status' => 1],
            ['name' => 'Vietnamese', 'status' => 1],
            ['name' => 'Xhosa', 'status' => 1],
            ['name' => 'Zulu', 'status' => 1],
        ];
        

        \DB::table('languages')->insert($languages);
    }
}

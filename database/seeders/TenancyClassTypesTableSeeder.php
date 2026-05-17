<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenancyClassTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('class_types')->delete();
        
        \DB::table('class_types')->insert(array (
            0 => 
            array (
                'name' => 'Primary',
                'code' => 'PE',
                'subjects_considered' => 7,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'name' => 'Secondary',
                'code' => 'SE',
                'subjects_considered' => 7,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'name' => 'Tertiary (Advance)',
                'code' => 'ASE',
                'subjects_considered' => 3,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
    }
}
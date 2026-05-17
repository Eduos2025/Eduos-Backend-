<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenancyMyClassesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('my_classes')->delete();
        
        \DB::table('my_classes')->insert(array (
            0 => 
            array (
                'name' => 'Form One',
                'class_type_id' => \DB::table('class_types')->where('code', 'SE')->value('id'),
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
            ),
        ));
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenancySectionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('sections')->delete();
        
        \DB::table('sections')->insert(array (
            0 => 
            array (
                'name' => 'A',
                'my_class_id' => \DB::table('my_classes')->where('class_type_id', \DB::table('class_types')->where('code', 'SE')->value('id'))->value('id'),
                'teacher_id' => NULL,
                'active' => 1,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}
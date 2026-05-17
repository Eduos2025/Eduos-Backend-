<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenancyStudentRecordsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('student_records')->delete();
        
        \DB::table('student_records')->insert(array (
            0 => 
            array (
                'user_id' => \DB::table('users')->where('username', 'student')->value('id'),
                'ps_name' => 'school',
                'ss_name' => '',
                'birth_certificate' => NULL,
                'disability' => NULL,
                'food_taboos' => NULL,
                'chp' => NULL,
                'p_status' => 'Not Orphan',
                'house_no' => '',
                'my_class_id' => 1,
                'section_id' => 1,
                'adm_no' => 'STUDENT',
                'my_parent_id' => 5,
                'dorm_id' => NULL,
                'dorm_room_no' => NULL,
                'session' => date("Y") - 1 . '-' . date('Y'),
                'age' => NULL,
                'date_admitted' => \Carbon\Carbon::now()->format('m-d-Y'),
                'grad' => 0,
                'grad_date' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        )); 
    }
}
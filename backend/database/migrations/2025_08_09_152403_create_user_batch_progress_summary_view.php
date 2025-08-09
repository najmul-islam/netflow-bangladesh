<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE VIEW `user_batch_progress_summary` AS select `u`.`user_id` AS `user_id`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`u`.`email` AS `email`,`cb`.`batch_id` AS `batch_id`,`cb`.`batch_name` AS `batch_name`,`cb`.`batch_code` AS `batch_code`,`c`.`course_id` AS `course_id`,`c`.`title` AS `course_title`,`be`.`enrollment_date` AS `enrollment_date`,`be`.`progress_percentage` AS `progress_percentage`,`be`.`status` AS `enrollment_status`,`be`.`attendance_percentage` AS `attendance_percentage`,`be`.`last_accessed` AS `last_accessed`,`be`.`final_exam_passed` AS `final_exam_passed`,`be`.`certificate_issued` AS `certificate_issued`,count(distinct `l`.`lesson_id`) AS `total_lessons`,count(distinct case when `blp`.`status` = 'completed' then `l`.`lesson_id` end) AS `completed_lessons`,count(distinct `bs`.`schedule_id`) AS `total_classes`,count(distinct case when `ca`.`status` = 'present' then `ca`.`attendance_id` end) AS `attended_classes`,`bsp`.`overall_grade` AS `overall_grade`,`bsp`.`class_rank` AS `class_rank` from (((((((((`lms_system`.`users` `u` join `lms_system`.`batch_enrollments` `be` on(`u`.`user_id` = `be`.`user_id`)) join `lms_system`.`course_batches` `cb` on(`be`.`batch_id` = `cb`.`batch_id`)) join `lms_system`.`courses` `c` on(`cb`.`course_id` = `c`.`course_id`)) left join `lms_system`.`modules` `m` on(`c`.`course_id` = `m`.`course_id`)) left join `lms_system`.`lessons` `l` on(`m`.`module_id` = `l`.`module_id` and `l`.`is_published` = 1)) left join `lms_system`.`batch_lesson_progress` `blp` on(`l`.`lesson_id` = `blp`.`lesson_id` and `blp`.`user_id` = `u`.`user_id` and `blp`.`batch_id` = `cb`.`batch_id`)) left join `lms_system`.`batch_schedule` `bs` on(`cb`.`batch_id` = `bs`.`batch_id`)) left join `lms_system`.`class_attendance` `ca` on(`bs`.`schedule_id` = `ca`.`schedule_id` and `ca`.`user_id` = `u`.`user_id`)) left join `lms_system`.`batch_student_performance` `bsp` on(`u`.`user_id` = `bsp`.`user_id` and `cb`.`batch_id` = `bsp`.`batch_id`)) where `u`.`status` = 'active' group by `u`.`user_id`,`u`.`first_name`,`u`.`last_name`,`u`.`email`,`cb`.`batch_id`,`cb`.`batch_name`,`cb`.`batch_code`,`c`.`course_id`,`c`.`title`,`be`.`enrollment_date`,`be`.`progress_percentage`,`be`.`status`,`be`.`attendance_percentage`,`be`.`last_accessed`,`be`.`final_exam_passed`,`be`.`certificate_issued`,`bsp`.`overall_grade`,`bsp`.`class_rank`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `user_batch_progress_summary`");
    }
};

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
        DB::statement("CREATE VIEW `batch_class_schedule_view` AS select `bs`.`schedule_id` AS `schedule_id`,`bs`.`batch_id` AS `batch_id`,`cb`.`batch_name` AS `batch_name`,`cb`.`batch_code` AS `batch_code`,`c`.`course_id` AS `course_id`,`c`.`title` AS `course_title`,`bs`.`title` AS `class_title`,`bs`.`session_type` AS `session_type`,`bs`.`start_datetime` AS `start_datetime`,`bs`.`end_datetime` AS `end_datetime`,`bs`.`duration_minutes` AS `duration_minutes`,`bs`.`meeting_platform` AS `meeting_platform`,`bs`.`meeting_url` AS `meeting_url`,`bs`.`meeting_id` AS `meeting_id`,`bs`.`meeting_password` AS `meeting_password`,`bs`.`status` AS `class_status`,count(distinct `be`.`user_id`) AS `enrolled_students`,count(distinct case when `ca`.`status` = 'present' then `ca`.`user_id` end) AS `attended_students`,case when count(distinct `be`.`user_id`) > 0 then round(count(distinct case when `ca`.`status` = 'present' then `ca`.`user_id` end) / count(distinct `be`.`user_id`) * 100,2) else 0 end AS `attendance_percentage`,`bi`.`user_id` AS `instructor_id`,`u`.`first_name` AS `instructor_first_name`,`u`.`last_name` AS `instructor_last_name` from ((((((`lms_system`.`batch_schedule` `bs` join `lms_system`.`course_batches` `cb` on(`bs`.`batch_id` = `cb`.`batch_id`)) join `lms_system`.`courses` `c` on(`cb`.`course_id` = `c`.`course_id`)) left join `lms_system`.`batch_enrollments` `be` on(`cb`.`batch_id` = `be`.`batch_id` and `be`.`status` = 'active')) left join `lms_system`.`class_attendance` `ca` on(`bs`.`schedule_id` = `ca`.`schedule_id`)) left join `lms_system`.`batch_instructors` `bi` on(`cb`.`batch_id` = `bi`.`batch_id` and `bi`.`role` = 'primary' and `bi`.`is_active` = 1)) left join `lms_system`.`users` `u` on(`bi`.`user_id` = `u`.`user_id`)) group by `bs`.`schedule_id`,`bs`.`batch_id`,`cb`.`batch_name`,`cb`.`batch_code`,`c`.`course_id`,`c`.`title`,`bs`.`title`,`bs`.`session_type`,`bs`.`start_datetime`,`bs`.`end_datetime`,`bs`.`duration_minutes`,`bs`.`meeting_platform`,`bs`.`meeting_url`,`bs`.`meeting_id`,`bs`.`meeting_password`,`bs`.`status`,`bi`.`user_id`,`u`.`first_name`,`u`.`last_name`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `batch_class_schedule_view`");
    }
};

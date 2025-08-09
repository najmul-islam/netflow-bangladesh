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
        DB::statement("CREATE VIEW `batch_final_exam_results` AS select `baa`.`attempt_id` AS `attempt_id`,`baa`.`user_id` AS `user_id`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`u`.`email` AS `email`,`baa`.`assessment_id` AS `assessment_id`,`ba`.`title` AS `exam_title`,`cb`.`batch_id` AS `batch_id`,`cb`.`batch_name` AS `batch_name`,`cb`.`batch_code` AS `batch_code`,`c`.`course_id` AS `course_id`,`c`.`title` AS `course_title`,`baa`.`score` AS `score`,`baa`.`max_score` AS `max_score`,`baa`.`percentage` AS `percentage`,`baa`.`passed` AS `passed`,`baa`.`submitted_at` AS `submitted_at`,`be`.`certificate_issued` AS `certificate_issued`,`cert`.`certificate_id` AS `certificate_id`,`cert`.`certificate_number` AS `certificate_number`,`bsp`.`class_rank` AS `class_rank`,`bsp`.`overall_grade` AS `overall_grade` from (((((((`lms_system`.`batch_assessment_attempts` `baa` join `lms_system`.`batch_assessments` `ba` on(`baa`.`assessment_id` = `ba`.`assessment_id`)) join `lms_system`.`course_batches` `cb` on(`baa`.`batch_id` = `cb`.`batch_id`)) join `lms_system`.`courses` `c` on(`cb`.`course_id` = `c`.`course_id`)) join `lms_system`.`users` `u` on(`baa`.`user_id` = `u`.`user_id`)) left join `lms_system`.`batch_enrollments` `be` on(`baa`.`user_id` = `be`.`user_id` and `cb`.`batch_id` = `be`.`batch_id`)) left join `lms_system`.`batch_certificates` `cert` on(`baa`.`user_id` = `cert`.`user_id` and `cb`.`batch_id` = `cert`.`batch_id`)) left join `lms_system`.`batch_student_performance` `bsp` on(`baa`.`user_id` = `bsp`.`user_id` and `cb`.`batch_id` = `bsp`.`batch_id`)) where `ba`.`is_final_exam` = 1 and `baa`.`status` = 'graded'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `batch_final_exam_results`");
    }
};

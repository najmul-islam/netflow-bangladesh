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
        DB::statement("CREATE VIEW `batch_enrollment_summary` AS select `cb`.`batch_id` AS `batch_id`,`cb`.`batch_name` AS `batch_name`,`cb`.`batch_code` AS `batch_code`,`cb`.`status` AS `batch_status`,`cb`.`start_date` AS `start_date`,`cb`.`end_date` AS `end_date`,`cb`.`max_students` AS `max_students`,`c`.`course_id` AS `course_id`,`c`.`title` AS `course_title`,`c`.`price` AS `price`,`c`.`is_free` AS `is_free`,`c`.`has_certificate` AS `has_certificate`,count(distinct `be`.`enrollment_id`) AS `total_enrollments`,count(distinct case when `be`.`status` = 'active' then `be`.`enrollment_id` end) AS `active_enrollments`,count(distinct case when `be`.`status` = 'completed' then `be`.`enrollment_id` end) AS `completed_enrollments`,count(distinct case when `be`.`certificate_issued` = 1 then `be`.`enrollment_id` end) AS `certificates_issued`,round(avg(`br`.`rating`),2) AS `average_rating`,count(distinct `br`.`review_id`) AS `total_reviews`,`cb`.`created_at` AS `created_at`,`cb`.`updated_at` AS `updated_at` from (((`lms_system`.`course_batches` `cb` join `lms_system`.`courses` `c` on(`cb`.`course_id` = `c`.`course_id`)) left join `lms_system`.`batch_enrollments` `be` on(`cb`.`batch_id` = `be`.`batch_id`)) left join `lms_system`.`batch_reviews` `br` on(`cb`.`batch_id` = `br`.`batch_id` and `br`.`is_approved` = 1)) group by `cb`.`batch_id`,`cb`.`batch_name`,`cb`.`batch_code`,`cb`.`status`,`cb`.`start_date`,`cb`.`end_date`,`cb`.`max_students`,`c`.`course_id`,`c`.`title`,`c`.`price`,`c`.`is_free`,`c`.`has_certificate`,`cb`.`created_at`,`cb`.`updated_at`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `batch_enrollment_summary`");
    }
};

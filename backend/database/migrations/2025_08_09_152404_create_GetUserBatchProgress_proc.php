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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserBatchProgress`(
    IN p_user_id CHAR(36),
    IN p_batch_id CHAR(36)
)
BEGIN
    SELECT 
        be.enrollment_id,
        be.progress_percentage,
        be.status as enrollment_status,
        be.attendance_percentage,
        be.last_accessed,
        be.final_exam_passed,
        be.certificate_issued,
        COUNT(DISTINCT l.lesson_id) as total_lessons,
        COUNT(DISTINCT CASE WHEN blp.status = 'completed' THEN l.lesson_id END) as completed_lessons,
        COUNT(DISTINCT bs.schedule_id) as total_classes,
        COUNT(DISTINCT CASE WHEN ca.status = 'present' THEN ca.attendance_id END) as attended_classes,
        COUNT(DISTINCT CASE WHEN ba.is_final_exam = TRUE THEN baa.attempt_id END) as final_exam_attempts,
        MAX(CASE WHEN ba.is_final_exam = TRUE THEN baa.percentage END) as best_final_exam_score,
        bsp.overall_grade,
        bsp.class_rank
    FROM batch_enrollments be
    JOIN course_batches cb ON be.batch_id = cb.batch_id
    LEFT JOIN modules m ON cb.course_id = m.course_id
    LEFT JOIN lessons l ON m.module_id = l.module_id AND l.is_published = TRUE
    LEFT JOIN batch_lesson_progress blp ON l.lesson_id = blp.lesson_id AND blp.user_id = be.user_id AND blp.batch_id = be.batch_id
    LEFT JOIN batch_schedule bs ON cb.batch_id = bs.batch_id
    LEFT JOIN class_attendance ca ON bs.schedule_id = ca.schedule_id AND ca.user_id = be.user_id
    LEFT JOIN batch_assessments ba ON cb.batch_id = ba.batch_id
    LEFT JOIN batch_assessment_attempts baa ON ba.assessment_id = baa.assessment_id AND baa.user_id = be.user_id
    LEFT JOIN batch_student_performance bsp ON be.user_id = bsp.user_id AND be.batch_id = bsp.batch_id
    WHERE be.user_id = p_user_id AND be.batch_id = p_batch_id
    GROUP BY be.enrollment_id, be.progress_percentage, be.status, be.attendance_percentage, be.last_accessed, 
             be.final_exam_passed, be.certificate_issued, bsp.overall_grade, bsp.class_rank;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS GetUserBatchProgress");
    }
};

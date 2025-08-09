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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `GetBatchContentStructure`(
    IN p_batch_id CHAR(36),
    IN p_user_id CHAR(36)
)
BEGIN
    SELECT 
        m.module_id,
        m.title as module_title,
        m.description as module_description,
        m.sort_order as module_order,
        m.is_batch_specific,
        l.lesson_id,
        l.title as lesson_title,
        l.content_type,
        l.duration_minutes,
        l.sort_order as lesson_order,
        l.is_free_preview,
        l.scheduled_date,
        l.scheduled_time,
        l.availability_start,
        l.availability_end,
        COALESCE(blp.status, 'not_started') as progress_status,
        blp.progress_percentage,
        blp.completed_at,
        blp.grade,
        blp.instructor_feedback,
        -- Check if user can access this lesson
        CASE 
            WHEN l.is_free_preview = TRUE THEN TRUE
            WHEN be.enrollment_id IS NOT NULL THEN TRUE
            ELSE FALSE
        END as can_access,
        -- Check if lesson is available based on schedule
        CASE 
            WHEN l.availability_start IS NOT NULL AND l.availability_start > NOW() THEN FALSE
            WHEN l.availability_end IS NOT NULL AND l.availability_end < NOW() THEN FALSE
            ELSE TRUE
        END as is_available
    FROM modules m
    JOIN course_batches cb ON m.course_id = cb.course_id
    LEFT JOIN lessons l ON m.module_id = l.module_id AND l.is_published = TRUE 
        AND (l.batch_id IS NULL OR l.batch_id = p_batch_id)
    LEFT JOIN batch_lesson_progress blp ON l.lesson_id = blp.lesson_id AND blp.user_id = p_user_id AND blp.batch_id = p_batch_id
    LEFT JOIN batch_enrollments be ON cb.batch_id = be.batch_id AND be.user_id = p_user_id AND be.status = 'active'
    WHERE cb.batch_id = p_batch_id AND m.is_published = TRUE
        AND (m.batch_id IS NULL OR m.batch_id = p_batch_id)
    ORDER BY m.sort_order, l.sort_order;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS GetBatchContentStructure");
    }
};

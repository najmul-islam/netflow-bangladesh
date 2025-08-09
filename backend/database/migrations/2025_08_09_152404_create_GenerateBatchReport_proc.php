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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateBatchReport`(
    IN p_batch_id CHAR(36)
)
BEGIN
    -- Batch overview
    SELECT 
        cb.batch_id,
        cb.batch_name,
        cb.batch_code,
        cb.status,
        cb.start_date,
        cb.end_date,
        cb.max_students,
        cb.current_students,
        c.title as course_title,
        bs.total_enrollments,
        bs.active_enrollments,
        bs.completed_enrollments,
        bs.completion_rate,
        bs.average_rating,
        bs.certificates_issued,
        bs.average_attendance
    FROM course_batches cb
    JOIN courses c ON cb.course_id = c.course_id
    LEFT JOIN batch_statistics bs ON cb.batch_id = bs.batch_id
    WHERE cb.batch_id = p_batch_id;
    
    -- Student performance summary
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        be.enrollment_date,
        be.progress_percentage,
        be.attendance_percentage,
        be.status,
        bsp.overall_grade,
        bsp.class_rank,
        be.certificate_issued
    FROM batch_enrollments be
    JOIN users u ON be.user_id = u.user_id
    LEFT JOIN batch_student_performance bsp ON be.user_id = bsp.user_id AND be.batch_id = bsp.batch_id
    WHERE be.batch_id = p_batch_id
    ORDER BY bsp.class_rank ASC, be.enrollment_date ASC;
    
    -- Class attendance summary
    SELECT 
        bs.schedule_id,
        bs.title,
        bs.session_type,
        bs.start_datetime,
        bs.status,
        COUNT(DISTINCT ca.user_id) as total_attendees,
        COUNT(DISTINCT CASE WHEN ca.status = 'present' THEN ca.user_id END) as present_count,
        ROUND((COUNT(DISTINCT CASE WHEN ca.status = 'present' THEN ca.user_id END) / COUNT(DISTINCT ca.user_id)) * 100, 2) as attendance_percentage
    FROM batch_schedule bs
    LEFT JOIN class_attendance ca ON bs.schedule_id = ca.schedule_id
    WHERE bs.batch_id = p_batch_id
    GROUP BY bs.schedule_id, bs.title, bs.session_type, bs.start_datetime, bs.status
    ORDER BY bs.start_datetime;
    
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS GenerateBatchReport");
    }
};

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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `ScheduleBatchClass`(
    IN p_batch_id CHAR(36),
    IN p_title VARCHAR(255),
    IN p_session_type VARCHAR(50),
    IN p_start_datetime TIMESTAMP,
    IN p_end_datetime TIMESTAMP,
    IN p_meeting_platform VARCHAR(50),
    IN p_meeting_url TEXT,
    IN p_meeting_id VARCHAR(255),
    IN p_meeting_password VARCHAR(255),
    IN p_created_by CHAR(36),
    OUT p_schedule_id CHAR(36)
)
BEGIN
    DECLARE batch_exists INT DEFAULT 0;
    
    -- Check if batch exists
    SELECT COUNT(*) INTO batch_exists
    FROM course_batches 
    WHERE batch_id = p_batch_id;
    
    IF batch_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batch not found';
    END IF;
    
    -- Create schedule entry
    SET p_schedule_id = UUID();
    
    INSERT INTO batch_schedule (
        schedule_id, batch_id, title, session_type, start_datetime, end_datetime,
        meeting_platform, meeting_url, meeting_id, meeting_password, created_by
    ) VALUES (
        p_schedule_id, p_batch_id, p_title, p_session_type, p_start_datetime, p_end_datetime,
        p_meeting_platform, p_meeting_url, p_meeting_id, p_meeting_password, p_created_by
    );
    
    -- Auto-create attendance records for all enrolled students
    INSERT INTO class_attendance (schedule_id, user_id, status)
    SELECT p_schedule_id, be.user_id, 'absent'
    FROM batch_enrollments be
    WHERE be.batch_id = p_batch_id AND be.status = 'active';
    
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS ScheduleBatchClass");
    }
};

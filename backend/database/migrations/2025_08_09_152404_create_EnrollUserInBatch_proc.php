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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `EnrollUserInBatch`(
    IN p_user_id CHAR(36),
    IN p_batch_id CHAR(36),
    IN p_enrolled_by CHAR(36)
)
BEGIN
    DECLARE batch_max_students INT;
    DECLARE current_students_count INT;
    DECLARE batch_status_var VARCHAR(50);
    DECLARE enrollment_end_date_var TIMESTAMP;
    
    -- Get batch details
    SELECT max_students, current_students, status, enrollment_end_date
    INTO batch_max_students, current_students_count, batch_status_var, enrollment_end_date_var
    FROM course_batches 
    WHERE batch_id = p_batch_id;
    
    -- Check if batch exists and is open for enrollment
    IF batch_status_var IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batch not found';
    END IF;
    
    IF batch_status_var NOT IN ('open_for_enrollment', 'draft') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batch is not open for enrollment';
    END IF;
    
    -- Check enrollment deadline
    IF enrollment_end_date_var IS NOT NULL AND enrollment_end_date_var < NOW() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Enrollment deadline has passed';
    END IF;
    
    -- Check batch capacity
    IF batch_max_students IS NOT NULL AND current_students_count >= batch_max_students THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batch is full';
    END IF;
    
    -- Insert batch enrollment
    INSERT INTO batch_enrollments (user_id, batch_id, enrolled_by)
    VALUES (p_user_id, p_batch_id, p_enrolled_by);
    
    -- Log activity
    INSERT INTO batch_activity_logs (user_id, batch_id, activity_type, metadata)
    VALUES (p_user_id, p_batch_id, 'batch_access', JSON_OBJECT('enrolled_by', p_enrolled_by));
    
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS EnrollUserInBatch");
    }
};

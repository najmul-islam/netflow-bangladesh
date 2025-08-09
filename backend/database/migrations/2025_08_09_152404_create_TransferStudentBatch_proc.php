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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `TransferStudentBatch`(
    IN p_user_id CHAR(36),
    IN p_from_batch_id CHAR(36),
    IN p_to_batch_id CHAR(36),
    IN p_transfer_reason TEXT,
    IN p_transferred_by CHAR(36)
)
BEGIN
    DECLARE from_course_id CHAR(36);
    DECLARE to_course_id CHAR(36);
    DECLARE to_batch_max_students INT;
    DECLARE to_batch_current_students INT;
    
    -- Get course IDs for both batches
    SELECT cb1.course_id, cb2.course_id, cb2.max_students, cb2.current_students
    INTO from_course_id, to_course_id, to_batch_max_students, to_batch_current_students
    FROM course_batches cb1, course_batches cb2
    WHERE cb1.batch_id = p_from_batch_id AND cb2.batch_id = p_to_batch_id;
    
    -- Ensure both batches belong to the same course
    IF from_course_id != to_course_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot transfer between different courses';
    END IF;
    
    -- Check capacity of target batch
    IF to_batch_max_students IS NOT NULL AND to_batch_current_students >= to_batch_max_students THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target batch is full';
    END IF;
    
    -- Update existing enrollment
    UPDATE batch_enrollments 
    SET status = 'transferred',
        transfer_date = CURRENT_TIMESTAMP
    WHERE user_id = p_user_id AND batch_id = p_from_batch_id;
    
    -- Create new enrollment in target batch
    INSERT INTO batch_enrollments (
        user_id, batch_id, enrolled_by, transfer_from_batch_id, transfer_reason
    ) VALUES (
        p_user_id, p_to_batch_id, p_transferred_by, p_from_batch_id, p_transfer_reason
    );
    
    -- Log the transfer
    INSERT INTO batch_activity_logs (user_id, batch_id, activity_type, metadata)
    VALUES (p_user_id, p_to_batch_id, 'batch_access', 
            JSON_OBJECT('transferred_from', p_from_batch_id, 'reason', p_transfer_reason, 'transferred_by', p_transferred_by));
    
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS TransferStudentBatch");
    }
};

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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateCourseBatch`(
    IN p_course_id CHAR(36),
    IN p_batch_name VARCHAR(255),
    IN p_batch_code VARCHAR(50),
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_max_students INT,
    IN p_created_by CHAR(36),
    OUT p_batch_id CHAR(36)
)
BEGIN
    DECLARE batch_exists INT DEFAULT 0;
    DECLARE course_exists INT DEFAULT 0;
    
    -- Check if course exists and batches are enabled
    SELECT COUNT(*) INTO course_exists
    FROM courses 
    WHERE course_id = p_course_id AND enable_batches = TRUE AND status = 'published';
    
    IF course_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Course not found or batches not enabled';
    END IF;
    
    -- Check if batch code already exists for this course
    SELECT COUNT(*) INTO batch_exists
    FROM course_batches 
    WHERE course_id = p_course_id AND batch_code = p_batch_code;
    
    IF batch_exists > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batch code already exists for this course';
    END IF;
    
    -- Create new batch
    SET p_batch_id = UUID();
    
    INSERT INTO course_batches (
        batch_id, course_id, batch_name, batch_code, start_date, end_date, 
        max_students, created_by, status
    ) VALUES (
        p_batch_id, p_course_id, p_batch_name, p_batch_code, p_start_date, 
        p_end_date, p_max_students, p_created_by, 'draft'
    );
    
    -- Initialize batch statistics
    INSERT INTO batch_statistics (batch_id) VALUES (p_batch_id);
    
    -- Create default forums for the batch
    INSERT INTO batch_forums (batch_id, title, description, forum_type, created_by) VALUES
    (p_batch_id, 'General Discussion', 'General discussions for this batch', 'general', p_created_by),
    (p_batch_id, 'Announcements', 'Important announcements', 'announcements', p_created_by),
    (p_batch_id, 'Q&A', 'Questions and answers', 'q_and_a', p_created_by);
    
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS CreateCourseBatch");
    }
};

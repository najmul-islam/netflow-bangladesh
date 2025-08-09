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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `AutoCreateBatches`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE course_id_var CHAR(36);
    DECLARE course_title_var VARCHAR(255);
    DECLARE max_batch_size_var INT;
    DECLARE batch_interval_var INT;
    DECLARE pending_enrollments INT;
    DECLARE new_batch_name VARCHAR(255);
    DECLARE new_batch_code VARCHAR(50);
    DECLARE new_batch_id CHAR(36);
    
    DECLARE course_cursor CURSOR FOR 
        SELECT course_id, title, max_batch_size, batch_start_interval_days
        FROM courses 
        WHERE enable_batches = TRUE 
        AND auto_create_batches = TRUE 
        AND status = 'published';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN course_cursor;
    
    auto_batch_loop: LOOP
        FETCH course_cursor INTO course_id_var, course_title_var, max_batch_size_var, batch_interval_var;
        IF done THEN
            LEAVE auto_batch_loop;
        END IF;
        
        -- Count enrollments without active batches
        SELECT COUNT(*) INTO pending_enrollments
        FROM enrollments e
        WHERE e.course_id = course_id_var
        AND e.user_id NOT IN (
            SELECT be.user_id 
            FROM batch_enrollments be 
            JOIN course_batches cb ON be.batch_id = cb.batch_id 
            WHERE cb.course_id = course_id_var 
            AND be.status = 'active'
        );
        
        -- Create new batch if enough pending enrollments
        IF pending_enrollments >= max_batch_size_var THEN
            SET new_batch_id = UUID();
            SET new_batch_name = CONCAT(course_title_var, ' - ', DATE_FORMAT(NOW(), '%b %Y'));
            SET new_batch_code = CONCAT('AUTO-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', SUBSTRING(new_batch_id, 1, 8));
            
            INSERT INTO course_batches (
                batch_id, course_id, batch_name, batch_code, max_students,
                start_date, status, auto_generated, created_by
            ) VALUES (
                new_batch_id, course_id_var, new_batch_name, new_batch_code, max_batch_size_var,
                DATE_ADD(CURDATE(), INTERVAL batch_interval_var DAY), 'open_for_enrollment', TRUE,
                '00000000-0000-0000-0000-000000000001'
            );
            
            -- Initialize batch statistics
            INSERT INTO batch_statistics (batch_id) VALUES (new_batch_id);
        END IF;
        
    END LOOP;
    
    CLOSE course_cursor;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS AutoCreateBatches");
    }
};

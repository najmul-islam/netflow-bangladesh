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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateAllBatchStatistics`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE batch_id_var CHAR(36);
    DECLARE batch_cursor CURSOR FOR SELECT batch_id FROM course_batches;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN batch_cursor;
    
    update_loop: LOOP
        FETCH batch_cursor INTO batch_id_var;
        IF done THEN
            LEAVE update_loop;
        END IF;
        
        -- Update batch statistics
        INSERT INTO batch_statistics (
            batch_id, total_enrollments, active_enrollments, completed_enrollments, 
            dropped_enrollments, completion_rate, average_attendance
        )
        SELECT 
            batch_id_var,
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'dropped' THEN 1 END) as dropped,
            CASE WHEN COUNT(*) > 0 THEN (COUNT(CASE WHEN status = 'completed' THEN 1 END) / COUNT(*)) * 100 ELSE 0 END,
            COALESCE(AVG(attendance_percentage), 0)
        FROM batch_enrollments 
        WHERE batch_id = batch_id_var
        ON DUPLICATE KEY UPDATE
            total_enrollments = VALUES(total_enrollments),
            active_enrollments = VALUES(active_enrollments),
            completed_enrollments = VALUES(completed_enrollments),
            dropped_enrollments = VALUES(dropped_enrollments),
            completion_rate = VALUES(completion_rate),
            average_attendance = VALUES(average_attendance),
            last_updated = CURRENT_TIMESTAMP;
            
        -- Update student performance records
        INSERT INTO batch_student_performance (
            user_id, batch_id, total_classes, attended_classes, attendance_percentage
        )
        SELECT 
            be.user_id,
            batch_id_var,
            COUNT(DISTINCT bs.schedule_id) as total,
            COUNT(DISTINCT CASE WHEN ca.status = 'present' THEN ca.schedule_id END) as attended,
            CASE 
                WHEN COUNT(DISTINCT bs.schedule_id) > 0 
                THEN (COUNT(DISTINCT CASE WHEN ca.status = 'present' THEN ca.schedule_id END) / COUNT(DISTINCT bs.schedule_id)) * 100
                ELSE 0 
            END
        FROM batch_enrollments be
        LEFT JOIN batch_schedule bs ON be.batch_id = bs.batch_id AND bs.status = 'completed'
        LEFT JOIN class_attendance ca ON bs.schedule_id = ca.schedule_id AND ca.user_id = be.user_id
        WHERE be.batch_id = batch_id_var
        GROUP BY be.user_id
        ON DUPLICATE KEY UPDATE
            total_classes = VALUES(total_classes),
            attended_classes = VALUES(attended_classes),
            attendance_percentage = VALUES(attendance_percentage),
            last_updated = CURRENT_TIMESTAMP;
            
    END LOOP;
    
    CLOSE batch_cursor;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS UpdateAllBatchStatistics");
    }
};

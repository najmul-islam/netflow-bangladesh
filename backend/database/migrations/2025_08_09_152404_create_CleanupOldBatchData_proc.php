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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanupOldBatchData`(
    IN p_days_old INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE batch_id_var CHAR(36);
    DECLARE batch_cursor CURSOR FOR 
        SELECT batch_id 
        FROM course_batches 
        WHERE status = 'completed' 
        AND end_date < DATE_SUB(CURDATE(), INTERVAL p_days_old DAY);
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN batch_cursor;
    
    cleanup_loop: LOOP
        FETCH batch_cursor INTO batch_id_var;
        IF done THEN
            LEAVE cleanup_loop;
        END IF;
        
        -- Archive old activity logs (move to archive table or delete)
        DELETE FROM batch_activity_logs 
        WHERE batch_id = batch_id_var 
        AND created_at < DATE_SUB(CURDATE(), INTERVAL p_days_old DAY);
        
        -- Clean up old notifications
        DELETE FROM batch_notifications 
        WHERE batch_id = batch_id_var 
        AND created_at < DATE_SUB(CURDATE(), INTERVAL p_days_old DAY)
        AND is_read = TRUE;
        
    END LOOP;
    
    CLOSE batch_cursor;
    
    -- Clean up old email queue entries
    DELETE FROM batch_email_queue 
    WHERE status = 'sent' 
    AND sent_at < DATE_SUB(CURDATE(), INTERVAL p_days_old DAY);
    
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS CleanupOldBatchData");
    }
};

<?php
require_once('dbcon.php');

try {
    // Create a temporary table and copy data from the main table
    $tempTableSql = "CREATE TEMPORARY TABLE temp_merged_data_bbt AS SELECT * FROM merged_data_bbt";
    $con->exec($tempTableSql);

    // Function to rearrange the data in the temporary table
    function rearrangeData($con) {
        $sql = "SELECT * FROM temp_merged_data_bbt ORDER BY exam_date, exam_time";
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Maintain a record of the latest exam_time for each student_code within the same exam_date
        $latestTimes = array();

        // Iterate through the result set and rearrange data
        foreach ($result as $row) {
            $studentCode = $row['student_code'];
            $examDate = $row['exam_date'];
            $examTime = strtotime($row['exam_time']);

            // Check if we have a previous exam_time for the same student_code and exam_date
            if (isset($latestTimes[$studentCode][$examDate])) {
                $prevTime = $latestTimes[$studentCode][$examDate];

                // Check if the time difference is less than 4 hours
                if ($examTime - $prevTime < 4 * 3600) {
                    // Update the current row's exam_time in the temporary table
                    $newTime = date('H:i:s', $prevTime + 4 * 3600);
                    $updateSql = "UPDATE temp_merged_data_bbt SET exam_time = :newTime WHERE student_code = :studentCode AND exam_date = :examDate AND exam_time = :prevTime";
                    $updateStmt = $con->prepare($updateSql);
                    $updateStmt->bindParam(':newTime', $newTime);
                    $updateStmt->bindParam(':studentCode', $studentCode);
                    $updateStmt->bindParam(':examDate', $examDate);
                    $updateStmt->bindParam(':prevTime', $row['exam_time']);
                    $updateStmt->execute();
                }
            }

            // Update the latest exam_time for this student_code and exam_date
            $latestTimes[$studentCode][$examDate] = $examTime;
        }
        echo "Data rearranged successfully in the temporary table.";
    }

    // Call the function to rearrange the data in the temporary table
    rearrangeData($con);

    // Update the main table with data from the temporary table
    $updateMainTableSql = "UPDATE merged_data_bbt m
                           JOIN temp_merged_data_bbt t ON m.student_code = t.student_code 
                           SET m.exam_time = t.exam_time";
    $con->exec($updateMainTableSql);

    // Drop the temporary table
    $dropTempTableSql = "DROP TEMPORARY TABLE IF EXISTS temp_merged_data_bbt";
    $con->exec($dropTempTableSql);

    echo "Data updated in the main table.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

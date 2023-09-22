<?php
// Include your database connection
include 'dbcon.php';


if (isset($_POST['generate_schedule'])) {
    try {
        // Fetch data from enrollments and timeslots tables
        $fetchDataQuery = "SELECT
            e.enrol_id,
            e.student_code,
            e.subject_code,
            e.subject_name,
            e.group_name,
            e.lect_name,
            t.exam_day,
            t.exam_date,
            t.exam_time,
            t.venue_name,
            t.invigilator_name
        FROM enrollments_bbt e
        JOIN timeslot_bbt t ON e.group_name = t.group_name
                        AND e.subject_code = t.subject_code";  // Join based on subject_code and group_name

        $stmt = $con->prepare($fetchDataQuery);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Create a mapping of group_name to used exam_date and exam_time combinations
        $usedDateTimeMap = [];

        // Create a mapping of group_name to assigned subject codes
        $assignedSubjectsMap = [];

        // Create a mapping of group_name to last assigned exam_date
        $lastAssignedDateMap = [];

        // Insert fetched data into ExamSchedule table
        $insertExamScheduleQuery = "INSERT INTO ExamSchedule_bbt (enrol_id, exam_day, exam_date, exam_time, subject_code, subject_name, group_name, group_capacity, venue_name, lect_name, invigilator_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $con->prepare($insertExamScheduleQuery);

        foreach ($data as $row) {
            $groupKey = $row['group_name'];
            $examCombination = $row['exam_date'] . '-' . $row['exam_time'];

            // Check if this group has already been assigned this exam_combination and this subject code
            if (!isset($usedDateTimeMap[$groupKey][$examCombination]) && 
                (!isset($assignedSubjectsMap[$groupKey][$row['subject_code']]))) {

                $usedDateTimeMap[$groupKey][$examCombination] = true;
                $assignedSubjectsMap[$groupKey][$row['subject_code']] = true;

                $currentDate = $row['exam_date'];
                $lastAssignedDate = $lastAssignedDateMap[$groupKey] ?? null;

                // Check if the group has 5 or fewer subjects and if it's a new day for assignment
                if (count($assignedSubjectsMap[$groupKey]) <= 5 && $currentDate === $lastAssignedDate) {
                    // Skip one day by adding 1 day to the current date
                    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                }

                $lastAssignedDateMap[$groupKey] = $currentDate;

                // Check if this group has already been assigned a subject for the current exam_date
                if (!isset($usedDateTimeMap[$groupKey][$currentDate])) {
                    $usedDateTimeMap[$groupKey][$currentDate] = true;

                    // Insert the data into the ExamSchedule table
                    $insertStmt->execute([
                        $row['enrol_id'],
                        $row['exam_day'],
                        $currentDate,
                        $row['exam_time'],
                        $row['subject_code'],
                        $row['subject_name'],
                        $row['group_name'],
                        $row['venue_name'],
                        $row['lect_name'],
                        $row['invigilator_name']
                    ]);
                }
            }
        }

        echo "Data fetched and inserted into ExamSchedule successfully.";

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Exam Schedule Generator</title>
</head>
<body>
    <h1>Exam Schedule Generator</h1>

    <form action="" method="post">
        <input type="submit" name="generate_schedule" value="Process Exam Schedule">
    </form>
</body>
</html>

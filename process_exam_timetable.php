<!DOCTYPE html>
<html>
<head>
    <title>Merge and Insert Data</title>
</head>
<body>
    <h1>Merge and Insert Data</h1>
    
    <?php
    if (isset($_POST['submit'])) {
        // Include the database connection
        require_once 'dbcon.php';

        try {
            // Perform the data insertion into the existing merged_data_bbt table
            $insertDataQuery = "
                INSERT INTO merged_data_bbt (
                    student_code,
                    exam_day,
                    exam_date,
                    exam_time,
                    venue_name,
                    timeslot_group_name,
                    group_capacity,
                    timeslot_subject_code,
                    timeslot_subject_name,
                    timeslot_lect_name,
                    invigilator_name
                )
                SELECT
                    e.student_code,
                    t.exam_day,
                    t.exam_date,
                    t.exam_time,
                    t.venue_name,
                    t.group_name AS timeslot_group_name,
                    t.group_capacity,
                    t.subject_code AS timeslot_subject_code,
                    t.subject_name AS timeslot_subject_name,
                    t.lect_name AS timeslot_lect_name,
                    t.invigilator_name
                FROM
                    enrollments_bbt e
                JOIN
                    timeslot_bbt t
                ON
                    e.subject_code = t.subject_code;
            ";

            // Execute the data insertion query
            $con->exec($insertDataQuery);

            echo "Data inserted into 'merged_data_bbt' table successfully.";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

        // Close the database connection
        $con = null;
    }
    ?>

    <form action="" method="post">
        <input type="submit" name="submit" value="Merge and Insert Data">
    </form>
</body>
</html>

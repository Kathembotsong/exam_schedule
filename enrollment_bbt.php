<?php
require_once 'dbcon.php';

if (isset($_POST['submit'])) {
    $student_code = $_POST['student_code'];
    $subjects = $_POST['subjects'];
    $group = $_POST['group'];
    $lecturers = $_POST['lecturers'];

    try {
        // Check if the student exists
        $checkStudentQuery = "SELECT COUNT(*) FROM students_bbt WHERE student_code = :student_code";
        $stmt = $con->prepare($checkStudentQuery);
        $stmt->bindParam(':student_code', $student_code);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            // ...
// Inside the foreach loop
foreach ($subjects as $subject) {
    // Get the subject name based on the subject code
    $subjectNameQuery = "SELECT subject_name FROM subjects_bbt WHERE subject_code = :subject_code";
    $stmt = $con->prepare($subjectNameQuery);
    $stmt->bindParam(':subject_code', $subject);
    $stmt->execute();
    $subject_name = $stmt->fetchColumn();

    // Get the selected lecturer for the subject
    $lecturer = isset($lecturers[$subject]) ? $lecturers[$subject] : null;

    // Get the selected status for the subject
    $status = isset($_POST['status'][$subject]) ? $_POST['status'][$subject] : 'Normal';

    // Insert enrollment record with the selected status
    $sql = "INSERT INTO enrollments_bbt (student_code, subject_code, subject_name, group_name, lect_name, enrol_status) VALUES (:student_code, :subject_code, :subject_name, :group_name, :lect_name, :status)";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':student_code', $student_code);
    $stmt->bindParam(':subject_code', $subject);
    $stmt->bindParam(':subject_name', $subject_name);
    $stmt->bindParam(':group_name', $group);
    $stmt->bindParam(':lect_name', $lecturer);
    $stmt->bindParam(':status', $status); // Bind the status value here
    $stmt->execute();
}   echo "Enrollments added successfully!";
        } else {
            echo "Student with the provided code does not exist.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enrollment Form</title>
</head>
<body>
    <form action="" method="post">
        <label for="student_code">Enter Student Code:</label><br>
        <input type="text" name="student_code" id="student_code"><br><br>

        <label>Select Subjects:</label><br>
        <?php
        try {
            $sql = "SELECT subject_code, subject_name FROM subjects_bbt";
            $stmt = $con->prepare($sql);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<input type="checkbox" name="subjects[]" value="' . $row['subject_code'] . '">' . $row['subject_name'] . '<br>';
                echo 'Select Lecturer for ' . $row['subject_name'] . ': ';
                echo '<select name="lecturers[' . $row['subject_code'] . ']">';
                
                $lecturerQuery = "SELECT lect_name FROM lecturers_bbt";
                $lecturerStmt = $con->prepare($lecturerQuery);
                $lecturerStmt->execute();
                
                while ($lecturerRow = $lecturerStmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . $lecturerRow['lect_name'] . '">' . $lecturerRow['lect_name'] . '</option>';
                }
                
                echo '</select><br>';
                
                // Add a status dropdown for each subject
                echo 'Select Status for ' . $row['subject_name'] . ': ';
                echo '<select name="status[' . $row['subject_code'] . ']">';
                echo '<option value="Normal">Normal</option>';
                echo '<option value="Special">Special</option>';
                echo '</select><br>';
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        ?>

        <br><br>
        <label for="group">Select Group:</label><br>
        <select name="group" id="group">
            <?php
            try {
                $sql = "SELECT group_name FROM group_bbt";
                $stmt = $con->prepare($sql);
                $stmt->execute();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . $row['group_name'] . '">' . $row['group_name'] . '</option>';
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            ?>
        </select>
        <br><br>

        <input type="submit" name="submit" value="Enroll">
    </form>
</body>
</html>


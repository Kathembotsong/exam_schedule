<?php
// Include the database connection
include('dbcon.php');

// Check if the form is submitted
if (isset($_POST['submit'])) {
    try {
        // Get the time availability from the user input
        $time_avail = $_POST['time_avail'];

        // Prepare the SQL statement for inserting time availability
        $sql = "INSERT INTO time_availability (time_avail) VALUES (:time_avail)";

        // Use PDO to prepare the statement and execute it
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':time_avail', $time_avail);
        $stmt->execute();

        echo "Time availability inserted successfully!";
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insert Time Availability</title>
</head>
<body>
    <h1>Insert Time Availability</h1>

    <form method="post">
        <label for="time_avail">Time Availability:</label>
        <input type="time" name="time_avail" id="time_avail" required>
        <input type="submit" name="submit" value="Insert">
    </form>
</body>
</html>

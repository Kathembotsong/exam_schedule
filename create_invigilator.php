<?php require_once 'dbcon.php';?>

<?php
// Process form submission
if(isset($_POST['submit'])) {
    $invigilator_code = $_POST['invigilator_code'];
    $invigilator_name = $_POST['invigilator_name'];

    $insertQuery = "INSERT INTO Invigilator (invigilator_code, invigilator_name) VALUES (:invigilator_code, :invigilator_name)";
    
    $stmt = $con->prepare($insertQuery);
    $stmt->bindParam(':invigilator_code', $invigilator_code);
    $stmt->bindParam(':invigilator_name', $invigilator_name);

    try {
        $stmt->execute();
        echo "Invigilator added successfully!";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invigilator Form</title>
</head>
<body>
    <form action="" method="post">
        <label for="invigilator_code">Invigilator Code:</label><br>
        <input type="text" name="invigilator_code" required><br><br>

        <label for="invigilator_name">Invigilator Name:</label><br>
        <input type="text" name="invigilator_name" required><br><br>

        <input type="submit" name="submit" value="Add Invigilator">
    </form>
</body>
</html>

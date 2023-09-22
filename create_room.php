<?php   require_once 'dbcon.php' ?>

<?php
if(isset($_POST['submit'])) {
    $roomName = $_POST['room_name'];
    $roomCapacity = $_POST['room_capacity'];

    try {
        $sql = "INSERT INTO Room (room_name, room_capacity) VALUES (?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->execute([$roomName, $roomCapacity]);
        echo "Room added successfully!";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Room</title>
</head>
<body>
    <h2>Add Room</h2>
    <form action="" method="post">
        <label for="room_name">Room Name:</label><br>
        <input type="text" name="room_name" id="room_name"><br><br>

        <label for="room_capacity">Room Capacity:</label><br>
        <input type="number" name="room_capacity" id="room_capacity"><br><br>

        <input type="submit" name="submit" value="Add Room">
    </form>
</body>
</html>

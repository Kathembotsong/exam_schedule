<?php
require_once 'dbcon.php';

// Fetch available supervisors (invigilators), venues, and groups from the database
$supervisorQuery = "SELECT invigilator_name FROM Invigilators_bbt";
$supervisors = $con->query($supervisorQuery)->fetchAll(PDO::FETCH_COLUMN);

$venueQuery = "SELECT venue_name, venue_capacity FROM exam_venue";
$venuesData = $con->query($venueQuery)->fetchAll(PDO::FETCH_ASSOC);

$subjectQuery = "SELECT subject_code, subject_name, group_name, lect_name FROM enrollments_bbt";
$subjectsData = $con->query($subjectQuery)->fetchAll(PDO::FETCH_ASSOC);

// Extract unique subject names and associated group names and lecturers
$subjects = [];
foreach ($subjectsData as $subjectData) {
    $subjectCode = $subjectData['subject_code'];
    $subjectName = $subjectData['subject_name'];
    $groupName = $subjectData['group_name'];
    $lectName = $subjectData['lect_name'];

    if (!isset($subjects[$subjectName])) {
        $subjects[$subjectName] = [
            'subject_code' => $subjectCode,
            'groups' => [],
            'lect_name' => $lectName,
        ];
    }

    $subjects[$subjectName]['groups'][] = $groupName;
}

// Initialize a flag to check if any time slots have been added successfully
$timeSlotsAdded = false;

if (isset($_POST['submit'])) {
    if (
        isset($_POST['subject_name']) &&
        isset($_POST['exam_time']) &&
        isset($_POST['lect_name']) &&
        isset($_POST['selected_groups']) &&
        isset($_POST['exam_date']) // Ensure exam_date is set
    ) {
        $subjectName = $_POST['subject_name'];

        // Check if the selected subject_name exists in the $subjects array
        if (array_key_exists($subjectName, $subjects)) {
            $selectedSubjectCode = $subjects[$subjectName]['subject_code'];
            $examTime = $_POST['exam_time'];
            $chiefInvigilator = $_POST['lect_name'];
            $examDate = $_POST['exam_date'];

            $selectedSubjectData = $subjects[$subjectName];
            $lectName = $selectedSubjectData['lect_name'];
            $selectedGroups = $_POST['selected_groups'];

            foreach ($selectedGroups as $selectedGroup) {
                $examDay = date('l', strtotime($examDate));
                $randomSupervisor = $supervisors[array_rand($supervisors)];

                $assignedVenue = null;

                // Initialize a flag to check if a suitable venue is found
                $venueFound = false;

                // Shuffle the venues array to randomize venue selection
                shuffle($venuesData);

                foreach ($venuesData as $venueData) {
                    $venueName = $venueData['venue_name'];
                    $venueCapacity = intval($venueData['venue_capacity']);

                    // Calculate the total capacity used by existing time slots for this venue, day, and time
                    $existingCapacityUsed = calculateExistingCapacityUsed($con, $examDay, $examDate, $examTime, $venueName);

                    // Calculate the available capacity for this venue
                    $availableCapacity = $venueCapacity - $existingCapacityUsed;

                    // Check if group size is less than or equal to available capacity
                    if (getGroupCapacity($con, $selectedGroup) <= $availableCapacity) {
                        $assignedVenue = $venueName;
                        $venueFound = true;

                        break; // Found a suitable venue, exit the loop
                    }
                }

                if ($venueFound) {
                    // Insert the time slot with the selected venue, group, and other details
                    $sql = "INSERT INTO timeslot_bbt (exam_day, exam_date, exam_time, venue_name, group_name, group_capacity, subject_code, subject_name, lect_name, invigilator_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $con->prepare($sql);
                    $stmt->execute([$examDay, $examDate, $examTime, $assignedVenue, $selectedGroup, getGroupCapacity($con, $selectedGroup), $selectedSubjectCode, $subjectName, $lectName, $randomSupervisor]);

                    // Set the flag to true when at least one time slot is added successfully
                    $timeSlotsAdded = true;
                } else {
                    echo "No suitable venue with enough capacity has been found for group: $selectedGroup<br>";
                }
            }

            // Display "Time slots added successfully!" only if at least one time slot is added
            if ($timeSlotsAdded) {
                echo "Time slots added successfully!";
            }
        } else {
            echo "Selected subject_name not found in the database.";
        }
    } else {
        echo "Please fill out all the required fields.";
    }
}

// Function to get the group capacity
function getGroupCapacity($con, $group) {
    $sql = "SELECT group_capacity FROM group_bbt WHERE group_name = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$group]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return intval($result['group_capacity']);
}

// Function to calculate the total capacity used by existing time slots for a specific venue, date, and time
function calculateExistingCapacityUsed($con, $examDay, $examDate, $examTime, $venueName) {
    $sql = "SELECT SUM(group_capacity) AS capacity_used 
            FROM timeslot_bbt
            WHERE exam_day = ? AND exam_date = ? AND exam_time = ? AND venue_name = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$examDay, $examDate, $examTime, $venueName]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return intval($result['capacity_used']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Time Slot</title>
    <script>
        function updateDay() {
            var examDate = new Date(document.getElementById("exam_date").value);
            var days = ['Sunday','Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            var day = days[examDate.getDay()];
            document.getElementById("exam_day").value = day;
        }

        function populateFields(select) {
            var selectedSubjectName = select.value;
            var groupInput = document.getElementById("selected_groups");
            var lectNameInput = document.getElementById("lect_name");

            var subjectData = <?php echo json_encode($subjects); ?>;
            var subjectInfo = subjectData[selectedSubjectName];

            if (subjectInfo) {
                var groups = subjectInfo.groups;
                var lectName = subjectInfo.lect_name;

                groupInput.innerHTML = '';
                for (var i = 0; i < groups.length; i++) {
                    var option = document.createElement("option");
                    option.text = groups[i];
                    option.value = groups[i];
                    groupInput.add(option);
                }

                lectNameInput.value = lectName;
            } else {
                // Handle the case where the selected subject name is not found
                groupInput.innerHTML = '';
                lectNameInput.value = '';
                alert("Selected subject_name not found in the database.");
            }
        }
    </script>
</head>
<body>
    <form action="" method="post">
        <label for="subject_name">Select Subject Name:</label>
        <select name="subject_name" id="subject_name" required onchange="populateFields(this)">
            <?php
            foreach ($subjects as $subjectName => $subjectInfo) {
                echo '<option value="' . $subjectName . '">' . $subjectName . '</option>';
            }
            ?>
        </select><br><br>
        
        <label for="exam_date">Select Exam Date:</label>
        <input type="date" name="exam_date" id="exam_date" onchange="updateDay()" required><br><br>
        
        <label for="exam_day">Exam Day:</label>
        <input type="text" name="exam_day" id="exam_day" readonly><br><br>
        
        <label for="exam_time">Set Exam Time:</label>
        <input type="time" name="exam_time" id="exam_time" required><br><br>

        <label for="lect_name">Chief Invigilator:</label><br>
        <input type="text" name="lect_name" id="lect_name"><br><br>

        <label>Select Groups:</label><br>
        <select name="selected_groups[]" id="selected_groups"></select><br><br>
            
        <input type="submit" name="submit" value="Add Time Slot">
    </form>
</body>
</html>

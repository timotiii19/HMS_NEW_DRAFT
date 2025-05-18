<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
include('../../config/db.php');


// Handle Add
if (isset($_POST['add'])) {
    $patientID = $_POST['PatientID'];
    $visitorName = $_POST['VisitorName'];
    $relationship = $_POST['Relationship'];
    $visitDateTime = $_POST['VisitDateTime'];
    $locationID = $_POST['LocationID'];
    $contactNumber = $_POST['ContactNumber'];

    $sql = "INSERT INTO Visitor (PatientID, VisitorName, Relationship, VisitDateTime, LocationID, ContactNumber)
            VALUES ('$patientID', '$visitorName', '$relationship', '$visitDateTime', '$locationID', '$contactNumber')";
    mysqli_query($conn, $sql);
    header("Location: visitor.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM Visitor WHERE VisitorID = $id");
    header("Location: visitor.php");
    exit();
}

// Handle Edit/Update
if (isset($_POST['update'])) {
    $visitorID = $_POST['VisitorID'];
    $patientID = $_POST['PatientID'];
    $visitorName = $_POST['VisitorName'];
    $relationship = $_POST['Relationship'];
    $visitDateTime = $_POST['VisitDateTime'];
    $locationID = $_POST['LocationID'];
    $contactNumber = $_POST['ContactNumber'];

    $sql = "UPDATE Visitor SET 
                PatientID = '$patientID',
                VisitorName = '$visitorName',
                Relationship = '$relationship',
                VisitDateTime = '$visitDateTime',
                LocationID = '$locationID',
                ContactNumber = '$contactNumber'
            WHERE VisitorID = $visitorID";
    mysqli_query($conn, $sql);
    header("Location: visitor.php");
    exit();
}

// Fetch all visitors
$result = mysqli_query($conn, "SELECT * FROM Visitor");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Visitors</title>
    <link rel="stylesheet" href="../../assets/style.css"> <!-- Optional CSS -->
</head>
<body>
<h2>Manage Visitors</h2>

<!-- Add Visitor Form -->
<form method="POST" action="visitor.php">
    <h3>Add Visitor</h3>
    <label>Patient ID:</label><input type="number" name="PatientID" required><br>
    <label>Visitor Name:</label><input type="text" name="VisitorName" required><br>
    <label>Relationship:</label><input type="text" name="Relationship" required><br>
    <label>Visit Date & Time:</label><input type="datetime-local" name="VisitDateTime" required><br>
    <label>Location ID:</label><input type="number" name="LocationID" required><br>
    <label>Contact Number:</label><input type="text" name="ContactNumber" required><br>
    <input type="submit" name="add" value="Add Visitor">
</form>

<hr>

<!-- Visitor Table -->
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th><th>Patient ID</th><th>Name</th><th>Relationship</th>
        <th>Visit DateTime</th><th>Location ID</th><th>Contact</th><th>Actions</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <form method="POST" action="visitor.php">
            <input type="hidden" name="VisitorID" value="<?= $row['VisitorID'] ?>">
            <td><?= $row['VisitorID'] ?></td>
            <td><input type="number" name="PatientID" value="<?= $row['PatientID'] ?>"></td>
            <td><input type="text" name="VisitorName" value="<?= $row['VisitorName'] ?>"></td>
            <td><input type="text" name="Relationship" value="<?= $row['Relationship'] ?>"></td>
            <td><input type="datetime-local" name="VisitDateTime" value="<?= date('Y-m-d\TH:i', strtotime($row['VisitDateTime'])) ?>"></td>
            <td><input type="number" name="LocationID" value="<?= $row['LocationID'] ?>"></td>
            <td><input type="text" name="ContactNumber" value="<?= $row['ContactNumber'] ?>"></td>
            <td>
                <input type="submit" name="update" value="Update">
                <a href="visitor.php?delete=<?= $row['VisitorID'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </form>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>

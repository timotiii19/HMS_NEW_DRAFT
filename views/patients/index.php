<?php
session_start();
include('../../config/db.php');

// Redirect if user is not an admin
if (!isset($_SESSION['username']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: ../../auth/login.php");
    exit();
}


include('../../includes/doctor_sidebar.php');

// Fetch patients
function getPatients($conn) {
    $query = "SELECT * FROM patients";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$patients = getPatients($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Management</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css"> <!-- Path to your CSS -->
</head>
<body>
<div class="content">
    <h2>Patient Management</h2>

    <a href="create.php" class="btn">+ Add New Patient</a>

    <hr>

    <table>
        <thead>
            <tr>
                <th>Patient ID</th>
                <th>Name</th>
                <th>Date of Birth</th>
                <th>Sex</th>
                <th>Address</th>
                <th>Patient Type</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($patients) > 0): ?>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= $patient['PatientID'] ?></td>
                        <td><?= $patient['Name'] ?></td>
                        <td><?= $patient['DateOfBirth'] ?></td>
                        <td><?= $patient['Sex'] ?></td>
                        <td><?= $patient['Address'] ?></td>
                        <td>
                            <a href="inpatients/create.php?patient_id=<?= $patient['PatientID'] ?>" class="btn btn-sm btn-primary">Inpatient</a>
                            <a href="outpatients/create.php?patient_id=<?= $patient['PatientID'] ?>" class="btn btn-sm btn-success">Outpatient</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No patients found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>

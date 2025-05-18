<?php
session_start();
include('../../../config/db.php');

// Redirect if user is not logged in or not admin
if (!isset($_SESSION['username']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: ../../auth/login.php");
    exit();
}


include('../../../includes/sidebar.php');

// Fetch dropdown options
$doctors = $conn->query("SELECT DoctorID, DoctorName FROM doctors");
$departments = $conn->query("SELECT DepartmentID, DepartmentName FROM departments");
$locations = $conn->query("SELECT LocationID, Building, RoomNumber FROM locations");

// Fetch inpatient records with JOINs for proper names
$sql = "
    SELECT i.PatientID, d.DoctorName, dept.DepartmentName, 
           CONCAT(l.Building, ' - Room ', l.RoomNumber) AS LocationName, 
           i.Availability, i.MedicalRecord
    FROM inpatients i
    LEFT JOIN doctors d ON i.DoctorID = d.DoctorID
    LEFT JOIN departments dept ON i.DepartmentID = dept.DepartmentID
    LEFT JOIN locations l ON i.LocationID = l.LocationID
";
$result = $conn->query($sql);
$inpatientRecords = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Inpatient</title>
    <link rel="stylesheet" href="../../../css/style.css">
</head>
<body>

<div class="content">
    <h2>Assign Inpatient</h2>

    <form method="POST" action="../../controllers/InpatientController.php" class="form-container">
        <input type="hidden" name="PatientID" value="<?= htmlspecialchars($_GET['patient_id'] ?? '') ?>">

        <select name="DoctorID" required>
            <option value="">-- Select Doctor --</option>
            <?php while ($doctor = $doctors->fetch_assoc()): ?>
                <option value="<?= $doctor['DoctorID'] ?>"><?= htmlspecialchars($doctor['DoctorName']) ?></option>
            <?php endwhile; ?>
        </select>

        <select name="DepartmentID" required>
            <option value="">-- Select Department --</option>
            <?php while ($dept = $departments->fetch_assoc()): ?>
                <option value="<?= $dept['DepartmentID'] ?>"><?= htmlspecialchars($dept['DepartmentName']) ?></option>
            <?php endwhile; ?>
        </select>

        <select name="LocationID" required>
            <option value="">-- Select Location --</option>
            <?php while ($loc = $locations->fetch_assoc()): ?>
                <option value="<?= $loc['LocationID'] ?>">
                    <?= htmlspecialchars($loc['Building'] . ' - Room ' . $loc['RoomNumber']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="text" name="Availability" placeholder="Availability (Occupied/Unoccupied)" required>
        <textarea name="MedicalRecord" placeholder="Enter medical record..." required></textarea>
        <button type="submit">Save Inpatient</button>
    </form>

    <hr>

    <h3>Assigned Inpatients</h3>
    <table>
        <thead>
            <tr>
                <th>Patient ID</th>
                <th>Doctor</th>
                <th>Department</th>
                <th>Location</th>
                <th>Availability</th>
                <th>Medical Record</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($inpatientRecords) > 0): ?>
                <?php foreach ($inpatientRecords as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['PatientID']) ?></td>
                        <td><?= htmlspecialchars($record['DoctorName']) ?></td>
                        <td><?= htmlspecialchars($record['DepartmentName']) ?></td>
                        <td><?= htmlspecialchars($record['LocationName']) ?></td>
                        <td><?= htmlspecialchars($record['Availability']) ?></td>
                        <td><?= htmlspecialchars($record['MedicalRecord']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No inpatients assigned.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

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

// Fetch outpatient records with JOINs
$sql = "
    SELECT o.PatientID, d.DoctorName, dept.DepartmentName, o.VisitDate, o.Reason
    FROM outpatients o
    LEFT JOIN doctors d ON o.DoctorID = d.DoctorID
    LEFT JOIN departments dept ON o.DepartmentID = dept.DepartmentID
";
$result = $conn->query($sql);
$outpatientRecords = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Outpatient</title>
    <link rel="stylesheet" href="../../../css/style.css">
</head>
<body>

<div class="content">
    <h2>Assign Outpatient</h2>

    <form method="POST" action="../../controllers/OutpatientController.php" class="form-container">
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

        <input type="datetime-local" name="VisitDate" required>
        <textarea name="Reason" placeholder="Enter reason for visit..." required></textarea>
        <button type="submit">Save Outpatient</button>
    </form>

    <hr>

    <h3>Assigned Outpatients</h3>
    <table>
        <thead>
            <tr>
                <th>Patient ID</th>
                <th>Doctor</th>
                <th>Department</th>
                <th>Visit Date</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($outpatientRecords) > 0): ?>
                <?php foreach ($outpatientRecords as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['PatientID']) ?></td>
                        <td><?= htmlspecialchars($record['DoctorName']) ?></td>
                        <td><?= htmlspecialchars($record['DepartmentName']) ?></td>
                        <td><?= htmlspecialchars($record['VisitDate']) ?></td>
                        <td><?= htmlspecialchars($record['Reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No outpatients assigned.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
session_start();
include('../../config/db.php');
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

// Insert new outpatients from doctorschedule where Status = 'Outpatient'
$sql = "
    SELECT PatientID, DoctorID, LocationID, StartTime
    FROM doctorschedule
    WHERE Status = 'Outpatients'
";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patientID = $row['PatientID'];
        $doctorID = $row['DoctorID'];
        $departmentID = $row['LocationID']; // Assuming LocationID maps to DepartmentID
        $visitDate = $row['StartTime'];
        $reason = "N/A"; // Placeholder, can be updated later

        // Check if already inserted
        $check = $conn->prepare("SELECT * FROM outpatients WHERE PatientID = ? AND DoctorID = ?");
        $check->bind_param("ii", $patientID, $doctorID);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows === 0) {
            $stmt = $conn->prepare("
                INSERT INTO outpatients 
                (PatientID, DoctorID, DepartmentID, VisitDate, Reason)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiss", $patientID, $doctorID, $departmentID, $visitDate, $reason);
            $stmt->execute();
            $stmt->close();
        }
        $check->close();
    }
}

// Fetch all outpatients to display
$outpatients = $conn->query("
    SELECT o.OutpatientID, p.Name AS PatientName, d.DoctorName, o.VisitDate, o.Reason
    FROM outpatients o
    JOIN patients p ON o.PatientID = p.PatientID
    JOIN doctor d ON o.DoctorID = d.DoctorID
    ORDER BY o.VisitDate DESC
");
?>

<!-- Content Area -->
<div class="content">
    <div class="container">
        <h2>Current Outpatients</h2>
        <table border="1" cellpadding="10">
            <tr>
                <th>Outpatient ID</th>
                <th>Patient Name</th>
                <th>Doctor Name</th>
                <th>Visit Date</th>
                <th>Reason</th>
            </tr>
            <?php while ($row = $outpatients->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['OutpatientID']) ?></td>
                    <td><?= htmlspecialchars($row['PatientName']) ?></td>
                    <td><?= htmlspecialchars($row['DoctorName']) ?></td>
                    <td><?= htmlspecialchars($row['VisitDate']) ?></td>
                    <td><?= htmlspecialchars($row['Reason']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<?php include('../../includes/footer.php'); ?>

<!-- Style Fixes -->
<style>
    body {
   background-color:rgb(255, 255, 255); /* Light background color */
     }  
    .content {
        margin-left: 210px;
        margin-top: 40px;
        padding: 20px;
        background-color: #fff;
        min-height: 100vh;
    }

    .container h2 {
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #f9f9f9;
    }

    table th, table td {
        padding: 12px;
        text-align: left;
    }

    table th {
        background-color: #4CAF50;
        color: white;
    }

    table tr:nth-child(even) {
        background-color: #f2f2f2;
    }
</style>
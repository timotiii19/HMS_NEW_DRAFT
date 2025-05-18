<?php
session_start();
include('../../config/db.php');
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

// Insert new inpatients from doctorschedule where Status = 'Inpatient'
$sql = "
    SELECT PatientID, DoctorID, LocationID, EndTime
    FROM doctorschedule
    WHERE Status = 'Inpatient'
";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patientID = $row['PatientID'];
        $doctorID = $row['DoctorID'];
        $locationID = $row['LocationID'];
        $admissionDate = $row['EndTime'];

        // Check if already inserted
        $check = $conn->prepare("SELECT * FROM inpatients WHERE PatientID = ? AND DoctorID = ?");
        $check->bind_param("ii", $patientID, $doctorID);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows === 0) {
            $stmt = $conn->prepare("
                INSERT INTO inpatients 
                (PatientID, DoctorID, LocationID, AdmissionDate, DischargeDate, MedicalRecord, AssignedLocationID)
                VALUES (?, ?, ?, ?, NULL, NULL, NULL)
            ");
            $stmt->bind_param("iiis", $patientID, $doctorID, $locationID, $admissionDate);
            $stmt->execute();
            $stmt->close();
        }
        $check->close();
    }
}

// Fetch all inpatients to display
$inpatients = $conn->query("
    SELECT i.InpatientID, p.Name AS PatientName, d.DoctorName, i.AdmissionDate, i.DischargeDate
    FROM inpatients i
    JOIN patients p ON i.PatientID = p.PatientID
    JOIN doctor d ON i.DoctorID = d.DoctorID
    ORDER BY i.AdmissionDate DESC
");
?>

<!-- Content Area -->
<div class="content">
    <div class="container">
        <h2>Current Inpatients</h2>
        <table border="1" cellpadding="10">
            <tr>
                <th>Inpatient ID</th>
                <th>Patient Name</th>
                <th>Doctor Name</th>
                <th>Admission Date</th>
                <th>Discharge Date</th>
            </tr>
            <?php while ($row = $inpatients->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['InpatientID']) ?></td>
                    <td><?= htmlspecialchars($row['PatientName']) ?></td>
                    <td><?= htmlspecialchars($row['DoctorName']) ?></td>
                    <td><?= htmlspecialchars($row['AdmissionDate']) ?></td>
                    <td><?= $row['DischargeDate'] ?? 'Pending' ?></td>
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
        margin-left: 210px; /* Align with sidebar width */
        margin-top: 40px;   /* Align with header height */
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
        background-color: #eb6d9b;
        color: white;
    }

    table tr:nth-child(even) {
        background-color: #f2f2f2;
    }
</style>
<?php
session_start();
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/login.php");
    exit();
}

include('../../config/db.php');

// Updated query: fetch latest vitals and nurse name
$query = "
    SELECT p.*, 
           i.PatientID AS inpatient_id,
           o.PatientID AS outpatient_id,
           CASE 
               WHEN i.PatientID IS NOT NULL THEN 'Inpatient'
               WHEN o.PatientID IS NOT NULL THEN 'Outpatient'
               ELSE 'Unknown'
           END AS PatientType,
           v.Temperature,
           v.BloodPressure,
           v.Pulse,
           n.Name AS LastUpdatedNurseName
    FROM patients p
    LEFT JOIN inpatients i ON p.PatientID = i.PatientID
    LEFT JOIN outpatients o ON p.PatientID = o.PatientID
    LEFT JOIN (
        SELECT pv1.*
        FROM patientvitals pv1
        INNER JOIN (
            SELECT PatientID, MAX(RecordedAt) AS Latest
            FROM patientvitals
            GROUP BY PatientID
        ) pv2 ON pv1.PatientID = pv2.PatientID AND pv1.RecordedAt = pv2.Latest
    ) v ON p.PatientID = v.PatientID
    LEFT JOIN nurse n ON v.NurseID = n.NurseID
    ORDER BY p.Name ASC
";

$result = $conn->query($query);
$patients = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>View Patients</title>
    <link rel="stylesheet" href="../../css/style.css" />
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; }
        .content { padding: 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        button.view-btn {
            background-color: #6f42c1; color: white; border: none;
            border-radius: 6px; padding: 8px 16px; cursor: pointer;
        }
        button.view-btn:hover { background-color: #512da8; }
        .modal {
            position: fixed; z-index: 999; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto;
            background-color: rgba(0,0,0,0.5); display: none;
            justify-content: center; align-items: center;
        }
        .modal-content {
            border: 2px solid purple; border-radius: 12px;
            padding: 40px; background-color: #fff;
            max-width: 500px; width: 90%; text-align: center;
            box-shadow: 0 0 12px rgba(0,0,0,0.05); position: relative;
        }
        .close {
            position: absolute; top: 15px; right: 20px;
            font-size: 28px; font-weight: bold;
            color: #888; cursor: pointer;
        }
        .close:hover { color: #000; }
        .profile-img {
            width: 100px; height: 100px; margin: 0 auto 30px;
            border-radius: 50%; background-color: #f0f0f0;
            display: flex; justify-content: center; align-items: center;
        }
        .profile-img img { width: 60px; height: 60px; }
        .info-row {
            display: flex; justify-content: space-between;
            margin: 12px 0; font-size: 16px; color: #555;
        }
        .info-row strong { font-weight: 600; color: #444; }
    </style>
</head>
<body>

<div class="content">
    <h2>Patients List</h2>

    <table>
        <thead>
            <tr>
                <th>Patient ID</th>
                <th>Name</th>
                <th>Temperature (°C)</th>
                <th>Blood Pressure</th>
                <th>Pulse (bpm)</th>
                <th>Patient Type</th>
                <th>Nurse</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($patients) === 0): ?>
                <tr><td colspan="8" style='text-align: center; font-style: italic; color: #666;'>No patients found.</td></tr>
            <?php else: ?>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= htmlspecialchars($patient['PatientID']) ?></td>
                        <td><?= htmlspecialchars($patient['Name']) ?></td>
                        <td><?= htmlspecialchars($patient['Temperature'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($patient['BloodPressure'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($patient['Pulse'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($patient['PatientType']) ?></td>
                        <td><?= htmlspecialchars($patient['LastUpdatedNurseName'] ?? '-') ?></td>
                        <td>
                            <button class="view-btn" 
                                onclick="openModal(
                                    '<?= htmlspecialchars($patient['PatientID']) ?>',
                                    '<?= htmlspecialchars($patient['Name']) ?>',
                                    '<?= htmlspecialchars($patient['Sex']) ?>',
                                    '<?= htmlspecialchars($patient['Temperature'] ?? 'N/A') ?>',
                                    '<?= htmlspecialchars($patient['BloodPressure'] ?? 'N/A') ?>',
                                    '<?= htmlspecialchars($patient['Pulse'] ?? 'N/A') ?>',
                                    '<?= htmlspecialchars($patient['PatientType']) ?>',
                                    '<?= htmlspecialchars($patient['LastUpdatedNurseName'] ?? '-') ?>'
                                )">View Details</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="patientModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>

        <div class="profile-img">
            <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Profile Icon" />
        </div>

        <div class="info-row"><strong>Patient ID:</strong> <span id="modalPatientID"></span></div>
        <div class="info-row"><strong>Name:</strong> <span id="modalName"></span></div>
        <div class="info-row"><strong>Gender:</strong> <span id="modalGender"></span></div>
        <div class="info-row"><strong>Temperature:</strong> <span id="modalTemp"></span></div>
        <div class="info-row"><strong>Blood Pressure:</strong> <span id="modalBP"></span></div>
        <div class="info-row"><strong>Pulse:</strong> <span id="modalPulse"></span></div>
        <div class="info-row"><strong>Patient Type:</strong> <span id="modalType"></span></div>
        <div class="info-row"><strong>Recent Nurse:</strong> <span id="modalNurse"></span></div>
    </div>
</div>

<script>
    
function openModal(id, name, sex, temp, bp, pulse, type, nurse) {
    console.log("Sex passed:", sex); // Optional: Debug check

    let genderStr = 'Female'; // default to Female
    const upperSex = (sex || '').toUpperCase();
    if (upperSex === 'M') genderStr = 'Male';

    document.getElementById('modalPatientID').textContent = id || 'N/A';
    document.getElementById('modalName').textContent = name || 'N/A';
    document.getElementById('modalGender').textContent = genderStr;
    document.getElementById('modalTemp').textContent = temp || 'N/A';
    document.getElementById('modalBP').textContent = bp || 'N/A';
    document.getElementById('modalPulse').textContent = pulse || 'N/A';
    document.getElementById('modalType').textContent = type || 'Unknown';
    document.getElementById('modalNurse').textContent = nurse || '-';

    document.getElementById('patientModal').style.display = 'flex';
}



function closeModal() {
    document.getElementById('patientModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('patientModal');
    if (event.target === modal) {
        closeModal();
    }
};
</script>

</body>
</html>

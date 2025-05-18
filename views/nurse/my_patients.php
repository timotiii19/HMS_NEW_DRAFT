<?php
session_start();
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
// Ensure the nurse is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/nurse_login.php");
    exit();
}

include('../../config/db.php');

// Get the nurse ID from session
$nurseID = $_SESSION['role_id'];

// Updated query with CASE and debug columns for inpatient/outpatient IDs
$query = "SELECT p.*, 
                 i.PatientID AS inpatient_id,
                 o.PatientID AS outpatient_id,
                 CASE 
                    WHEN i.PatientID IS NOT NULL THEN 'Inpatient'
                    WHEN o.PatientID IS NOT NULL THEN 'Outpatient'
                    ELSE 'Unknown'
                 END AS PatientType,
                 n.Name AS NurseName
          FROM patients p
          LEFT JOIN inpatients i ON p.PatientID = i.PatientID
          LEFT JOIN outpatients o ON p.PatientID = o.PatientID
          LEFT JOIN nurse n ON p.AssignedNurseID = n.NurseID
          WHERE p.AssignedNurseID = ?";  // Changed to only patients assigned to this nurse

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $nurseID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $patients = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $patients = [];
}

// The rest of your update and include code stays the same
if (isset($_POST['update_patient'])) {
    $patient_id = $_POST['patient_id'];
    $vital_signs = $_POST['vital_signs'];
    $stmt = $conn->prepare("UPDATE patients SET VitalSigns = ? WHERE PatientID = ?");
    $stmt->bind_param("si", $vital_signs, $patient_id);
    $stmt->execute();
    header("Location: patient.php");
    exit();
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Patient Management</title>
<link rel="stylesheet" href="../../css/style.css" />
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }

    .content {
        padding: 40px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        padding: 10px;
        text-align: center;
        border: 1px solid #ddd;
    }

    th {
        background-color: #f8f9fa;
    }

    form input, form button {
        padding: 5px 10px;
        margin-top: 5px;
    }

    button.view-btn {
        background-color: #6f42c1;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
    }

    button.view-btn:hover {
        background-color: #512da8;
    }

    /* Modal styles (based on your patient details page) */
    .modal {
        position: fixed;
        z-index: 999;
        left: 0; top: 0;
        width: 100%; height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
        display: none;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        border: 2px solid purple;
        border-radius: 12px;
        padding: 40px;
        background-color: #fff;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 0 12px rgba(0,0,0,0.05);
        position: relative;
    }

    .close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
        color: #888;
        cursor: pointer;
    }

    .close:hover {
        color: #000;
    }

    .profile-img {
        width: 100px;
        height: 100px;
        margin: 0 auto 30px;
        border-radius: 50%;
        background-color: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .profile-img img {
        width: 60px;
        height: 60px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin: 12px 0;
        font-size: 16px;
        color: #555;
    }

    .info-row strong {
        font-weight: 600;
        color: #444;
    }

    .back-link {
        display: inline-block;
        margin-top: 30px;
        text-decoration: none;
        color: #fff;
        background-color: #6f42c1;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
    }

    .back-link:hover {
        background-color: #512da8;
    }
</style>
</head>
<body>

<div class="content">
    <h2>My Patients </h2>

    <table>
        <thead>
            <tr>
                <th>Patient ID</th>
                <th>Name</th>
                <th>Vital Signs</th>
                <th>Patient Type</th>
                <th>Assigned Nurse</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($patients) === 0): ?>
                <tr><td colspan="6" style='text-align: center; font-style: italic; color: #666;'>No patients found.</td></tr>
            <?php else: ?>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= htmlspecialchars($patient['PatientID']) ?></td>
                        <td><?= htmlspecialchars($patient['Name']) ?></td>
                        <td>
                            <form method="POST" style="margin:0;">
                                <input type="text" name="vital_signs" value="<?= htmlspecialchars($patient['VitalSigns'] ?? 'Not Recorded') ?>" required>
                                <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient['PatientID']) ?>">
                                <button type="submit" name="update_patient">Update</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($patient['PatientType']) ?></td>
                        <td><?= htmlspecialchars($patient['NurseName'] ?? 'Unassigned') ?></td>
                        <td>
                            <button class="view-btn" 
                                onclick="openModal(
                                    '<?= htmlspecialchars($patient['PatientID']) ?>',
                                    '<?= htmlspecialchars($patient['Name']) ?>',
                                    '<?= htmlspecialchars($patient['Sex']) ?>',
                                    '<?= htmlspecialchars($patient['VitalSigns'] ?? 'Not Recorded') ?>',
                                    '<?= htmlspecialchars($patient['PatientType']) ?>',
                                    '<?= htmlspecialchars($patient['NurseName'] ?? 'Unassigned') ?>'
                                )">View Details</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Patient Details -->
<div id="patientModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>

        <div class="profile-img">
            <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Profile Icon" />
        </div>

        <div class="info-row">
            <strong>Patient ID:</strong>
            <span id="modalPatientID"></span>
        </div>
        <div class="info-row">
            <strong>Name:</strong>
            <span id="modalName"></span>
        </div>
        <div class="info-row">
            <strong>Gender:</strong>
            <span id="modalGender"></span>
        </div>
        <div class="info-row">
            <strong>Vital Sign:</strong>
            <span id="modalVital"></span>
        </div>
        <div class="info-row">
            <strong>Patient Type:</strong>
            <span id="modalType"></span>
        </div>
        <div class="info-row">
            <strong>Assigned Nurse:</strong>
            <span id="modalNurse"></span>
        </div>
    </div>
</div>

<script>
function openModal(id, name, sex, vital, type, nurse) {
    document.getElementById('modalPatientID').textContent = id || 'N/A';
    document.getElementById('modalName').textContent = name || 'N/A';
    
    // Map sex code to string
    let genderStr = 'Other';
    if (sex === 'M') genderStr = 'Male';
    else if (sex === 'F') genderStr = 'Female';
    document.getElementById('modalGender').textContent = genderStr;
    
    document.getElementById('modalVital').textContent = vital || 'Not Recorded';
    document.getElementById('modalType').textContent = type || 'Unknown';
    document.getElementById('modalNurse').textContent = nurse || 'Unassigned';

    document.getElementById('patientModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('patientModal').style.display = 'none';
}

// Close modal on clicking outside modal content
window.onclick = function(event) {
    const modal = document.getElementById('patientModal');
    if (event.target === modal) {
        closeModal();
    }
};
</script>

</body>
</html>
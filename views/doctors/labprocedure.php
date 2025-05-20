<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Doctor') {
    header("Location: ../../auth/doctor_login.php");
    exit();
}

include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');
include('../../config/db.php');

date_default_timezone_set('Asia/Manila');

$doctor_name = $_SESSION['username'];
$doctorID = $_SESSION['role_id'] ?? null;

// Handle new lab request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_request'])) {
    $patientID = $_POST['PatientID'];
    $doctorID = $_POST['DoctorID'];
    $testDate = $_POST['TestDate'];
    $procedureName = $_POST['ProcedureName'];
    $status = "Request Submitted";
    $result = "";

    $stmt = $conn->prepare("INSERT INTO labprocedure (PatientID, DoctorID, TestDate, ProcedureName, Status, Result) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $patientID, $doctorID, $testDate, $procedureName, $status, $result);
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid resubmission on refresh
    header("Location: labprocedure.php");
    exit();
}

// Handle updating the result and status inline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_result'])) {
    $labReqID = intval($_POST['LabReqID']);
    $newResult = $_POST['Result'];
    $newStatus = 'Done';

    $stmt = $conn->prepare("UPDATE labprocedure SET Result = ?, Status = ? WHERE LabReqID = ?");
    $stmt->bind_param("ssi", $newResult, $newStatus, $labReqID);
    $stmt->execute();
    $stmt->close();

    header("Location: labprocedure.php");
    exit();
}

// Fetch lists for dropdowns
$procedureQuery = "SELECT ProcedureName FROM procedures";
$procedureResult = mysqli_query($conn, $procedureQuery);
$procedures = $procedureResult ? mysqli_fetch_all($procedureResult, MYSQLI_ASSOC) : [];

$patientsResult = mysqli_query($conn, "SELECT PatientID, Name FROM patients");
$patients = $patientsResult ? mysqli_fetch_all($patientsResult, MYSQLI_ASSOC) : [];

$doctorsResult = mysqli_query($conn, "SELECT DoctorID, DoctorName FROM doctor");
$doctors = $doctorsResult ? mysqli_fetch_all($doctorsResult, MYSQLI_ASSOC) : [];

// Fetch lab requests
$requestsQuery = "
    SELECT lp.*, p.Name AS PatientName, d.DoctorName AS DoctorName 
    FROM labprocedure lp
    JOIN patients p ON lp.PatientID = p.PatientID
    JOIN doctor d ON lp.DoctorID = d.DoctorID
";

$requestsResult = mysqli_query($conn, $requestsQuery);
$labRequests = $requestsResult ? mysqli_fetch_all($requestsResult, MYSQLI_ASSOC) : [];

$hour = date("H");

// Auto update statuses based on time passed
foreach ($labRequests as &$req) {
    $labReqID = $req['LabReqID'];
    $testDate = new DateTime($req['TestDate']);
    $now = new DateTime();
    $diffDays = $testDate->diff($now)->days;
    $status = $req['Status'];

    if ($status == "Request Submitted" && $diffDays >= 1) {
        // Update to In Progress after 1 day
        $status = "In Progress";
        mysqli_query($conn, "UPDATE labprocedure SET Status='In Progress' WHERE LabReqID=$labReqID");
    } elseif ($status == "In Progress" && $diffDays >= 3) {
        // Update to Done after 3 days, if no result yet assign default result
        $status = "Done";
        if (empty($req['Result'])) {
            $defaultResult = "Negative"; // Or your logic here
            mysqli_query($conn, "UPDATE labprocedure SET Status='Done', Result='$defaultResult' WHERE LabReqID=$labReqID");
            $req['Result'] = $defaultResult;
        } else {
            mysqli_query($conn, "UPDATE labprocedure SET Status='Done' WHERE LabReqID=$labReqID");
        }
    }
    $req['Status'] = $status;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Procedures</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        /* Your existing styles */
        .content { padding: 40px; margin-left: 230px; }
        .card-box { background: #fff; padding: 30px; border-radius: 12px; border: 4px solid #c34b4b; box-shadow: 6px 6px 0px #e58585; margin-bottom: 40px; }
        .form-label { font-weight: bold; }
        .btn-submit { background-color: rgb(221, 106, 106); color: white; border: none; padding: 12px; width: 100%; border-radius: 10px; font-weight: bold; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        table th, table td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        table th { background-color: #f5bebe; color: rgb(248, 64, 64); }
        .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
        .form-group { flex: 1; min-width: 220px; }
        select, input[type="text"], input[type="date"], input[type="datetime-local"], textarea {
            width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #ccc;
        }
        textarea { resize: vertical; }
        .inline-form { display: inline-block; margin: 0; }
        .btn-update { background-color: #4CAF50; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
<div class="content">
    <h3><?= "Good " . ($hour < 12 ? "Morning" : ($hour < 18 ? "Afternoon" : "Evening")) . ", Dr. " . htmlspecialchars($doctor_name); ?>!</h3>
    <p class="subtitle">Submit Lab Requests:</p>

    <!-- New Request Form -->
    <div class="card-box">
        <form method="POST">
            <input type="hidden" name="new_request" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Patient</label>
                    <select name="PatientID" required>
                        <option disabled selected>Select Patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['PatientID'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Doctor</label>
                    <select name="DoctorID" required readonly>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?= $doc['DoctorID'] ?>" <?= $doc['DoctorID'] == $doctorID ? "selected" : "" ?>>
                                <?= htmlspecialchars($doc['DoctorName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Test Date & Time</label>
                    <input type="datetime-local" name="TestDate" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Procedure</label>
                    <select name="ProcedureName" required>
                        <option disabled selected>Select Procedure</option>
                        <?php foreach ($procedures as $proc): ?>
                            <option value="<?= htmlspecialchars($proc['ProcedureName']) ?>"><?= htmlspecialchars($proc['ProcedureName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button class="btn-submit" type="submit">Submit Request</button>
        </form>
    </div>

    <!-- Lab Requests Table -->
    <div class="card-box">
        <h4>Lab Requests</h4>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Test Date</th>
                    <th>Procedure</th>
                    <th>Status</th>
                    <th>Result</th>
                    <th>Update Result</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($labRequests as $req): ?>
                    <tr>
                        <td><?= $req['LabReqID'] ?></td>
                        <td><?= htmlspecialchars($req['PatientName']) ?></td>
                        <td><?= htmlspecialchars($req['DoctorName']) ?></td>
                        <td><?= htmlspecialchars($req['TestDate']) ?></td>
                        <td><?= htmlspecialchars($req['ProcedureName']) ?></td>
                        <td><?= htmlspecialchars($req['Status']) ?></td>
                        <td><?= $req['Status'] === 'Done' ? htmlspecialchars($req['Result']) : '—' ?></td>
                        <td>
                            <?php if ($req['Status'] === 'In Progress'): ?>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="update_result" value="1">
                                    <input type="hidden" name="LabReqID" value="<?= $req['LabReqID'] ?>">
                                    <textarea name="Result" required placeholder="Enter result..."></textarea>
                                    <button type="submit" class="btn-update">Save</button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($req['Status'] === 'Done'): ?>
                                <a href="LabResult_pdf.php?id=<?= $req['LabReqID'] ?>" target="_blank">Download PDF</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

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

$hour = date('H');
if ($hour < 12) {
    $greet = "Good Morning";
} elseif ($hour < 18) {
    $greet = "Good Afternoon";
} else {
    $greet = "Good Evening";
}

// Fetch procedures
$query = "SELECT * FROM labprocedure";
$result = mysqli_query($conn, $query);
$procedures = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Procedures</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .content {
            padding: 40px;
            margin-top: 80px; /* space for fixed header */
            margin-left: 220px; /* space for sidebar */
            width: calc(100% - 220px);
            box-sizing: border-box;
        }

        h3, h4 {
            color: #730000;
        }

        .card-box {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            border: 6px solid #c34b4b;
            box-shadow: 8px 8px 0px #e58585;
            margin-bottom: 40px;
        }

        .form-label {
            font-weight: bold;
        }

        .btn-submit {
            background-color: #c34b4b;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #a42c2c;
        }

        /* Scope form input styling to prevent global effect */
        .card-box input[type="text"],
        .card-box input[type="number"],
        .card-box input[type="date"],
        .card-box input[type="datetime-local"] {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 4px solid #ccc;
            box-sizing: border-box;
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .form-group {
            flex: 1;
            min-width: 220px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        table th {
            background-color: #f5bebe;
            color: #730000;
        }

        .subtitle {
            font-size: 18px;
            color: #a42c2c;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 30px;
        }

        /* Example: Targeting the search bar inside the header */
        header input[type="text"],
        .header input[type="text"] {
            height: 13px;          /* slightly taller, default is usually ~28-30px */
            padding: 6px 12px;     /* more vertical and horizontal padding */
            font-size: 16px;       /* slightly bigger font */
            border-radius: 6px;    /* keep it rounded */
        }

    </style>
</head>
<body>

<div class="content">
    <h3><?php echo $greet . ", Dr. " . htmlspecialchars($doctor_name); ?>!</h3>
    <p class="subtitle">Manage Lab Procedures below:</p>

    <div class="card-box">
        <h4>Add New Lab Procedure</h4>
        <form method="POST" action="/labprocedure/store">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="PatientID">Patient ID</label>
                    <input type="number" name="PatientID" id="PatientID" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="DoctorID">Doctor ID</label>
                    <input type="number" name="DoctorID" id="DoctorID" value="<?= htmlspecialchars($doctorID); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="TestDate">Test Date & Time</label>
                    <input type="datetime-local" name="TestDate" id="TestDate" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="Result">Test Result</label>
                    <input type="text" name="Result" id="Result" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="DateReleased">Date Released</label>
                    <input type="date" name="DateReleased" id="DateReleased" required>
                </div>
            </div>
            <button class="btn-submit" type="submit">Submit Procedure</button>
        </form>
    </div>

    <?php if (!empty($procedures)): ?>
        <div class="card-box">
            <h4>Existing Lab Procedures</h4>
            <table>
                <thead>
                    <tr>
                        <th>Procedure ID</th>
                        <th>Patient ID</th>
                        <th>Doctor ID</th>
                        <th>Test Date</th>
                        <th>Result</th>
                        <th>Date Released</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($procedures as $proc): ?>
                        <tr>
                            <td><?= htmlspecialchars($proc['ProcedureID']); ?></td>
                            <td><?= htmlspecialchars($proc['PatientID']); ?></td>
                            <td><?= htmlspecialchars($proc['DoctorID']); ?></td>
                            <td><?= htmlspecialchars($proc['TestDate']); ?></td>
                            <td><?= htmlspecialchars($proc['Result']); ?></td>
                            <td><?= htmlspecialchars($proc['DateReleased']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

<?php
session_start();
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');
include('../../config/db.php');

// Fetch procedures from database
$query = "SELECT * FROM labprocedure";
$result = mysqli_query($conn, $query);
$procedures = mysqli_fetch_all($result, MYSQLI_ASSOC);
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Doctor' && isset($_SESSION['role_id'])) {
    $doctorID = $_SESSION['role_id'];
}
if (!$procedures) {
    echo "Error fetching data: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Procedures</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 60px;
        }

        .main-content {
            margin-left: 220px;
            padding: 40px 30px;
        }

        .main-box {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        h2, .card-header h4 {
            color: #343a40;
        }

        .form-section {
            margin-bottom: 40px;
        }

        .btn {
            padding: 10px 16px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .table th, .table td {
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="main-box">
        <h2 class="text-center mb-4">Lab Procedures</h2>

        <!-- Add New Lab Procedure Form -->
        <div class="card form-section">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Add New Lab Procedure</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/labprocedure/store">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="PatientID" class="form-label">Patient ID</label>
                            <input type="number" name="PatientID" id="PatientID" class="form-control" placeholder="Enter Patient ID" required>
                        </div>
                        <div class="col-md-4">
                            <label for="DoctorID" class="form-label">Doctor ID</label>
                            <input type="number" name="DoctorID" id="DoctorID" class="form-control" placeholder="Enter Doctor ID" required>
                        </div>
                        <div class="col-md-4">
                            <label for="TestDate" class="form-label">Test Date & Time</label>
                            <input type="datetime-local" name="TestDate" id="TestDate" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="Result" class="form-label">Test Result</label>
                            <input type="text" name="Result" id="Result" class="form-control" placeholder="Enter Result" required>
                        </div>
                        <div class="col-md-6">
                            <label for="DateReleased" class="form-label">Date Released</label>
                            <input type="date" name="DateReleased" id="DateReleased" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100" type="submit">Submit Procedure</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Lab Procedure Form -->
        <?php if (isset($editProcedure)): ?>
        <div class="card form-section">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Edit Lab Procedure</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/labprocedure/update">
                    <input type="hidden" name="ProcedureID" value="<?= $editProcedure['ProcedureID']; ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="PatientID" class="form-label">Patient ID</label>
                            <input type="number" name="PatientID" id="PatientID" class="form-control" value="<?= $editProcedure['PatientID']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="DoctorID" class="form-label">Doctor ID</label>
                            <input type="number" name="DoctorID" id="DoctorID" class="form-control" value="<?= $editProcedure['DoctorID']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="TestDate" class="form-label">Test Date & Time</label>
                            <input type="datetime-local" name="TestDate" id="TestDate" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($editProcedure['TestDate'])); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="Result" class="form-label">Test Result</label>
                            <input type="text" name="Result" id="Result" class="form-control" value="<?= $editProcedure['Result']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="DateReleased" class="form-label">Date Released</label>
                            <input type="date" name="DateReleased" id="DateReleased" class="form-control" value="<?= $editProcedure['DateReleased']; ?>" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-warning w-100" type="submit">Update Procedure</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>

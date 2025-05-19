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
// Check for errors
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
            padding-top: 60px; /* space for fixed header */
        }

        .main-content {
            margin-left: 220px; /* space for fixed sidebar */
            padding: 40px 30px;
        }

        .main-box {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 100%;
        }

        h2 {
            color: #343a40;
            font-size: 2.2rem;
            margin-bottom: 30px;
        }

        .table th, .table td {
            vertical-align: middle;
        }

        .form-control {
            margin-bottom: 1rem;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
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
        <h2 class="text-center">Lab Procedures</h2>


    <div class="card mb-4">
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
                        <button class="btn btn-primary w-100" type="submit">Add Lab Procedure</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

        <!-- Edit Form -->
        <?php if (isset($editProcedure)): ?>
            <hr>
            <h4>Edit Lab Procedure</h4>
            <form method="POST" action="/labprocedure/store" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="PatientID" class="form-label">Patient ID</label>
                        <input type="number" name="PatientID" id="PatientID" class="form-control" placeholder="Enter Patient ID" required>
                        <div class="invalid-feedback">
                            Please provide a valid Patient ID.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="DoctorID" class="form-label">Doctor ID</label>
                        <input type="number" name="DoctorID" id="DoctorID" class="form-control" placeholder="Enter Doctor ID" required>
                        <div class="invalid-feedback">
                            Please provide a valid Doctor ID.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="TestDate" class="form-label">Test Date & Time</label>
                        <input type="datetime-local" name="TestDate" id="TestDate" class="form-control" required>
                        <div class="invalid-feedback">
                            Please select a Test Date & Time.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="Result" class="form-label">Test Result</label>
                        <input type="text" name="Result" id="Result" class="form-control" placeholder="Enter Result" required>
                        <div class="invalid-feedback">
                            Please provide a Test Result.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="DateReleased" class="form-label">Date Released</label>
                        <input type="date" name="DateReleased" id="DateReleased" class="form-control" required>
                        <div class="invalid-feedback">
                            Please select a Date Released.
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100" type="submit">Add Lab Procedure</button>
                    </div>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>

<script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>
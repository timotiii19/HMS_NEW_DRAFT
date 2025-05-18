<?php
session_start();

include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');
include('../../config/db.php');


// Fetch procedures from database
$query = "SELECT * FROM labprocedure";  // Adjust the table name if necessary
$result = mysqli_query($conn, $query);
$procedures = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Check for any errors in fetching data
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
            background-color: #f8f9fa; /* Light background color */
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 1200px;  /* Maximum width */
            margin-left: 17%;   /* Adjusted left margin */
            margin-top: 50px;   /* Adds more space from top */
        }
        h2 {
            color: #343a40; /* Darker color for header */
            font-size: 2.5rem; /* Larger header text */
        }
        .table th, .table td {
            vertical-align: middle; /* Align text to the center vertically */
        }
        .form-control {
            margin-bottom: 1rem; /* Space between form inputs */
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
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Lab Procedures</h2>

    <!-- Add New Form -->
    <form method="POST" action="/labprocedure/store" class="mb-4">
        <div class="row g-3">
            <div class="col-md-2">
                <input type="number" name="PatientID" class="form-control" placeholder="Patient ID" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="DoctorID" class="form-control" placeholder="Doctor ID" required>
            </div>
            <div class="col-md-2">
                <input type="datetime-local" name="TestDate" class="form-control" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="Result" class="form-control" placeholder="Result" required>
            </div>
            <div class="col-md-2">
                <input type="date" name="DateReleased" class="form-control" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Add</button>
            </div>
        </div>
    </form>

    <!-- Display Procedures Table -->
    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Test Date</th>
                <th>Result</th>
                <th>Released</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($procedures as $p): ?>
                <tr>
                    <td><?= $p['LabProcedureID'] ?></td>
                    <td><?= $p['PatientID'] ?></td>
                    <td><?= $p['DoctorID'] ?></td>
                    <td><?= $p['TestDate'] ?></td>
                    <td><?= $p['Result'] ?></td>
                    <td><?= $p['DateReleased'] ?></td>
                    <td>
                        <a href="/labprocedure/edit/<?= $p['LabProcedureID'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Edit Form -->
    <?php if (isset($editProcedure)): ?>
        <hr>
        <h4>Edit Lab Procedure</h4>
        <form method="POST" action="/labprocedure/update/<?= $editProcedure['LabProcedureID'] ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <input type="number" name="PatientID" class="form-control" value="<?= $editProcedure['PatientID'] ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="DoctorID" class="form-control" value="<?= $editProcedure['DoctorID'] ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="datetime-local" name="TestDate" class="form-control" value="<?= $editProcedure['TestDate'] ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="Result" class="form-control" value="<?= $editProcedure['Result'] ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="date" name="DateReleased" class="form-control" value="<?= $editProcedure['DateReleased'] ?>" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100" type="submit">Update</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>
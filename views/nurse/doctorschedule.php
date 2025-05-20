<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/nurse_login.php");
    exit();
}
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
include('../../config/db.php');

$location_id = $conn->query("SELECT * FROM locations");

// Update schedule if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $id = $_POST['DoctorScheduleID'];
    $doctor_id = $_POST['DoctorID'];
    $location_id = $_POST['LocationID'];
    $date = $_POST['ScheduleDate'];
    $start = $_POST['StartTime'];
    $end = $_POST['EndTime'];
    $status = $_POST['Status'];

    $stmt = $conn->prepare("UPDATE doctorschedule SET DoctorID=?, LocationID=?, ScheduleDate=?, StartTime=?, EndTime=?, Status=? WHERE DoctorScheduleID=?");
    $stmt->bind_param("iissssi", $doctor_id, $location_id, $date, $start, $end, $status, $id);
    $stmt->execute();
    header("Location: doctor_schedule.php");
    exit();
}

// Fetch doctor schedules
$query = "SELECT * FROM doctorschedule";
$result = $conn->query($query);
$schedules = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Schedules - Nurse Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <style>
        body {
            background-color:rgb(255, 255, 255); /* Light background color */
        }  
        .content { padding: 20px; }
        .btn {
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-warning { background-color: #ffc107; color: white; }
        .btn-warning:hover { background-color: #e0a800; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-success:hover { background-color: #218838; }

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
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f2f2f2;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-dialog {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
        }
        .modal-header, .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body label {
            display: block;
            margin-top: 10px;
        }
        .modal-body input, .modal-body select {
            width: 100%;
            padding: 6px;
            margin-top: 4px;
        }
        .close {
            background: none;
            border: none;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Doctor Schedules</h2>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Doctor ID</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $s): ?>
                    <tr>
                        <td><?= $s['DoctorScheduleID'] ?></td>
                        <td><?= $s['DoctorID'] ?></td>
                        <td><?= $s['LocationID'] ?></td>
                        <td><?= $s['ScheduleDate'] ?></td>
                        <td><?= $s['StartTime'] ?></td>
                        <td><?= $s['EndTime'] ?></td>
                        <td><?= $s['Status'] ?></td>
                        <td>
                            <button class="btn btn-warning" onclick='openEditModal(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal" id="editModal">
        <div class="modal-dialog">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5>Edit Doctor Schedule</h5>
                    <button type="button" onclick="closeModal()" class="close">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="DoctorScheduleID" id="edit-id">
                    <label>Doctor ID</label>
                    <input type="number" name="DoctorID" id="edit-doctor-id" required>

                    <label>Location ID</label>
                    <input type="number" name="LocationID" id="edit-location-id" required>

                    <label>Date</label>
                    <input type="date" name="ScheduleDate" id="edit-date" required>

                    <label>Start Time</label>
                    <input type="time" name="StartTime" id="edit-start" required>

                    <label>End Time</label>
                    <input type="time" name="EndTime" id="edit-end" required>

                    <label>Status</label>
                    <select name="Status" id="edit-status" required>
                        <option value="Regular">Regular</option>
                        <option value="Resident">Resident</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_schedule" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(schedule) {
            document.getElementById('edit-id').value = schedule.DoctorScheduleID;
            document.getElementById('edit-doctor-id').value = schedule.DoctorID;
            document.getElementById('edit-location-id').value = schedule.LocationID;
            document.getElementById('edit-date').value = schedule.ScheduleDate;
            document.getElementById('edit-start').value = schedule.StartTime;
            document.getElementById('edit-end').value = schedule.EndTime;
            document.getElementById('edit-status').value = schedule.Status;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>
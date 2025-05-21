<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
include('../../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete doctor and user
if (isset($_GET['delete'])) {
    $doctor_id = $_GET['delete'];
    $result = $conn->query("SELECT UserID FROM doctor WHERE DoctorID = $doctor_id");
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['UserID'];
        $conn->query("DELETE FROM doctor WHERE DoctorID = $doctor_id");
        $conn->query("DELETE FROM users WHERE id = $user_id");
    }
    header("Location: doctors.php");
    exit();
}

// Fetch doctors with user info
$result = $conn->query("SELECT d.DoctorID, u.username AS DoctorName, u.email AS Email, d.Availability, u.ContactNumber, d.DoctorType, dep.DepartmentName, d.DoctorFee
                        FROM doctor d 
                        JOIN users u ON d.UserID = u.UserID
                        LEFT JOIN department dep ON d.DepartmentID = dep.DepartmentID
                        ");

?>

<div class="content">
    <h2>Doctor Management</h2>
    <table>
        <tr>
            <th>DoctorID</th>
            <th>DoctorName</th>
            <th>Email</th>
            <th>Availability</th>
            <th>ContactNumber</th>
            <th>DoctorType</th>
            <th>Department</th>
            <th>DoctorFee</th>
            <th>Action</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['DoctorID'] ?></td>
                    <td><?= htmlspecialchars($row['DoctorName']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td><?= htmlspecialchars($row['Availability']) ?></td>
                    <td><?= htmlspecialchars($row['ContactNumber']) ?></td>
                    <td><?= htmlspecialchars($row['DoctorType']) ?></td>
                    <td><?= htmlspecialchars($row['DepartmentName']) ?></td>
                    <td><?= htmlspecialchars($row['DoctorFee']) ?></td>
                    <td>
                        <a href="edit_doctor.php?doctor_id=<?= $row['DoctorID'] ?>">Edit</a> |
                         <a href="employees.php?delete=<?= $user['DoctorID'] ?>" 
                            onclick="return confirm('Are you sure?');" 
                            class="delete-link">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9">No doctor records found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Doctor Management</title>
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

    .delete-link {
            color: red;
        }

</style>
</head>
<body>

<?php
session_start();
include('../../config/db.php');
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

// Insert new outpatients from doctorschedule where Status = 'Outpatient'
$sql = "SELECT OutpatientID, PatientID, DoctorID, VisitDate, Reason FROM outpatients";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Outpatients Table</title>
    <style>
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }

        th, td {
            border: 1px solid #666;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Outpatients Records</h2>
    <table>
        <tr>
            <th>OutpatientID</th>
            <th>PatientID</th>
            <th>DoctorID</th>
            <th>VisitDate</th>
            <th>Reason</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            // Output data for each row
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['OutpatientID']}</td>
                        <td>{$row['PatientID']}</td>
                        <td>{$row['DoctorID']}</td>
                        <td>{$row['VisitDate']}</td>
                        <td>{$row['Reason']}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No records found</td></tr>";
        }

        $conn->close();
        ?>
    </table>
</body>
</html>
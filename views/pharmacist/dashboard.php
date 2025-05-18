<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Pharmacist') {
    header("Location: ../../auth/pharmacist_login.php");
    exit();
}
include('../../config/db.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacist Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
</head>
<body>

<?php
include('../../includes/pharmacist_sidebar.php');
include('../../includes/pharmacist_header.php');
?>

<div class="content">
    <h2>Welcome, Pharmacist!</h2>
    <p>This is your Pharmacist dashboard. Use the sidebar to navigate.</p>
</div>

</body>
</html>

 <style>
        body {
            background-color: #ffffff;
            font-family: Arial, sans-serif;
        }

    </style>
<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Doctor') {
    header("Location: ../../auth/doctor_login.php");
    exit();
}
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');
include('../../config/db.php');

?>

<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
</head>
<body>

<div class="content">
    <h2>Welcome, Doctor!</h2>
    <p>This is your doctor dashboard. Use the sidebar to navigate.</p>
</div>

<style>
  body {
   background-color: #f8f9fa; /* Light background color */
     }  
</style>
    
</body>
</html>
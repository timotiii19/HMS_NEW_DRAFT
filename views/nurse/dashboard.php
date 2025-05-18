<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/nurse_login.php");
    exit();
}
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
include('../../config/db.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Nurse Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
</head>
<body>

<div class="content">
    <h2>Welcome, Nurse!</h2>
    <p>This is your Nurse dashboard. Use the sidebar to navigate.</p>
</div>
    
<style>
body {
   background-color:rgb(255, 255, 255); /* Light background color */
     } 
</style>
 
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Cashier') {
    header("Location: ../../auth/cashier_login.php");
    exit();
}
include('../../config/db.php');
include('../../includes/cashier_header.php');
include('../../includes/cashier_sidebar.php');

$cashier_name = $_SESSION['username'];
?>


<!DOCTYPE html>
<html>
<head>
    <title>Cashier Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(255, 255, 255);
        }

        .content {
            padding: 40px;
        }
    </style>
</head>

<body>

<div class="content">
<h2>Welcome, <?php echo htmlspecialchars($cashier_name); ?>!</h2>
    <p>This is your cashier dashboard. Use the sidebar to navigate.</p>
</div>
    
</body>
</html>

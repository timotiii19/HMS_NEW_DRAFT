<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['username']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: ../../auth/login.php");
    exit();
}


include('../../includes/doctor_sidebar.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $sql = "INSERT INTO patients (Name, DateOfBirth, Sex, Address)
            VALUES ('$name', '$dob', '$sex', '$address')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "New patient added successfully!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['message'] = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Patient</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
</head>
<body>

<div class="content">
    <h2>Create New Patient</h2>

    <!-- Display message -->
    <?php if (isset($_SESSION['message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
    <?php endif; ?>

    <form method="POST" action="" class="form-container">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="date" name="dob" required>
        <select name="sex" required>
            <option value="">-- Select Sex --</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
        <textarea name="address" placeholder="Enter address" required></textarea>
        <button type="submit">Save Patient</button>
    </form>

    <br>
    <a href="index.php" class="btn">← Back to Patient List</a>
</div>

</body>
</html>

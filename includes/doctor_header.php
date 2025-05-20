<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../../config/db.php');

// Initialize doctorName variable
$doctorName = "Unknown";

// Check session and fetch doctor name
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Doctor' && isset($_SESSION['role_id'])) {
    $doctorID = $_SESSION['role_id'];

    $query = "SELECT DoctorName FROM doctor WHERE DoctorID = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $doctorID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $name);
        if (mysqli_stmt_fetch($stmt)) {
            $doctorName = $name;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Redirect unauthorized users to login page (optional)
    header("Location: ../../auth/doctor_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Doctor Dashboard</title>
<!-- Font Awesome CDN for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
  }

  .header {
    position: fixed;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #eb6d9b;
    padding: 10px 20px;
    color: white;
    width: 100%;
    height: 60px;
    z-index: 10;
    top: 0;
    box-sizing: border-box;
  }

  .left-section,
  .right-section {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .logo {
    height: 40px;
  }

  .menu-icon {
    font-size: 20px;
    cursor: pointer;
  }

  .search-section {
    display: flex;
    align-items: center;
    background: #fcc0ef;
    border-radius: 20px;
    padding: 5px 10px;
    color: white;
  }

  .search-section input {
    background: transparent;
    border: none;
    outline: none;
    color: white;
    padding: 5px;
    width: 200px;
  }

  .search-icon {
    margin-left: 5px;
    color: #cc8383;
  }

  .avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: 2px solidrgb(91, 207, 95);
  }

  .user-dropdown {
    cursor: pointer;
    font-size: 14px;
  }
</style>
</head>
<body>

<div class="header">
  <div class="left-section">
    <img src="../../images/hosplogo.png" alt="Logo" class="logo" />
  </div>

  <div class="search-section">
    <input type="text" placeholder="Search..." />
    <i class="fas fa-search search-icon"></i>
  </div>

  <div class="right-section">
    <i class="fas fa-bars menu-icon"></i> <!-- burger icon on right -->
    <img src="../../images/user.png" alt="Avatar" class="avatar" />
    <div class="user-dropdown">
      <span>Welcome Dr. <?php echo htmlspecialchars($doctorName); ?> <i class="fas fa-chevron-down"></i></span>
    </div>
  </div>
</div>

</body>
</html>

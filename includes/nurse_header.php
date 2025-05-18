<?php
// Include the database connection
include('../../config/db.php');

$nurseName = "Unknown Nurse";

if (isset($_SESSION['role']) && $_SESSION['role'] === 'nurse' && isset($_SESSION['role_id'])) {
    $nurseID = $_SESSION['role_id'];

    // Fetch nurse name from DB
    $stmt = $conn->prepare("SELECT Name FROM nurse WHERE NurseID = ?");
    $stmt->bind_param("i", $nurseID);
    $stmt->execute();
    $stmt->bind_result($fetchedName);
    if ($stmt->fetch()) {
        $nurseName = $fetchedName;
    }
    $stmt->close();
} else {
    $nurseID = "Unknown";
}
?>

<style>
  /* Your CSS remains the same */
  .header {
    position: fixed;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #eb6d9b;
    padding: 10px 20px;
    color: white;
    width: 98%;
    height: 60px;
    z-index: 10;
    top: 0;
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

  .dropbtn {
      background: none;
      border: none;
      color: white;
      font-size: 16px;
      cursor: pointer;
  }

  .dropdown {
      position: relative;
      display: inline-block;
  }

  .dropdown-content {
      display: none;
      position: absolute;
      background-color: #3e4a56;
      min-width: 150px;
      z-index: 1;
      top: 100%;
      left: 0;
  }

  .dropdown-content a {
      color: white;
      padding: 10px;
      display: block;
      text-decoration: none;
  }

  .dropdown-content a:hover {
      background-color: #5a6570;
  }

  .dropdown:hover .dropdown-content {
      display: block;
  }

  .search-section {
      display: flex;
      align-items: center;
      background: #fcc0ef;
      border-radius: 20px;
      padding: 5px 10px;
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
      border: 2px solid #4caf50;
  }

  .user-dropdown {
      cursor: pointer;
      font-size: 14px;
  }
</style>

<div class="header">
    <div class="left-section">
        <img src="../../images/hosplogo.png" alt="Logo" class="logo">
        <i class="fas fa-bars menu-icon"></i>
    </div>

    <div class="search-section">
        <input type="text" placeholder="Search...">
        <i class="fas fa-search search-icon"></i>
    </div>

    <div class="right-section">
        <img src="../../assets/user.png" alt="Avatar" class="avatar">
        <div class="user-dropdown">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'nurse'): ?>
                <span> <?php echo htmlspecialchars($nurseName); ?> <i class="fas fa-chevron-down"></i></span>
            <?php endif; ?>
        </div>
    </div>
</div>
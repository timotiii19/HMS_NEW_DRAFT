<?php
//session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Header</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    /* Your CSS here — same as your existing header styles */
    .header {
      position: fixed;
      top: 0;
      width: 100%;
      height: 60px;
      background-color: #eb6d9b;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      z-index: 10;
    }

    .left-section, .right-section {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .logo {
      height: 40px;
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
      cursor: pointer;
    }

    .dropdown {
      position: relative;
    }

    .dropbtn {
      background: none;
      border: none;
      color: white;
      font-size: 16px;
      cursor: pointer;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #3e4a56;
      min-width: 160px;
      z-index: 20;
      top: 100%;
      left: 0;
    }

    .dropdown-content a {
      color: white;
      padding: 10px;
      text-decoration: none;
      display: block;
    }

    .dropdown-content a:hover {
      background-color: #5a6570;
    }

    .dropdown:hover .dropdown-content {
      display: block;
    }

    .avatar {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      border: 2px solid #4caf50;
      margin-right: 0px;
    }

    .user-dropdown {
      position: relative;
      cursor: pointer;
      font-size: 14px;
      margin-right: 50px;
    }

    .user-dropdown .dropdown-content {
      position: absolute;
      display: none;
      background-color: #3e4a56;
      min-width: 140px;
      right: 0;
      top: 40px;
      z-index: 999;
      margin-right: 50px;
    }

    .user-dropdown .dropdown-content a {
      color: white;
      padding: 10px;
      text-decoration: none;
      display: block;
    }

    .user-dropdown .dropdown-content a:hover {
      background-color: #555;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="left-section">
      <img src="/HMS-main/images/hosplogo.png" alt="Logo" class="logo" />

      <div class="dropdown">
        <button class="dropbtn">
          Create New <i class="fas fa-chevron-down"></i>
        </button>
        <div class="dropdown-content">
          <a href="/HMS-main/views/admin/employees.php">Add Employee</a>
          <a href="/HMS-main/views/admin/admin.php">View Admins</a>
          <a href="/HMS-main/views/admin/doctors.php">View Doctors</a>
          <a href="/HMS-main/views/admin/nurses.php">View Nurses</a>
          <a href="/HMS-main/views/admin/pharmacists.php">View Pharmacists</a>
          <a href="/HMS-main/views/admin/cashiers.php">View Cashiers</a>
        </div>
      </div>
    </div>

    <div class="search-section">
      <input type="text" placeholder="Search..." id="searchInput" />
      <i class="fas fa-search search-icon" id="searchBtn"></i>
    </div>

    <div class="right-section">
      <img src="/HMS-main/assets/user.png" alt="Avatar" class="avatar" />
      <div class="user-dropdown" id="userDropdownToggle">
        <span>
          <?php
          if (isset($_SESSION['role']) && isset($_SESSION['full_name'])) {
            echo htmlspecialchars($_SESSION['role'] . ': ' . $_SESSION['full_name']);
          } else {
            echo "Guest User";
          }
          ?>
          <i class="fas fa-chevron-down"></i>
        </span>
        <div class="dropdown-content" id="userDropdownMenu">
          <a href="/HMS-main/views/admin/profile.php">My Profile</a>
          <a href="/HMS-main/auth/logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');

    function performSearch(query) {
      if (!query.trim()) {
        alert('Please enter a search term');
        return;
      }
      window.location.href = `/HMS-main/views/admin/search.php?query=${encodeURIComponent(query)}`;
    }

    searchInput.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') {
        performSearch(this.value);
      }
    });

    searchBtn.addEventListener('click', function () {
      performSearch(searchInput.value);
    });

    // User dropdown toggle
    const userToggle = document.getElementById('userDropdownToggle');
    const userMenu = document.getElementById('userDropdownMenu');

    userToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      userMenu.style.display = userMenu.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function () {
      userMenu.style.display = 'none';
    });
  </script>
</body>
</html>

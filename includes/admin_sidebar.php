<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <button id="sidebarToggle" aria-label="Toggle Sidebar">&#9776;</button>
    <h2>Admin Dashboard</h2>
  </div>
  <ul>
    <li><a href="/HMS-main/views/admin/dashboard.php">Dashboard</a></li>
    <li>
      <a href="javascript:void(0);" class="dropdown-btn">Employees</a>
      <ul class="dropdown-content">
        <li><a href="/HMS-main/views/admin/employees.php">Employees Management</a></li> 
        <li style="text-align: center;">⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯</li>
        <li><a href="/HMS-main/views/admin/admin.php">Admins Management</a></li>
        <li><a href="/HMS-main/views/admin/doctors.php">Doctors Management</a></li>
        <li><a href="/HMS-main/views/admin/nurses.php">Nurses Management</a></li>
        <li><a href="/HMS-main/views/admin/pharmacists.php">Pharmacists Management</a></li>
        <li><a href="/HMS-main/views/admin/cashiers.php">Cashiers Management</a></li>
      </ul>
    </li>

    <li><a href="/HMS-main/views/admin/appointments.php">Appointments Management</a></li>
    <li><a href="/HMS-main/views/admin/departments.php">Department Management</a></li>
    <li><a href="/HMS-main/views/admin/location.php">Location Management</a></li>
    <li><a href="/HMS-main/views/admin/reports.php">Billing Management & Reports</a></li>
    <li><a href="/HMS-main/views/admin/patients.php">Patients</a></li>
    <!-- <li><a href="/HMS-main/views/admin/visitor.php">Visitors Management</a></li> -->
    <li><a href="/HMS-main/auth/logout.php">Logout</a></li>
  </ul>
</div>

<script>
  // Dropdown toggle for Employees menu
  const dropdownBtns = document.querySelectorAll('.dropdown-btn');
  dropdownBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      btn.classList.toggle('active');
      const dropdownContent = btn.nextElementSibling;
      if (dropdownContent.style.display === 'block') {
        dropdownContent.style.display = 'none';
      } else {
        dropdownContent.style.display = 'block';
      }
    });
  });

  // Sidebar toggle with hamburger button
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.content');

  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    if(sidebar.classList.contains('collapsed')){
      content.style.marginLeft = '60px';  // small margin when collapsed
    } else {
      content.style.marginLeft = '220px';
    }
  });
</script>

<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #e0f7fa;
    color: #333;
    box-sizing: border-box;
    padding-top: 60px; /* Ensure body content starts below the header */
  }

  .sidebar {
    position: fixed;
    top: 70px; /* Push the sidebar below the header */
    width: 190px;
    height: calc(100vh - 60px);
    background-color: #9c335a;
    padding: 20px;
    color: white;
    z-index: 1;
    overflow-y: auto;
    transition: width 0.3s ease;
  }

  /* Collapsed sidebar */
  .sidebar.collapsed {
    width: 60px;
    padding: 20px 10px;
  }

  .sidebar.collapsed h2 {
    display: none; /* Hide the text when collapsed */
  }

  .sidebar-header {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-bottom: 20px;
  }

  #sidebarToggle {
    font-size: 24px;
    color: white;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    user-select: none;
    margin-bottom: 5px;
  }

  .sidebar h2 {
    margin: 0;
    font-size: 1.5em;
    
  }

  .sidebar ul {
    list-style-type: none;
    padding: 0;
  }

  .sidebar ul li {
    margin-bottom: 10px;
    position: relative;
  }

  .sidebar ul li a {
    color: white;
    text-decoration: none;
    display: block;
    padding: 8px;
    font-size: 1em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .sidebar ul li a:hover {
    background-color: #7a0154;
    border-radius: 4px;
  }

  .content {
    margin-left: 220px;
    padding: 40px;
    transition: margin-left 0.3s ease;
  }

  /* When sidebar collapsed, adjust content margin */
  .sidebar.collapsed ~ .content {
    margin-left: 60px;
  }

  .dropdown-content {
    display: none;
    list-style-type: none;
    padding-left: 10px;
    background-color: #923f78;
  }

  .dropdown-content li a {
    padding: 6px 8px;
    font-size: 0.85em !important;
    opacity: 0.85;
    margin-left: 0px;
  }

  .sidebar .dropdown-content li a {
    padding-left: 30px !important;
  }

  .dropdown-btn::after {
    content: " ▼";
    font-size: 0.7em;
  }

  .dropdown-btn.active::after {
    content: " ▲";
  }
</style>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Nurse Dashboard</title>
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #e0f7fa;
      color: #333;
      box-sizing: border-box;
      padding-top: 60px;
    }

    .sidebar {
      position: fixed;
      top: 60px;
      width: 190px;
      height: calc(100vh - 60px);
      background-color: #9c335a;
      padding: 20px;
      color: white;
      z-index: 1;
      overflow-y: auto;
    }

    .sidebar h2 {
      margin-bottom: 20px;
      font-size: 1.5em;
    }

    .sidebar ul {
      list-style-type: none;
      padding: 0;
    }

    .sidebar ul li {
      margin-top: 4px;
      margin-bottom: 10px;
    }

    .sidebar ul li a {
      color: white;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px;
      font-size: 1em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .sidebar ul li a i {
      font-size: 1.2em;
      width: 20px;
      text-align: center;
    }

    .sidebar ul li a:hover {
      background-color: #7a0154;
      border-radius: 4px;
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
      margin-left: auto;
    }

    .dropdown-btn.active::after {
      content: " ▲";
    }

    .content {
      margin-left: 220px;
      padding: 40px;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <h2>Hospital System</h2>
    <ul>
      <li><a href="/HMS-main/views/nurse/dashboard.php"><i class="fa fa-tachometer-alt"></i>Dashboard</a></li>

      <li>
        <a href="javascript:void(0);" class="dropdown-btn"><i class="fa fa-procedures"></i>Patient Management</a>
        <ul class="dropdown-content">
          <li><a href="/HMS-main/views/nurse/add_patient.php"><i class="fa fa-user-plus"></i>Add Patient</a></li>
          <li><a href="/HMS-main/views/nurse/patient.php"><i class="fa fa-users"></i>View Patients</a></li>
          <li style="text-align: center;"></li>
          <li style="text-align: center; opacity: 0.1;">⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯</li>
          <li><a href="/HMS-main/views/nurse/my_patients.php"><i class="fa fa-user-md"></i>My Patients</a></li>
          <li><a href="/HMS-main/views/nurse/inpatient.php"><i class="fa fa-bed"></i>Inpatients</a></li>
          <li><a href="/HMS-main/views/nurse/outpatient.php"><i class="fa fa-walking"></i>Outpatients</a></li>
        </ul>
      </li>

      <li><a href="/HMS-main/views/nurse/department.php"><i class="fa fa-building"></i>Departments</a></li>
      <li><a href="/HMS-main/views/nurse/doctorschedule.php"><i class="fa fa-user-clock"></i>Doctor Schedule</a></li>
      <li><a href="/HMS-main/views/nurse/location.php"><i class="fa fa-map-marker-alt"></i>Location</a></li>
      <li><a href="/HMS-main/views/nurse/emergency.php"><i class="fa fa-ambulance"></i>Emergency</a></li>
      <li><a href="/HMS-main/auth/logout.php"><i class="fa fa-sign-out-alt"></i>Logout</a></li>
    </ul>
  </div>


  <script>
    const dropdownBtns = document.querySelectorAll('.dropdown-btn');
    dropdownBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        btn.classList.toggle('active');
        const dropdownContent = btn.nextElementSibling;
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
      });
    });
  </script>

</body>
</html>

<div class="sidebar">
    <h2>Pharmacist Dashboard</h2>
    <ul>
        <li><a href="/HMS-main/views/pharmacist/dashboard.php">Dashboard</a></li>
        <li><a href="/HMS-main/views/pharmacist/patientmedication.php">Patient Medication</a></li>
        <li><a href="/HMS-main/views/pharmacist/pharmacy.php">Pharmacy</a></li>
        <li><a href="/HMS-main/auth/logout.php">Logout</a></li>
    </ul>
</div>

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
    width: 175px;
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
    margin-bottom: 10px;
    position: relative;
}

.sidebar ul li a {
    color: white;
    text-decoration: none;
    display: block;
    padding: 8px;
    font-size: 1em;
}

.sidebar ul li a:hover {
    background-color: #7a0154;
    border-radius: 4px;
}

.content {
    margin-left: 220px;
    padding: 40px;
}

.dropdown-content {
    display: none;
    list-style-type: none;
    padding-left: 10px;
    background-color:#923f78;
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
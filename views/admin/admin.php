<?php
ob_start();
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
include('../../config/db.php');

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

function getAdmins($conn, $filter = 'all') {
    $baseQuery = "SELECT u.UserID, u.username, u.email, u.full_name, a.superadmin, u.role
                  FROM admin a
                  JOIN users u ON a.UserID = u.UserID";

    if ($filter === 'superadmin') {
        $baseQuery .= " WHERE a.superadmin = 1";
    } elseif ($filter === 'admin') {
        $baseQuery .= " WHERE a.superadmin = 0";
    }
    $result = mysqli_query($conn, $baseQuery);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getNonAdmins($conn) {
    $query = "SELECT UserID, username, email, full_name FROM users WHERE role != 'admin'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function isLastAdmin($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin");
    $count = mysqli_fetch_assoc($result);
    return $count['total'] <= 1;
}

// Promote user to admin
if (isset($_GET['promote']) && is_numeric($_GET['promote'])) {
    $target_id = $_GET['promote'];
    $check = mysqli_query($conn, "SELECT * FROM admin WHERE UserID = '$target_id'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO admin (UserID, superadmin) VALUES ('$target_id', 0)");
        mysqli_query($conn, "UPDATE users SET role = 'admin' WHERE UserID = '$target_id'");
    }
    header("Location: admin.php?filter=$filter");
    exit();
}

// Remove admin
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $target_id = $_GET['remove'];
    $current_user_id = $_SESSION['user_id']; // lowercase user_id, consistent with table check

    $admin_query = mysqli_query($conn, "SELECT superadmin FROM admin WHERE UserID = '$current_user_id'");
    $target_query = mysqli_query($conn, "SELECT superadmin FROM admin WHERE UserID = '$target_id'");

    if (!$admin_query || !$target_query) {
        die("Database query failed: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($admin_query) > 0 && mysqli_num_rows($target_query) > 0) {
        $is_superadmin = mysqli_fetch_assoc($admin_query)['superadmin'];
        $is_target_superadmin = mysqli_fetch_assoc($target_query)['superadmin'];

        if ($current_user_id == $target_id) {
            $error = "You cannot remove yourself as admin.";
        } elseif (isLastAdmin($conn)) {
            $error = "You cannot remove the last remaining admin.";
        } elseif (!$is_superadmin && $is_target_superadmin) {
            $error = "Only superadmins can remove other superadmins.";
        } else {
            $delete1 = mysqli_query($conn, "DELETE FROM admin WHERE UserID = '$target_id'");
            $delete2 = mysqli_query($conn, "UPDATE users SET role = 'user' WHERE UserID = '$target_id'");

            if (!$delete1 || !$delete2) {
                die("Failed to remove admin: " . mysqli_error($conn));
            }

            // Removed admin actions logging to avoid fopen errors

            // Redirect after success
            header("Location: admin.php?filter=$filter");
            exit();
        }
    }
}

$admins = getAdmins($conn, $filter);
$nonAdmins = getNonAdmins($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Management</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
         body {
    font-family: Arial, sans-serif;
    background-color: #ffffff;
}

.content {
    padding: 40px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
}

th {
    background-color: #f8f9fa;
}

form input, form button {
    padding: 5px 10px;
    margin-top: 5px;
}

/* Base button style */
button, .filter-buttons button {
    padding: 10px 20px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    color: white;
    user-select: none;
}

/* View button style */
button.view-btn {
    background: linear-gradient(135deg, #7b5cff, #6f42c1);
    box-shadow: 0 4px 15px rgba(111, 66, 193, 0.4);
}

button.view-btn:hover {
    background: linear-gradient(135deg, #5931c3, #512da8);
    box-shadow: 0 6px 20px rgba(81, 45, 168, 0.6);
}

/* Filter buttons base */
.filter-buttons button {
    background-color: #e0e7ff;
    color: #3f51b5;
    box-shadow: 0 2px 6px rgba(63, 81, 181, 0.2);
    margin-right: 10px; /* keep margin */
}

.filter-buttons button:hover {
    background-color: #c5cee8;
    box-shadow: 0 4px 12px rgba(63, 81, 181, 0.35);
}

.filter-buttons button.active {
    background-color: #3f51b5;
    color: white;
    box-shadow: 0 6px 15px rgba(63, 81, 181, 0.6);
}

/* Modal styles (based on your patient details page) */
.modal {
    position: fixed;
    z-index: 999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    display: none;
    justify-content: center;
    align-items: center;
}

.modal-content {
    border: 2px solid purple;
    border-radius: 12px;
    padding: 40px;
    background-color: #fff;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: 0 0 12px rgba(0,0,0,0.05);
    position: relative;
}

.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #888;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

.profile-img {
    width: 100px;
    height: 100px;
    margin: 0 auto 30px;
    border-radius: 50%;
    background-color: #f0f0f0;
    display: flex;
    justify-content: center;
    align-items: center;
}

.profile-img img {
    width: 60px;
    height: 60px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin: 12px 0;
    font-size: 16px;
    color: #555;
}

.info-row strong {
    font-weight: 600;
    color: #444;
}

.back-link {
    display: inline-block;
    margin-top: 30px;
    text-decoration: none;
    color: #fff;
    background-color: #6f42c1;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.back-link:hover {
    background-color: #512da8;
}

.delete-link {
    color: red;
}

 </style>
</head>
<body>
<div class="content">
    <h2>Admin Management</h2>
    
    <!-- Filter Buttons -->
    <div class="filter-buttons">
        <a href="admin.php?filter=all"><button class="<?= $filter === 'all' ? 'active' : '' ?>">All Admins</button></a>
        <a href="admin.php?filter=superadmin"><button class="<?= $filter === 'superadmin' ? 'active' : '' ?>">Superadmins</button></a>
        <a href="admin.php?filter=admin"><button class="<?= $filter === 'admin' ? 'active' : '' ?>">Admins</button></a>
    </div>
    <br>
    
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <table border="1" cellspacing="0" cellpadding="10">
        <thead>
        <tr>
            <th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Superadmin</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?= $admin['UserID'] ?></td>
                <td><?= htmlspecialchars($admin['username']) ?></td>
                <td><?= htmlspecialchars($admin['full_name']) ?></td>
                <td><?= htmlspecialchars($admin['email']) ?></td>
                <td><?= htmlspecialchars($admin['role']) ?></td>
                <td><?= $admin['superadmin'] ? 'Yes' : 'No' ?></td>
                <td>
                    <?php if ($_SESSION['UserID'] != $admin['UserID']): ?>
                        <a href="admin.php?remove=<?= $admin['UserID'] ?>&filter=<?= $filter ?>" onclick="return confirm('Are you sure you want to remove admin rights from <?= htmlspecialchars($admin['username']) ?>? This cannot be undone.');">Remove</a>
                    <?php else: ?>
                        (You)
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Promote Users to Admin</h2>
    <?php if (count($nonAdmins) === 0): ?>
        <p>All users are already admins.</p>
    <?php else: ?>
    <table border="1" cellspacing="0" cellpadding="10">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($nonAdmins as $user): ?>
            <tr>
                <td><?= $user['UserID'] ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['full_name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                    <a href="admin.php?promote=<?= $user['UserID'] ?>&filter=<?= $filter ?>"
                       onclick="return confirm('Promote <?= htmlspecialchars($user['username']) ?> to admin?');">
                       Promote to Admin
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>
</body>
</html>

<?php ob_end_flush(); ?>

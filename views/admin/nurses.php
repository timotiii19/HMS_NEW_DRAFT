<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update nurse
if (isset($_POST['update_nurse'])) {
    $nurse_id = $_POST['nurse_id'];
    $availability = $_POST['availability'];
    $contact = $_POST['contact'];

    // Update nurse availability
    $stmt = $conn->prepare("UPDATE nurse SET Availability=? WHERE NurseID=?");
    $stmt->bind_param("si", $availability, $nurse_id);
    $stmt->execute();

    // Get UserID to update users table
    $result = $conn->query("SELECT UserID FROM nurse WHERE NurseID = $nurse_id");
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['UserID'];

        // Update contact in users table
        $stmt2 = $conn->prepare("UPDATE users SET ContactNumber=? WHERE UserID=?");
        $stmt2->bind_param("si", $contact, $user_id);
        $stmt2->execute();
    }

    header("Location: nurses.php");
    exit();
}

// Delete nurse
if (isset($_GET['delete'])) {
    $nurse_id = $_GET['delete'];
    $result = $conn->query("SELECT UserID FROM nurse WHERE NurseID = $nurse_id");
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['UserID'];
        $conn->query("DELETE FROM nurse WHERE NurseID = $nurse_id");
        $conn->query("DELETE FROM users WHERE UserID = $user_id");
    }
    header("Location: nurses.php");
    exit();
}

// Fetch nurses
$result = $conn->query("SELECT n.NurseID, u.username AS NurseName, u.email AS Email, u.ContactNumber, n.Availability, n.DepartmentID
                        FROM nurse n
                        JOIN users u ON n.UserID = u.UserID");

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Nurses Management</title>
<link rel="stylesheet" href="../../css/style.css" />
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

    button.view-btn {
        background-color: #6f42c1;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
    }

    button.view-btn:hover {
        background-color: #512da8;
    }

    /* Modal styles */
    .modal {
        position: fixed;
        z-index: 9999;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.5);
        display: none;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: #fff;
        padding: 30px;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        position: relative;
        box-shadow: 0 0 15px rgba(0,0,0,0.3);
        text-align: left;
    }

    .close {
        position: absolute;
        top: 12px;
        right: 15px;
        font-size: 28px;
        font-weight: bold;
        color: #888;
        cursor: pointer;
    }

    .close:hover {
        color: #000;
    }

    .edit-link {
        color: #007bff;
        cursor: pointer;
        text-decoration: underline;
    }

    .edit-link:hover {
        text-decoration: none;
    }

    .delete-link {
        color: #dc3545;
        text-decoration: underline;
        cursor: pointer;
    }

    .delete-link:hover {
        text-decoration: none;
    }
</style>
</head>
<body>
<div class="content">
    <h2>Nurse Management</h2>
    <table>
        <tr>
            <th>NurseID</th>
            <th>NurseName</th>
            <th>Email</th>
            <th>Availability</th>
            <th>ContactNumber</th>
            <th>Department</th>
            <th>Action</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['NurseID'] ?></td>
                    <td><?= htmlspecialchars($row['NurseName']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td><?= htmlspecialchars($row['Availability']) ?></td>
                    <td><?= htmlspecialchars($row['ContactNumber']) ?></td>
                    <td><?= htmlspecialchars($row['DepartmentID']) ?></td>
                    <td>
                        <span class="edit-link" 
                              onclick="showEditForm(
                                <?= $row['NurseID'] ?>,
                                '<?= addslashes(htmlspecialchars($row['Availability'])) ?>',
                                '<?= addslashes(htmlspecialchars($row['ContactNumber'])) ?>',
                                '<?= addslashes(htmlspecialchars($row['Email'])) ?>'
                              )">Edit</span>
                        |
                        <a href="?delete=<?= $row['NurseID'] ?>" class="delete-link" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No nurse records found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- Modal Overlay -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h3>Edit Nurse Details</h3>
    <form id="editForm" method="post" action="nurses.php">
      <input type="hidden" name="nurse_id" id="nurse_id" value="">
      <div class="form-group">
        <label>Availability</label>
        <input type="text" name="availability" id="availability" required>
      </div>
      <div class="form-group">
        <label>Contact Number</label>
        <input type="text" name="contact" id="contact" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="email" disabled>
      </div>
      <button type="submit" name="update_nurse" class="save-btn">Save Changes</button>
    </form>
  </div>
</div>

<script>
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function showEditForm(nurseID, availability, contact, email) {
        const modal = document.getElementById('editModal');
        modal.style.display = 'flex';

        document.getElementById('nurse_id').value = nurseID;
        document.getElementById('availability').value = availability;
        document.getElementById('contact').value = contact;
        document.getElementById('email').value = email;
    }

    // Close modal when clicking outside the modal-content
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeModal();
        }
    };
</script>
</body>
</html>

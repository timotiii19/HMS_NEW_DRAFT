<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../config/db.php');

// Handle update
if (isset($_POST['update_cashier'])) {
    $cashier_id = $_POST['cashier_id'];
    $name = $_POST['name'];

    $stmt = $conn->prepare("UPDATE cashier SET Name=? WHERE CashierID=?");
    $stmt->bind_param("si", $name, $cashier_id);
    $stmt->execute();
    header("Location: cashier.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM cashier WHERE CashierID = $id");
    header("Location: cashier.php");
    exit();
}

// Fetch cashiers
$result = $conn->query("SELECT * FROM cashier");
include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Casheirs Management</title>
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
        }

        .back-link:hover {
            background-color: #512da8;
        }

        .edit-link {
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
            margin-right: 10px;
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
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 10px; right: 10px;
            cursor: pointer;
            font-size: 20px;
        }
  </style>
</head>
<body>

<div class="content">
    <h2>Cashier Management</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cashier Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['CashierID'] ?></td>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td>
                                <span class="edit-link" onclick="openModal(
                                    <?= $row['CashierID'] ?>,
                                    '<?= htmlspecialchars($row['Name'], ENT_QUOTES) ?>'
                                )">Edit</span>
                                |
                                <a href="?delete=<?= $row['CashierID'] ?>" class="delete-link" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No cashier records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">×</span>
        <h3>Edit Cashier Details</h3>
        <form method="post" action="cashier.php">
            <input type="hidden" name="cashier_id" id="modal_cashier_id">

            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" id="modal_name" required>
            </div>
            <button type="submit" name="update_cashier" class="sbtn">Save Changes</button>
        </form>
    </div>
</div>

<script>
function openModal(id, name) {
    document.getElementById('modal_cashier_id').value = id;
    document.getElementById('modal_name').value = name;
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

</body>
</html>

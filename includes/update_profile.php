<?php
session_start();
header('Content-Type: application/json');
include('../config/db.php');

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}


$userId = $_SESSION['UserID'];
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$contact = trim($_POST['contact'] ?? '');

// Validate input
if (!$full_name || !$email || !$contact) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Update user data in the database
$stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, ContactNumber = ? WHERE UserID = ?");
$stmt->bind_param("sssi", $full_name, $email, $contact, $userId);

if ($stmt->execute()) {
    // Update session values
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['ContactNumber'] = $contact;

    // Get updated role
    $stmt2 = $conn->prepare("SELECT role FROM users WHERE UserID = ?");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $role = '';
    if ($row = $result->fetch_assoc()) {
        $role = $row['role'];
        $_SESSION['role'] = $role;
    }

    echo json_encode([
        'success' => true,
        'full_name' => $full_name,
        'email' => $email,
        'contact' => $contact,
        'role' => $role
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
?>

<?php
require '../../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include('../../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get role from POST (from filter dropdown)
$role = $_POST['role'] ?? 'all';

// Filter query based on role
if ($role === 'all') {
    $sql = "SELECT * FROM users";
    $result = $conn->query($sql);
} else {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = ?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Check for query success
if (!$result) {
    die("Query failed: " . $conn->error);
}

// HTML for PDF
$html = '
<h2>User Report - ' . ucfirst($role) . '</h2>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <tr>
        <th>User ID</th>
        <th>Username</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Role</th>
    </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($row['UserID']) . '</td>
        <td>' . htmlspecialchars($row['username']) . '</td>
        <td>' . htmlspecialchars($row['full_name']) . '</td>
        <td>' . htmlspecialchars($row['email']) . '</td>
        <td>' . htmlspecialchars($row['role']) . '</td>
    </tr>';
}

$html .= '</table>';

// Create and render PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output to browser (viewable, not forced download)
$dompdf->stream("user_report.pdf", ["Attachment" => 0]);
exit();
?>
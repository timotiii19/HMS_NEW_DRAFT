<?php
require '../../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include('../../config/db.php');

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM patientbilling");

// Check if the query was successful
if (!$result) {
    die("Query failed: " . $conn->error);
}

$html = '
<h2>Billing Summary Report</h2>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Patient Name</th>
        <th>Doctor Fee</th>
        <th>Medicine Cost</th>
        <th>Total Amount</th>
    </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($row['id']) . '</td>
        <td>' . htmlspecialchars($row['patient_name']) . '</td>
        <td>' . htmlspecialchars($row['doctor_fee']) . '</td>
        <td>' . htmlspecialchars($row['medicine_cost']) . '</td>
        <td>' . htmlspecialchars($row['total_amount']) . '</td>
    </tr>';
}

$html .= '</table>';

$dompdf = new Dompdf();

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size (A4) and orientation (landscape)
$dompdf->setPaper('A4', 'landscape');

// Render PDF (first pass to parse HTML)
$dompdf->render();

// Stream the PDF file to the browser
$dompdf->stream("billing_summary.pdf", ["Attachment" => 0]);

// Exit the script
exit();
?>

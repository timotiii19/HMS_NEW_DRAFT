<?php
require '../../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include('../../config/db.php');

if (!isset($_GET['id'])) {
    die("Missing ID");
}

$id = intval($_GET['id']);
$query = "
    SELECT lp.*, p.Name AS PatientName, d.DoctorName 
    FROM labprocedure lp
    JOIN patients p ON lp.PatientID = p.PatientID
    JOIN doctor d ON lp.DoctorID = d.DoctorID
    WHERE LabReqID = $id
";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("No data found.");
}

$dompdf = new Dompdf();

$html = "<h1>Lab Result</h1>
<hr>
<p><strong>Patient Name:</strong> {$data['PatientName']}</p>
<p><strong>Doctor:</strong> Dr. {$data['DoctorName']}</p>
<p><strong>Test Date:</strong> {$data['TestDate']}</p>
<p><strong>Procedure:</strong> {$data['ProcedureName']}</p>
<p><strong>Status:</strong> {$data['Status']}</p>
<p><strong>Result:</strong> {$data['Result']}</p>
<p><strong>Date Released:</strong> {$data['DateReleased']}</p>";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("LabResult_{$data['LabReqID']}.pdf", array("Attachment" => 0));
exit();

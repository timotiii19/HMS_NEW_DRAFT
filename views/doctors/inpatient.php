<?php
session_start();
include('../../config/db.php');
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

// Handle AJAX POST for assigning location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_location') {
    $inpatientId = intval($_POST['inpatientId'] ?? 0);
    $locationId = intval($_POST['locationId'] ?? 0);

    if (!$inpatientId || !$locationId) {
        http_response_code(400);
        echo "Invalid input.";
        exit;
    }

    $conn->begin_transaction();

    try {
        // Lock the location row for update to prevent race conditions
        $stmt = $conn->prepare("SELECT RoomCapacity FROM locations WHERE LocationID = ? FOR UPDATE");
        $stmt->bind_param("i", $locationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $location = $result->fetch_assoc();
        $stmt->close();

        if (!$location) {
            throw new Exception("Location not found.");
        }

        // Count occupied beds at location (only currently admitted patients)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as occupied 
            FROM inpatients 
            WHERE LocationID = ? AND (DischargeDate IS NULL OR DischargeDate > CURRENT_DATE())
        ");
        $stmt->bind_param("i", $locationId);
        $stmt->execute();
        $res = $stmt->get_result();
        $countData = $res->fetch_assoc();
        $stmt->close();

        if ($countData['occupied'] >= $location['RoomCapacity']) {
            throw new Exception("Selected location is full.");
        }

        // Update inpatient's assigned location
        $stmt = $conn->prepare("UPDATE inpatients SET LocationID = ? WHERE InpatientID = ?");
        $stmt->bind_param("ii", $locationId, $inpatientId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update inpatient location.");
        }
        $stmt->close();

        $conn->commit();
        echo "success";
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo "Failed: " . $e->getMessage();
    }
    exit;
}

// Insert new inpatients from doctorschedule where Status = 'Inpatient'
$sql = "SELECT PatientID, DoctorID, DepartmentID, EndTime FROM doctorschedule WHERE Status = 'Inpatient'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patientID = $row['PatientID'];
        $doctorID = $row['DoctorID'];
        $departmentID = $row['DepartmentID'];
        $admissionDate = $row['EndTime'];

        $check = $conn->prepare("SELECT * FROM inpatients WHERE PatientID = ? AND DoctorID = ?");
        $check->bind_param("ii", $patientID, $doctorID);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO inpatients (PatientID, DoctorID, DepartmentID, AdmissionDate, DischargeDate, MedicalRecord, LocationID) VALUES (?, ?, ?, ?, NULL, NULL, NULL)");
            $stmt->bind_param("iiis", $patientID, $doctorID, $departmentID, $admissionDate);
            $stmt->execute();
            $stmt->close();
        }
        $check->close();
    }
}

// Fetch all inpatients
$inpatients = $conn->query("SELECT * FROM inpatients ORDER BY AdmissionDate DESC");

// Fetch all locations grouped by building and floor with occupied count
$locationsSql = "
    SELECT 
        l.LocationID, l.LocationName, l.ConditionType, l.RoomType, l.RoomCapacity,
        l.Building, l.Floor, l.RoomNumber,
        IFNULL(inpatient_counts.occupied, 0) AS OccupiedBeds
    FROM locations l
    LEFT JOIN (
        SELECT LocationID, COUNT(*) AS occupied
        FROM inpatients
        WHERE DischargeDate IS NULL OR DischargeDate > NOW()
        GROUP BY LocationID
    ) inpatient_counts ON inpatient_counts.LocationID = l.LocationID
    ORDER BY l.Building, l.Floor, l.RoomNumber
";

$locationsResult = $conn->query($locationsSql);

// Organize locations by building and floor for tabs
$locations = [];
if ($locationsResult) {
    while ($loc = $locationsResult->fetch_assoc()) {
        $building = $loc['Building'];
        $floor = $loc['Floor'];
        $locations[$building][$floor][] = $loc;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Inpatients - Assign Location</title>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
}
.tabs {
    display: flex;
    margin-bottom: 10px;
}
.tab {
    padding: 10px 20px;
    margin-right: 5px;
    background: #eb6d9b;
    color: white;
    cursor: pointer;
    border-radius: 5px 5px 0 0;
}
.tab.active {
    background: #c13d70;
}
.floor-section {
    border: 1px solid #ddd;
    margin-bottom: 15px;
    border-radius: 0 5px 5px 5px;
    padding: 10px;
}
.floor-header {
    font-weight: bold;
    cursor: pointer;
    margin-bottom: 10px;
}
.floor-header:hover {
    color: #c13d70;
}
.room-table {
    width: 100%;
    border-collapse: collapse;
}
.room-table th, .room-table td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
}
.room-table th {
    background-color: #f5a4bf;
    color: white;
}
.room-available {
    background-color: #d4edda;
    color: #155724;
}
.room-full {
    background-color: #f8d7da;
    color: #721c24;
}
.assign-btn {
    padding: 5px 10px;
    cursor: pointer;
    background-color: #4caf50;
    border: none;
    color: white;
    border-radius: 3px;
}
.assign-btn:disabled {
    background-color: #aaa;
    cursor: not-allowed;
}
.inpatients-table {
    margin-bottom: 40px;
    width: 100%;
    border-collapse: collapse;
}
.inpatients-table th, .inpatients-table td {
    border: 1px solid #ccc;
    padding: 6px;
    text-align: center;
}
.inpatients-table th {
    background-color: #eb6d9b;
    color: white;
}
</style>
</head>
<body>

<h2>Current Inpatients</h2>
<table class="inpatients-table">
    <thead>
        <tr>
            <th>InpatientID</th>
            <th>PatientID</th>
            <th>DoctorID</th>
            <th>DepartmentID</th>
            <th>AdmissionDate</th>
            <th>DischargeDate</th>
            <th>MedicalRecord</th>
            <th>Assigned Location</th>
            <th>Assign Room</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $inpatients->fetch_assoc()): ?>
        <tr data-inpatientid="<?= htmlspecialchars($row['InpatientID']) ?>">
            <td><?= htmlspecialchars($row['InpatientID']) ?></td>
            <td><?= htmlspecialchars($row['PatientID']) ?></td>
            <td><?= htmlspecialchars($row['DoctorID']) ?></td>
            <td><?= htmlspecialchars($row['DepartmentID']) ?></td>
            <td><?= htmlspecialchars($row['AdmissionDate']) ?></td>
            <td><?= htmlspecialchars($row['DischargeDate'] ?? 'Pending') ?></td>
            <td><?= htmlspecialchars($row['MedicalRecord'] ?? '') ?></td>
            <td>
                <?= $row['LocationID'] ? "ID: " . htmlspecialchars($row['LocationID']) : "Not assigned" ?>
            </td>
            <td>
                <button class="assign-room-btn" data-inpatientid="<?= htmlspecialchars($row['InpatientID']) ?>">Assign Room</button>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<h2>Assign Rooms</h2>

<div class="tabs" id="buildingTabs">
    <?php
    $buildings = array_keys($locations);
    foreach ($buildings as $index => $building) {
        $activeClass = $index === 0 ? "active" : "";
        echo "<div class='tab $activeClass' data-building='$building'>Building $building</div>";
    }
    ?>
</div>

<div id="floorsContainer">
<?php
foreach ($locations as $building => $floors) {
    $style = ($building === $buildings[0]) ? "" : "style='display:none'";
    echo "<div class='building-floors' data-building='$building' $style>";
    foreach ($floors as $floor => $rooms) {
        echo "<div class='floor-section'>";
        echo "<div class='floor-header'>Floor $floor</div>";
        echo "<table class='room-table'>";
        echo "<thead><tr><th>Room #</th><th>Type</th><th>Capacity</th><th>Occupied</th><th>Status</th><th>Assign</th></tr></thead><tbody>";
        foreach ($rooms as $room) {
            $availableBeds = $room['RoomCapacity'] - $room['OccupiedBeds'];
            $isFull = $availableBeds <= 0;
            $statusClass = $isFull ? 'room-full' : 'room-available';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($room['RoomNumber']) . "</td>";
            echo "<td>" . htmlspecialchars($room['RoomType']) . "</td>";
            echo "<td>" . intval($room['RoomCapacity']) . "</td>";
            echo "<td>" . intval($room['OccupiedBeds']) . "</td>";
            echo "<td class='$statusClass'>" . ($isFull ? "Full" : "Available") . "</td>";
            echo "<td>";
            $btnDisabled = $isFull ? "disabled" : "";
            echo "<button class='assign-room-btn' data-locationid='" . intval($room['LocationID']) . "' $btnDisabled>Assign</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
    }
    echo "</div>";
}
?>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabs = document.querySelectorAll('.tab');
    const floorsContainer = document.getElementById('floorsContainer');
    let selectedInpatientId = null;

    // Switch building tabs
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const building = tab.getAttribute('data-building');
            [...floorsContainer.children].forEach(div => {
                div.style.display = (div.getAttribute('data-building') === building) ? '' : 'none';
            });
        });
    });

    // Track which inpatient is selected for assignment
    document.querySelectorAll('.inpatients-table .assign-room-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            selectedInpatientId = btn.getAttribute('data-inpatientid');
            alert("Select a room from below to assign to Inpatient ID: " + selectedInpatientId);
        });
    });

    // Assign room buttons in room tables
    floorsContainer.querySelectorAll('.assign-room-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!selectedInpatientId) {
                alert("Please select an inpatient first by clicking 'Assign Room' next to their name.");
                return;
            }

            const locationId = btn.getAttribute('data-locationid');
            if (!locationId) {
                alert("Invalid room selection.");
                return;
            }

            btn.disabled = true;
            btn.textContent = "Assigning...";

            fetch("", {
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=assign_location&inpatientId=${encodeURIComponent(selectedInpatientId)}&locationId=${encodeURIComponent(locationId)}`
            })
            .then(response => response.text())
            .then(text => {
                if (text.trim() === "success") {
                    alert("Location assigned successfully.");

                    // Find the row for this room
                    const rows = floorsContainer.querySelectorAll('tr');
                    rows.forEach(row => {
                        const assignBtn = row.querySelector('.assign-room-btn');
                        if (assignBtn && assignBtn.getAttribute('data-locationid') === locationId) {
                            // Update occupied beds
                            let occupiedCell = row.cells[3];
                            let capacityCell = row.cells[2];
                            let statusCell = row.cells[4];

                            let occupied = parseInt(occupiedCell.textContent);
                            let capacity = parseInt(capacityCell.textContent);

                            occupied += 1;
                            occupiedCell.textContent = occupied;

                            // Update status & button if full
                            if (occupied >= capacity) {
                                statusCell.textContent = "Full";
                                statusCell.classList.remove('room-available');
                                statusCell.classList.add('room-full');

                                assignBtn.disabled = true;
                                assignBtn.textContent = "Full";
                            } else {
                                statusCell.textContent = "Available";
                                statusCell.classList.remove('room-full');
                                statusCell.classList.add('room-available');

                                assignBtn.disabled = false;
                                assignBtn.textContent = "Assign";
                            }
                        }
                    });

                    // Update inpatient table assigned location
                    const inpatientRow = document.querySelector(`.inpatients-table tr[data-inpatientid="${selectedInpatientId}"]`);
                    if (inpatientRow) {
                        inpatientRow.cells[7].textContent = `ID: ${locationId}`;
                    }

                    // Reset selection
                    selectedInpatientId = null;
                } else {
                    alert("Error assigning location: " + text);
                    btn.disabled = false;
                    btn.textContent = "Assign";
                }
            })
            .catch(err => {
                alert("Error: " + err);
                btn.disabled = false;
                btn.textContent = "Assign";
            });
        });
    });
});
</script>

</body>
</html>

<?php
ob_start();
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Cashier') {
    header("Location: ../../auth/cashier_login.php");
    exit();
}
include('../../includes/cashier_header.php');
include('../../includes/cashier_sidebar.php');
include('../../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Medicine
$medicines_result = $conn->query("SELECT MedicineID, MedicineName, Price FROM Pharmacy");

// Fetch doctors
$doctors_result = $conn->query("SELECT DoctorID, DoctorName, DoctorFee FROM doctor");

// Fetch patients
$patients_result = $conn->query("SELECT PatientID, Name FROM patients");

// Generate receipt number
$result = $conn->query("SELECT MAX(CAST(Receipt AS UNSIGNED)) AS last_receipt FROM patientbilling");
$row = $result->fetch_assoc();
$last_receipt = $row['last_receipt'] ?? 0;
$new_receipt_number = str_pad($last_receipt + 1, 6, '0', STR_PAD_LEFT);

// Add bill
if (isset($_POST['add_bill'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_fee = (float) $_POST['doctor_fee'];
    $medicine_total = (float) $_POST['medicine_total'];
    $total = $doctor_fee + $medicine_total;
    $payment_date = $_POST['payment_date'];
    $receipt = $_POST['receipt'];
    $doctor_id = $_POST['doctor_id'];

    $stmt = $conn->prepare("INSERT INTO patientbilling (PatientID, DoctorID, DoctorFee, MedicineCost, TotalAmount, PaymentDate, Receipt) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidddss", $patient_id, $doctor_id, $doctor_fee, $medicine_total, $total, $payment_date, $receipt);
    $stmt->execute();
    header("Location: patient_billing.php");
    exit();
}


// Update bill
if (isset($_POST['update_bill'])) {
    $billing_id = $_POST['billing_id'];  // <-- get billing ID

    $patient_id = $_POST['patient_id'];
    $doctor_fee = (float) $_POST['doctor_fee'];
    $medicine_total = (float) $_POST['medicine_total'];
    $total = $doctor_fee + $medicine_total;
    $payment_date = $_POST['payment_date'];
    $receipt = $_POST['receipt'];
    $doctor_id = $_POST['doctor_id'];

    $stmt = $conn->prepare("UPDATE patientbilling SET PatientID=?, DoctorID=?, DoctorFee=?, MedicineCost=?, TotalAmount=?, PaymentDate=?, Receipt=? WHERE BillingID=?");
    $stmt->bind_param("iidddssi", $patient_id, $doctor_id, $doctor_fee, $medicine_total, $total, $payment_date, $receipt, $billing_id);

    if (!$stmt->execute()) {
        die("Update failed: " . $stmt->error);
    }

    header("Location: patient_billing.php");
    exit();
}


// Delete bill
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT PatientID FROM patientbilling WHERE BillingID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $patientID = $row['PatientID'];

        $stmt = $conn->prepare("DELETE FROM patientbilling WHERE BillingID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        header("Location: patient_billing.php?patient_id=$patientID");
        exit();
    } else {
        echo "Error: Billing entry not found.";
    }
}



// Fetch bills
$bills_result = $conn->query("SELECT b.*, p.PatientID, p.Name AS PatientName, d.DoctorID, d.DoctorName
FROM patientbilling b
JOIN patients p ON b.PatientID = p.PatientID
JOIN doctor d ON b.DoctorID = d.DoctorID;");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Management</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }

    .content {
        padding: 40px;
        max-width: 820px;
        margin-left: 210px; / space for sidebar /
        margin-top: 20px;
    }

    table {
        width: 150%;
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

    form input, form select, form button {
        padding: 8px 12px;
        margin-top: 5px;
        width: 150%;
        box-sizing: border-box;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
    }

    .modal-content input,
    .modal-content select,
    .modal-content button {
        width: 100% !important;
    }



    form label {
        margin-top: 15px;
        display: block;
        font-weight: 600;
        color: #333;
    }

    button.btn-primary {
        background-color: #6f42c1;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 12px 20px;
        cursor: pointer;
        margin-top: 20px;
        width: auto;
        font-size: 16px;
    }

    button.btn-primary:hover {
        background-color: #512da8;
    }

    / Modal styles */
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
            width: 500px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 10px; right: 10px;
            cursor: pointer;
            font-size: 20px;
        }
            .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-edit {
        background-color: #4CAF50; /* Green */
        color: white;
    }

    .btn-delete {
        background-color: #eb6d9b; /* Red */
        color: white;
    }

    .btn-edit:hover {
        background-color: #45a049;
    }

    .btn-delete:hover {
        background-color: #d32f2f;
    }
    .modal-grid {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
    }

    .modal-grid > div {
        flex: 1 1 45%;
    }

    #modal_selected_medicines {
        list-style: none;
        padding-left: 0;
        max-height: 150px;
        overflow-y: auto;
        margin-top: 10px;
    }

    #modal_selected_medicines li {
        background-color: #f8f8f8;
        border: 1px solid #ccc;
        padding: 6px 10px;
        margin-bottom: 6px;
        border-radius: 4px;
        font-size: 14px;
    }


    </style>
</head>
<body>

<div class="content">
    <h2>Billing Management</h2>

    <form method="post" action="">
        <label>Patient Name:</label>
        <input 
            list="patientList" 
            name="patient_id" 
            placeholder="Select Patient" 
            required>
        <datalist id="patientList">
            <?php
            $patients_result->data_seek(0);
            while ($p = $patients_result->fetch_assoc()) {
                echo "<option value='{$p['PatientID']} - " . htmlspecialchars($p['Name']) . "'>";
            }
            ?>
        </datalist>

        <label>Doctor Name:</label>
        <input 
            list="doctorList" 
            id="doctorSearch" 
            name="doctor_id" 
            placeholder="Select Doctor" 
            onchange="setDoctorFee()" 
            required>

        <datalist id="doctorList">
            <?php
            $doctors_result->data_seek(0);
            while ($d = $doctors_result->fetch_assoc()) {
                echo "<option value='{$d['DoctorID']} - " . htmlspecialchars($d['DoctorName']) . "' data-fee='{$d['DoctorFee']}'>";
            }
            ?>
        </datalist>

        <label>Doctor Fee:</label>
        <input type="text" id="doctorFeeDisplay" readonly placeholder="₱">
        <input type="hidden" name="doctor_fee" id="doctorFee" required>

        <label>Search Medicine:</label>
        <input list="medicineList" id="medicineSearch" placeholder="Type to search..." onkeydown="handleEnter(event)">
        <button type="button" onclick="addMedicine()">Add</button>

        <ul id="selectedMedicines">
            <!-- Selected medicines will be listed here -->
        </ul>

        <button type="button" onclick="calculateTotal()">Done</button>

        <label>Medicine Total:</label>
        <input type="text" id="medicineCostDisplay" readonly>
        <input type="hidden" name="medicine_total" id="medicineCost" required>

        <datalist id="medicineList">
        <?php
        $medicines_result->data_seek(0);
        while ($med = $medicines_result->fetch_assoc()) {
            echo "<option value='{$med['MedicineID']} - " . htmlspecialchars($med['MedicineName']) . "' data-price='{$med['Price']}'></option>";
        }
        ?>
        </datalist>

        <label>Payment Date:</label>
        <input type="date" name="payment_date" required>

        <label>Receipt Number:</label>
        <input type="text" name="receipt" value="<?php echo $new_receipt_number; ?>" readonly>

        <button type="submit" name="add_bill" style="padding: 15px 26px; background-color: #eb6d9b; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px;">
            Add Bill
        </button>
    </form>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Doctor Fee</th>
            <th>Medicine Cost</th>
            <th>Total Amount</th>
            <th>Payment Date</th>
            <th>Receipt</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $bills_result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['BillingID'] ?></td>
                <td><?= htmlspecialchars($row['PatientName']) ?></td>
                <td><?= htmlspecialchars($row['DoctorName']) ?></td>
                <td>₱<?= number_format($row['DoctorFee'], 2) ?></td>
                <td>₱<?= number_format($row['MedicineCost'], 2) ?></td>
                <td>₱<?= number_format($row['TotalAmount'], 2) ?></td>
                <td><?= htmlspecialchars($row['PaymentDate']) ?></td>
                <td><?= htmlspecialchars($row['Receipt']) ?></td>
                <td>

                <!-- Inside your billing table -->
                <button 
                    class="edit-btn"
                    onclick="openEditModal(this)"
                    data-billing-id="<?= $row['BillingID'] ?>"
                    data-patient="<?= $row['PatientID'] ?> - <?= htmlspecialchars($row['PatientName']) ?>"
                    data-doctor="<?= $row['DoctorID'] ?> - <?= htmlspecialchars($row['DoctorName']) ?>"
                    data-doctor-fee="<?= $row['DoctorFee'] ?>"
                    data-medicine-total="<?= $row['MedicineCost'] ?>"
                    data-payment-date="<?= $row['PaymentDate'] ?>"
                    data-receipt="<?= $row['Receipt'] ?>">
                    Edit
                </button>
                
                    <a class="btn btn-delete" href="?delete=<?= $row['BillingID'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Edit Billing Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">×</span>
        <h3>Edit Billing</h3>

        <form method="post" action="patient_billing.php">
            <input type="hidden" name="billing_id" id="modal_billing_id">

            <div class="modal-grid">
                <!-- Left Column -->
                <div>
                    <label>Patient Name:</label>
                    <input 
                        list="patientList" 
                        name="patient_id" 
                        id="modal_patient_input"
                        placeholder="Select Patient" 
                        required>
                    <datalist id="patientList">
                        <?php
                        $patients_result->data_seek(0);
                        while ($p = $patients_result->fetch_assoc()) {
                            echo "<option value='{$p['PatientID']} - " . htmlspecialchars($p['Name']) . "'>";
                        }
                        ?>
                    </datalist>

                    <label>Doctor Name:</label>
                    <input 
                        list="doctorList" 
                        id="modal_doctor_input" 
                        name="doctor_id" 
                        placeholder="Select Doctor" 
                        onchange="setDoctorFee('modal_doctor_input', 'modal_doctor_fee_display', 'modal_doctor_fee')" 
                        required>
                    <datalist id="doctorList">
                        <?php
                        $doctors_result->data_seek(0);
                        while ($d = $doctors_result->fetch_assoc()) {
                            echo "<option value='{$d['DoctorID']} - " . htmlspecialchars($d['DoctorName']) . "' data-fee='{$d['DoctorFee']}'>"; 
                        }
                        ?>
                    </datalist>

                    <label>Doctor Fee:</label>
                    <input type="text" id="modal_doctor_fee_display" readonly placeholder="₱">
                    <input type="hidden" name="doctor_fee" id="modal_doctor_fee" required>

                    <label>Payment Date:</label>
                    <input type="date" name="payment_date" id="modal_payment_date" required>

                    <label>Receipt Number:</label>
                    <input type="text" name="receipt" id="modal_receipt" readonly>
                </div>

                <!-- Right Column -->
                <div>
                    <label>Search Medicine:</label>
                    <input 
                        list="medicineList" 
                        id="modal_medicine_search" 
                        placeholder="Type to search..." 
                        onkeydown="handleEnter(event, 'modal_selected_medicines')">
                    <button type="button" onclick="addMedicine('modal_medicine_search', 'modal_selected_medicines')">Add</button>

                    <ul id="modal_selected_medicines">
                        <?php
                        if (!empty($selectedMedicines)) {
                            foreach ($selectedMedicines as $medicine) {
                                echo "<li data-id='{$medicine['MedicineID']}' data-name='" . htmlspecialchars($medicine['MedicineName']) . "' data-price='{$medicine['Price']}'>
                                        {$medicine['MedicineName']} - ₱{$medicine['Price']}
                                        <button type='button' onclick='removeMedicine(this)' style='margin-left:10px;'>Remove</button>
                                    </li>";
                            }
                        }
                        ?>
                    </ul>


                   <button type="button" onclick="calculateTotal('selectedMedicines', 'main_medicine_cost_display', 'main_medicine_cost')">Done</button>


                    <label>Total Medicine Cost:</label>
                    <input type="text" id="main_medicine_cost_display" readonly>
                    <input type="hidden" name="medicine_total" id="main_medicine_cost" required>


                    <datalist id="medicineList">
                        <?php
                        $medicines_result->data_seek(0);
                        while ($med = $medicines_result->fetch_assoc()) {
                            echo "<option value='{$med['MedicineID']} - " . htmlspecialchars($med['MedicineName']) . "' data-price='{$med['Price']}'>";
                        }
                        ?>
                    </datalist>
                </div>
            </div>

            <button type="submit" name="update_bill" style="padding: 15px 26px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px;">
                Update Bill
            </button>
        </form>
    </div>
</div>




<script>
// Store selected medicines globally (for both add and edit)
let selectedMedicines = [];
let modalSelectedMedicines = [];

function setDoctorFee(inputId = 'doctorSearch', displayId = 'doctorFeeDisplay', hiddenId = 'doctorFee') {
    const doctorInput = document.getElementById(inputId);
    const doctorList = document.getElementById('doctorList');
    const display = document.getElementById(displayId);
    const hidden = document.getElementById(hiddenId);
    
    const selected = [...doctorList.options].find(option => option.value === doctorInput.value);
    const fee = selected ? selected.getAttribute('data-fee') : '';
    
    display.value = fee ? `₱${parseFloat(fee).toFixed(2)}` : '';
    hidden.value = fee || '';
}

function handleEnter(event, ulId = 'selectedMedicines') {
    if (event.key === 'Enter') {
        event.preventDefault();
        addMedicine(event.target.id, ulId);
    }
}

function addMedicine(inputId = 'medicineSearch', ulId = 'selectedMedicines') {
    const input = document.getElementById(inputId);
    const list = document.getElementById('medicineList');
    const ul = document.getElementById(ulId);
    const value = input.value.trim();
    
    if (!value) return;

    const selected = [...list.options].find(opt => opt.value === value);
    if (!selected) {
        alert("Please select a valid medicine from the list.");
        return;
    }

    const [id] = value.split(' - ');
    const name = selected.textContent.split(' - ')[1];
    const price = parseFloat(selected.getAttribute('data-price'));

    const medicine = { MedicineID: id, MedicineName: name, Price: price };

    // Add to correct array
    const medicineArray = (ulId === 'modal_selected_medicines') ? modalSelectedMedicines : selectedMedicines;
    medicineArray.push(medicine);

    const li = document.createElement('li');
    li.textContent = `${medicine.MedicineName} - ₱${price.toFixed(2)}`;
    li.dataset.id = id;
    li.dataset.name = name;
    li.dataset.price = price;
    
    const removeBtn = document.createElement('button');
    removeBtn.textContent = 'Remove';
    removeBtn.type = 'button';
    removeBtn.style.marginLeft = '10px';
    removeBtn.onclick = () => {
        ul.removeChild(li);
        const index = medicineArray.findIndex(m => m.MedicineID === id);
        if (index > -1) medicineArray.splice(index, 1);
    };

    li.appendChild(removeBtn);
    ul.appendChild(li);
    input.value = '';
}

function calculateTotal(ulId = 'selectedMedicines', displayId = 'medicineCostDisplay', hiddenId = 'medicineCost') {
    const medicineArray = (ulId === 'modal_selected_medicines') ? modalSelectedMedicines : selectedMedicines;
    const total = medicineArray.reduce((sum, m) => sum + m.Price, 0);
    document.getElementById(displayId).value = `₱${total.toFixed(2)}`;
    document.getElementById(hiddenId).value = total.toFixed(2);
}

// Modal Logic
function openEditModal(button) {
    document.getElementById('editModal').style.display = 'block';

    document.getElementById('modal_billing_id').value = button.dataset.billingId;
    document.getElementById('modal_patient_input').value = button.dataset.patient;
    document.getElementById('modal_doctor_input').value = button.dataset.doctor;
    document.getElementById('modal_payment_date').value = button.dataset.paymentDate;
    document.getElementById('modal_receipt').value = button.dataset.receipt;

    setDoctorFee('modal_doctor_input', 'modal_doctor_fee_display', 'modal_doctor_fee');

    document.getElementById('main_medicine_cost_display').value = `₱${parseFloat(button.dataset.medicineTotal).toFixed(2)}`;
    document.getElementById('main_medicine_cost').value = parseFloat(button.dataset.medicineTotal).toFixed(2);

    // Clear and reset modal medicine list
    modalSelectedMedicines = [];
    const ul = document.getElementById('modal_selected_medicines');
    ul.innerHTML = '';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>



</body>
</html>
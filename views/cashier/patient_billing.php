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
$medicines_result = $conn->query("SELECT MedicineID, MedicineName, Price, StockQuantity FROM Pharmacy");

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
    $conn->begin_transaction();
    
    try {
        $patient_parts = explode(' - ', $_POST['patient_id']);
        $patient_id = (int)$patient_parts[0];
        $doctor_parts = explode(' - ', $_POST['doctor_id']);
        $doctor_id = (int)$doctor_parts[0];
        $doctor_fee = (float)$_POST['doctor_fee'];
        $medicine_total = (float)$_POST['medicine_total'];
        $total = $doctor_fee + $medicine_total;
        $payment_date = $_POST['payment_date'];
        $receipt = $_POST['receipt'];

        // Insert billing record
        $stmt = $conn->prepare("INSERT INTO patientbilling (PatientID, DoctorID, DoctorFee, MedicineCost, TotalAmount, PaymentDate, Receipt) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidddss", $patient_id, $doctor_id, $doctor_fee, $medicine_total, $total, $payment_date, $receipt);
        $stmt->execute();

        // Handle medicine stock updates
if (!empty($_POST['medicine_ids']) && !empty($_POST['medicine_quantities'])) {
    // Convert input to arrays consistently
    $medicine_ids = is_array($_POST['medicine_ids']) ? 
        $_POST['medicine_ids'] : 
        explode(',', $_POST['medicine_ids']);
    
    $quantities = is_array($_POST['medicine_quantities']) ? 
        $_POST['medicine_quantities'] : 
        explode(',', $_POST['medicine_quantities']);

    // Debug output
    error_log("Medicine IDs (type: " . gettype($medicine_ids) . "): " . print_r($medicine_ids, true));
    error_log("Quantities (type: " . gettype($quantities) . "): " . print_r($quantities, true));

    // Verify we have valid arrays
    if (!is_array($medicine_ids) || !is_array($quantities)) {
        throw new Exception("Medicine data is not in valid format");
    }

    // Verify counts match
    if (count($medicine_ids) !== count($quantities)) {
        throw new Exception("Medicine IDs and quantities count mismatch");
    }

    for ($i = 0; $i < count($medicine_ids); $i++) {
        // Extract just the numeric ID part (handles "ID - Name" format)
        $med_id = (int)$medicine_ids[$i];
        $qty = (int)$quantities[$i];
        
        // Skip if invalid values
        if ($med_id <= 0 || $qty <= 0) {
            error_log("Skipping invalid entry: MedicineID=$med_id, Quantity=$qty");
            continue;
        }
        
        // Debug output
        error_log("Processing: MedicineID=$med_id, Quantity=-$qty");
        
        $update_stmt = $conn->prepare("UPDATE Pharmacy SET StockQuantity = StockQuantity - ? WHERE MedicineID = ?");
        $update_stmt->bind_param("ii", $qty, $med_id);
        
        if (!$update_stmt->execute()) {
            error_log("Update failed for MedicineID $med_id: " . $update_stmt->error);
            throw new Exception("Failed to update stock for MedicineID $med_id");
        }
        
        // Verify the update worked
        $affected = $update_stmt->affected_rows;
        error_log("Update affected $affected rows for MedicineID $med_id");
        
        $update_stmt->close();
    }
}

        $conn->commit();
        $_SESSION['success_message'] = "Bill added successfully!";
        header("Location: patient_billing.php?patient_id=".$patient_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: patient_billing.php");
        exit();
    }
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
            background: rgba(255, 255, 255, 0.6);
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

    .btn-delete {
        background-color: #eb6d9b; /* Red */
        color: white;
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

<script>
let selectedMedicinePrices = [];

function setDoctorFee() {
    const doctorInput = document.getElementById("doctorSearch");
    const datalist = document.getElementById("doctorList").options;
    const value = doctorInput.value.trim();

    for (let option of datalist) {
        if (option.value === value) {
            const fee = option.getAttribute("data-fee");
            document.getElementById("doctorFeeDisplay").value = "₱" + parseFloat(fee).toFixed(2);
            document.getElementById("doctorFee").value = parseFloat(fee).toFixed(2);
            return;
        }
    }

    document.getElementById("doctorFeeDisplay").value = "";
    document.getElementById("doctorFee").value = "";
}

function handleEnter(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addMedicine();
    }
}

function showStock() {
    const input = document.getElementById("medicineSearch");
    const value = input.value.trim();
    const stockDisplay = document.getElementById("stockDisplay");
    const options = document.getElementById("medicineList").options;
    
    // Reset stock display if input is empty
    if (!value) {
        stockDisplay.textContent = "Stock: -";
        return;
    }
    
    // Find the matching option
    for (let opt of options) {
        if (opt.value === value) {
            const stock = opt.getAttribute("data-stock");
            stockDisplay.textContent = `Stock: ${stock}`;
            return;
        }
    }
    
    // If no match found
    stockDisplay.textContent = "Stock: -";
}

// Global variable to track selected medicines with quantities
let selectedMedicines = [];

function addMedicine() {
    try {
        // Get input elements
        const input = document.getElementById("medicineSearch");
        const quantityInput = document.getElementById("medicineQuantity");
        
        // Validate inputs
        if (!input || !quantityInput) {
            console.error("Could not find input elements");
            return;
        }

        const value = input.value.trim();
        const quantity = parseInt(quantityInput.value) || 1;
        
        if (!value) {
            alert("Please select a medicine first");
            return;
        }

        // Get medicine options
        const options = document.getElementById("medicineList").options;
        if (!options || options.length === 0) {
            console.error("No medicine options found");
            return;
        }

        let medicineData = null;
        
        // Find the selected medicine in datalist
        for (let opt of options) {
            if (opt.value === value) {
                medicineData = {
                    id: opt.value.split(' - ')[0],
                    name: opt.value,
                    price: parseFloat(opt.getAttribute("data-price")) || 0,
                    stock: parseInt(opt.getAttribute("data-stock")) || 0
                };
                break;
            }
        }

        if (!medicineData) {
            alert("Medicine not found!");
            return;
        }

        // Check stock availability
        if (medicineData.stock < quantity) {
            alert(`Not enough stock! Only ${medicineData.stock} available.`);
            return;
        }

        // Check if medicine already exists in selected list
        const existingIndex = selectedMedicines.findIndex(m => m.id === medicineData.id);
        
        if (existingIndex >= 0) {
            // Update existing medicine quantity
            selectedMedicines[existingIndex].quantity += quantity;
        } else {
            // Add new medicine
            selectedMedicines.push({
                id: medicineData.id,
                name: medicineData.name,
                price: medicineData.price,
                quantity: quantity
            });
        }

        // Update UI
        updateSelectedMedicinesList();
        input.value = "";
        quantityInput.value = "1";
        
    } catch (error) {
        console.error("Error in addMedicine:", error);
        alert("An error occurred while adding medicine");
    }
}

function updateSelectedMedicinesList() {
    try {
        const list = document.getElementById("selectedMedicines");
        if (!list) {
            console.error("Could not find selected medicines list");
            return;
        }

        list.innerHTML = "";
        
        selectedMedicines.forEach((med, index) => {
            const li = document.createElement("li");
            li.style.marginBottom = "8px";
            li.style.padding = "8px";
            li.style.borderRadius = "4px";
            li.style.backgroundColor = "#f8f9fa"; // Added background color
            
            // Changed from input to span for non-editable quantity
            li.innerHTML = `
                ${med.name} - 
                Qty: <span style="display: inline-block; width: 50px; padding: 4px; font-weight: bold;">${med.quantity}</span>
                × ₱${med.price.toFixed(2)} = ₱${(med.price * med.quantity).toFixed(2)}
                <button type="button" onclick="removeMedicine(${index})" 
                        style="margin-left:10px; 
                               background:rgba(220, 53, 70, 0.39); 
                               color: white; 
                               border: none; 
                               border-radius: 4px; 
                               padding: 1px 4px;
                               font-size: 10px;
                               width: 50px;
                               height: 25px;
                               line-height: 20px;">×</button>
            `;
            list.appendChild(li);
        });
    } catch (error) {
        console.error("Error updating medicines list:", error);
    }
}

function removeMedicine(index) {
    if (index >= 0 && index < selectedMedicines.length) {
        selectedMedicines.splice(index, 1);
        updateSelectedMedicinesList();
    }
}

function calculateTotal() {
    // Update quantities from input fields
    const quantityInputs = document.querySelectorAll('.medicine-qty');
    quantityInputs.forEach((input, index) => {
        selectedMedicines[index].quantity = parseInt(input.value) || 1;
    });

    // Prepare data
    let total = 0;
    const medicineIds = [];
    const quantities = [];
    
    selectedMedicines.forEach(med => {
        total += med.price * med.quantity;
        medicineIds.push(med.id); // Just the numeric ID
        quantities.push(med.quantity);
    });

    // Update display
    document.getElementById("medicineTotalDisplay").value = "₱" + total.toFixed(2);
    document.getElementById("medicineTotal").value = total.toFixed(2);
    
    // Update hidden inputs as comma-separated strings
    document.getElementById("hidden_medicine_ids").value = medicineIds.join(',');
    document.getElementById("hidden_medicine_quantities").value = quantities.join(',');
}
</script>


<div class="content">
    <h2>Billing Management</h2>

    <form method="post" action="">
        <label>Patient Name:</label>
        <input list="patientList" name="patient_id" id="patient_id_input" placeholder="Select Patient" required>
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
        <div style="display: flex; align-items: center; gap: 10px;">
        <input list="medicineList" id="medicineSearch" placeholder="Type to search..." 
            oninput="showStock()" onchange="showStock()">
            <span id="stockDisplay" style="font-size: 14px;">Stock: -</span>
        </div>

        <label>Quantity:</label>
        <input type="number" id="medicineQuantity" min="1" value="1" style="width: 60px;">

        <button type="button" onclick="addMedicine()">Add</button>
        <ul id="selectedMedicines"></ul>

        <button type="button" onclick="calculateTotal()">Calculate Total</button> 
        <!-- Changed text from "Done" to "Calculate Total" for clarity -->


        <input type="hidden" name="medicine_ids" id="hidden_medicine_ids">
        <input type="hidden" name="medicine_quantities" id="hidden_medicine_quantities">


        <label>Medicine Total:</label>
        <input type="text" id="medicineTotalDisplay" readonly>
        <input type="hidden" name="medicine_total" id="medicineTotal" required>

        <datalist id="medicineList">
            <?php
            $medicines_result->data_seek(0); // Reset pointer to beginning
            while ($med = $medicines_result->fetch_assoc()) {
                echo "<option value='{$med['MedicineID']} - " . htmlspecialchars($med['MedicineName']) . "' 
                    data-price='{$med['Price']}' 
                    data-stock='{$med['StockQuantity']}'></option>";
            }
            ?>
        </datalist>

        <label>Payment Date:</label>
        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" readonly required>

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
                
                    <a class="btn btn-delete" href="?delete=<?= $row['BillingID'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
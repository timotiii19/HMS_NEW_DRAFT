<?php
session_start();
require '../vendor/autoload.php'; // ← This is all you need for PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../config/db.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();

        $resetLink = "http://localhost/HMS-main/auth/reset_password.php?token=$token";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'timothytalagtag019@gmail.com';       // Your Gmail
            $mail->Password = 'yuwg szpo sixu siih';         // App password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;


            $mail->setFrom('yourgmail@gmail.com', 'Hospital Management System');
            $mail->addAddress($email, $user['full_name']);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Link';
            $mail->Body = "
                <p>Hi <strong>{$user['full_name']}</strong>,</p>
                <p>Click the link below to reset your password. It expires in 1 hour:</p>
                <p><a href='$resetLink'>$resetLink</a></p>
                <br>
                <p>If you didn’t request this, please ignore it.</p>
            ";

            $mail->send();
            $message = "Password reset link sent to your email.";
        } catch (Exception $e) {
            $message = "Email could not be sent. Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        $message = "No user found with that email and role.";
    }
}
?>


<!-- HTML Form -->
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        form {
            width: 400px;
            padding: 30px;
            background: #f7f7f7;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 12px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            background: #c94141;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .message {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Forgot Password</h2>
        <input type="email" name="email" placeholder="Enter your email" required>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="Admin">Admin</option>
            <option value="Doctor">Doctor</option>
            <option value="Nurse">Nurse</option>
            <option value="Laboratory">Laboratory</option>
            <option value="Pharmacist">Pharmacist</option>
        </select>
        <button type="submit">Send Reset Link</button>
        <div class="message"><?php echo $message; ?></div>
    </form>
</body>
</html>

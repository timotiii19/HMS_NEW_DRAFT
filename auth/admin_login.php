<?php
session_start();
include('../config/db.php');
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Make sure to use prepared statements to avoid SQL injection (recommended)
    $sql = "SELECT * FROM users WHERE email = ? AND role = 'Admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Verify password (hashed or plain for legacy)
        $passwordMatch = password_verify($password, $user['password']) || $password === $user['password'];

        if ($passwordMatch) {
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['role'] = $user['role'];      // Use 'user_role' here
            $_SESSION['full_name'] = $user['full_name']; // Make sure users table has this field

            // If you want username too:
            $_SESSION['username'] = $user['username'];

            $roleQuery = "SELECT AdminID AS role_id FROM admin WHERE UserID = {$user['UserID']}";
            $roleResult = $conn->query($roleQuery);
            if ($roleResult && $roleResult->num_rows == 1) {
                $_SESSION['role_id'] = $roleResult->fetch_assoc()['role_id'];
            }

            header("Location: ../views/admin/dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials!";
        }
    } else {
        $error = "No Admin found with those credentials!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
   <style>
        body {
            position: relative;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden; /* avoid scrollbars if needed */
        }

        body::before {
            content: "";
            position: fixed; /* fixed so it stays while scrolling */
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('../images/admin_bg3.png') no-repeat center center;
            background-size: cover;
            filter: blur(0px);  /* adjust blur amount here */
            z-index: -1;  /* behind everything */
        }

        .login-box {
            position: relative; /* make sure it stacks above the pseudo bg */
            width: 450px;
            height: 450px;
            margin: 45px auto 0 160px; /* top auto bottom left */
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
            z-index: 1;
        }
        h2 {
            font-size: 30px;
            margin-bottom: 20px;
        }
        input {
            width: 90%;
            padding: 10px;
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            width: 95%;
            padding: 10px;
            background-color:rgb(201, 65, 65);
            color: white;
            border: none;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background-color:rgb(206, 107, 118);
        }
        .error {
            color: red;
            font-size: 12px;
            margin: 10px 0;
        }
        .top-img {
            width: 100px;
        }
         .back-btn {
            display: inline-block;
            margin-top: 15px;
            margin-left: 10px;
            text-decoration: none;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 8px;
            background-color: #fff;
            color: rgb(201, 65, 65);
            border: 1px solid rgb(201, 65, 65);
            transition: background-color 0.2s, color 0.2s;
        }

        .back-btn:hover {
            background-color: rgb(201, 65, 65);
            color: #fff;
        }
    </style>
</head>
<body>
    <a href="role_selection.php" class="back-btn">← Back to Role Selection</a>
    <div class="login-box">
        <img src="../images/admin1.png" class="top-img" alt="Admin">
        <h2>Good day, Admin!<br><span style="font-size: 18px;">Welcome!</span></h2>
        <form method="POST">
            <input type="text" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <div class="error"><?php echo $error; ?></div>
            <button type="submit">LOG IN</button>
        </form>
    </div>
</body>
</html>

<?php
//Copilot suggested code for reset-password.php
session_start();
include('database.php');
$error_message = "";
$success_message = "";

if (!isset($_GET['token'])) {
    header("Location: login.php");
    exit();
}

$token = $_GET['token'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password === $confirm_password) {
        if (strlen($new_password) >= 8) {
            // Verify token and check expiry
            $stmt = $conn->prepare("SELECT email FROM password_reset_tokens WHERE token = ? AND expiry > NOW() AND used = 0");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $email = $row['email'];
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $update_stmt->bind_param("ss", $hashed_password, $email);
                
                if ($update_stmt->execute()) {
                    // Mark token as used
                    $conn->query("UPDATE password_reset_tokens SET used = 1 WHERE token = '$token'");
                    $success_message = "Password updated successfully. You can now <a href='login.php'>login</a>.";
                } else {
                    $error_message = "Error updating password.";
                }
            } else {
                $error_message = "Invalid or expired reset link.";
            }
        } else {
            $error_message = "Password must be at least 8 characters long.";
        }
    } else {
        $error_message = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Copy the same CSS styles from login.php */
        /* ... existing styles ... */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reset Password</h1>
            <p>Enter your new password</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-message" style="color: red; text-align: center; margin-bottom: 15px;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message" style="color: green; text-align: center; margin-bottom: 15px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter new password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password">
            </div>
            <button type="submit" class="login-btn">Reset Password</button>
        </form>
    </div>
</body>
</html>
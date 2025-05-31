<?php
//Below is the copilot suggested code for forgot password.php
session_start();
include('database.php');
$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $conn->prepare("INSERT INTO password_reset_tokens (email, token, expiry) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expiry);
            
            if ($stmt->execute()) {
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/check/reset-password.php?token=" . $token;
                $to = $email;
                $subject = "Password Reset Request";
                $message = "Click the following link to reset your password: " . $reset_link;
                $headers = "From: noreply@yourwebsite.com";
                
                if (mail($to, $subject, $message, $headers)) {
                    $success_message = "Password reset instructions have been sent to your email.";
                } else {
                    $error_message = "Error sending email. Please try again.";
                }
            } else {
                $error_message = "An error occurred. Please try again.";
            }
        } else {
            $error_message = "No account found with this email address.";
        }
    } else {
        $error_message = "Invalid email format.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Copy the same CSS styles from login.php and modify the container width */
        /* ... existing styles ... */
        .container {
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Forgot Password</h1>
            <p>Enter your email to reset password</p>
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

        <form action="forgot-password.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            <button type="submit" class="login-btn">Send Reset Link</button>
        </form>
        <div class="signup-link">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
<?php
include 'database.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    // Validate email format using PHP's built-in function
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
    }
    // Validate password strength using regex
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $message = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    }
    else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $fullname, $email, $hashedPassword);

        if ($stmt->execute()) {
            $message = "Signup successful. <a href='login.php'>Login here</a>.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        /* (same CSS from your original form) */
        /* ... full CSS remains unchanged ... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #4cb050 0%, #333 100%);
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 400px;
            margin: 1rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4cb050;
        }

        .terms {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .terms input[type="checkbox"] {
            width: auto;
        }

        .terms label {
            font-size: 0.9rem;
            color: #666;
        }

        .terms a {
            color: #667eea;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        .signup-btn {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #4cb050 0%, #333 100%);
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .signup-btn:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 1.75rem;
            }
        }    
        .message { margin-top: 1rem; text-align: center; color: red; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Create Account</h1>
        <p>Please fill in the details to sign up</p>
    </div>
    <form method="POST" action="">
        <div class="form-group">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" required placeholder="Enter your full name">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Create a password" required minlength="6">
        </div>
        <div class="form-group">
            <label for="confirm-password">Confirm Password</label>
            <input type="password" id="confirm-password" name="confirm-password" required placeholder="Confirm your password">
        </div>
        <div class="form-group">
            <input type="checkbox" id="terms" name="terms" required>
            <label for="terms">I agree to the <a href="#">Terms</a> & <a href="#">Privacy Policy</a></label>
        </div>
        <button type="submit" class="signup-btn">Sign Up</button>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="login-link">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>
</body>
</html>
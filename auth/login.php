<?php
// Start the session
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
$error_message = "";
// Include the database connection file
include('database.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Regex validation for email
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}$/", $email)) {
        $error_message = "Invalid email format!";
        error_log("Invalid email format: $email");
    }

    // Regex validation for password (minimum 8 characters, at least one letter and one number)
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $error_message = "Password must be at least 8 characters long and include at least one letter and one number.";
        error_log("Weak or invalid password entered.");
    }
    $remember = isset($_POST['remember']) ? true : false;

    error_log("Login attempt - Email: " . $email);

    // Validate email and password (simple validation)
    if (!empty($email) && !empty($password)) {
        // Query to check if the user exists in the database
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            die("Database error occurred. Please try again later.");
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        error_log("Query executed - Found rows: " . $result->num_rows);

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            error_log("User found - User ID: " . $user['user_id']);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                error_log("Password verified successfully");
                
                // Store user information in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];

                error_log("Session data after login: " . print_r($_SESSION, true));

                // Remember me functionality (optional)
                if ($remember) {
                    setcookie('user_id', $user['user_id'], time() + (86400 * 30), "/"); // 30 days
                    setcookie('email', $user['email'], time() + (86400 * 30), "/");
                }

                // Redirect to the dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                error_log("Password verification failed");
                $error_message = "Invalid email or password!";
            }
        } else {
            error_log("No user found with email: " . $email);
            $error_message = "No user found with this email!";
        }
    } else {
        error_log("Empty email or password submitted");
        $error_message = "Please fill in both fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* The previous CSS remains the same */
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

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .login-btn {
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

        .login-btn:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link a:hover {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome Back</h1>
            <p>Please login to your account</p>
        </div>
        
        <!-- Display error message if any -->
        <?php if (isset($error_message)): ?>
            <div class="error-message" style="color: red; text-align: center;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="#" class="forgot-password">Forgot Password?</a>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign up</a>
        </div>
    </div>  
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // e.preventDefault();
            // Add any client-side validation here if needed
            console.log('Login form submitted');
        });
    </script>
</body>
</html>

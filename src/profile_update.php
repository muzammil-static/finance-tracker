<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_password = $_POST['password'];
    $update_query = "UPDATE users SET fullname=?, email=?" . (!empty($new_password) ? ", password=?" : "") . " WHERE user_id=?";
    if (!empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $new_username, $new_email, $hashed, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=? WHERE user_id=?");
        $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
    }
    $stmt->execute();
    $_SESSION['username'] = $new_username;
    $_SESSION['email'] = $new_email;
    header("Location: settings.php?profile_updated=1");
    exit();
}

// Fetch current user info
$stmt = $conn->prepare("SELECT fullname, email FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
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
        .container{
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 400px;
            margin: 1rem;
        }
        .form-group{
            margin-bottom: 1.5rem;
        }

        .form-group label{
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input{
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
        button{
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
        button:hover{
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
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
            <h1>Update Profile</h1>
            <p>What you want to update?</p>
        </div>
        <form method="POST" action="profile_update.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="password">
            </div>
            <button type="submit">Update</button>
        </form>
    </div>
</body>
</html>
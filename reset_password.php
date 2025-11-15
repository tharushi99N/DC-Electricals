<?php
session_start();
include("connection.php");

$message = "";
$message_type = "";
$valid_token = false;
$user_data = null;

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header("Location: forgot_password.php?error=invalid_token");
    exit();
}

$token = $_GET['token'];

// Verify token
$stmt = $conn->prepare("
    SELECT rt.user_id, rt.expires_at, u.name, u.email 
    FROM PASSWORD_RESET_TOKENS rt 
    JOIN USER u ON rt.user_id = u.userID 
    WHERE rt.token = ? AND rt.expires_at > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $valid_token = true;
    $user_data = $result->fetch_assoc();
} else {
    $message = "Invalid or expired reset token. Please request a new password reset.";
    $message_type = "error";
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password
        $update_stmt = $conn->prepare("UPDATE USER SET password = ? WHERE userID = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_data['user_id']);
        
        if ($update_stmt->execute()) {
            // Delete the used token
            $delete_token_stmt = $conn->prepare("DELETE FROM PASSWORD_RESET_TOKENS WHERE token = ?");
            $delete_token_stmt->bind_param("s", $token);
            $delete_token_stmt->execute();
            
            $message = "Password has been successfully reset! You can now login with your new password.";
            $message_type = "success";
            
            // Redirect to login after 3 seconds
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'login.php?message=password_reset_success';
                }, 3000);
            </script>";
        } else {
            $message = "Failed to update password. Please try again.";
            $message_type = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>DC Electricals – Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg-a:#6d92f0;
            --bg-b:#000000;
            --accent:#ffffff;
            --card-bg: rgba(255,255,255,0.06);
            --glass-border: rgba(255,255,255,0.06);
            --text: #eaf6ff;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            font-family:'Poppins',system-ui,Arial;
            background: url("bg.jpg") no-repeat center center fixed;
            background-size: cover;
            color:var(--text);
            padding:20px;
        }
        
        .container{ width:100%; max-width:420px; }
        .card{
            background:var(--card-bg);
            border-radius:14px;
            padding:28px;
            box-shadow: 0 12px 40px rgba(2,6,23,0.6);
            backdrop-filter: blur(8px);
            border: 1px solid var(--glass-border);
        }

        .brand{
            display:flex;gap:12px;align-items:center;margin-bottom:14px;
        }
        .mark{
            width:52px;
            height:52px;
            border-radius:12px;
            overflow:hidden;
            display:flex;
            align-items:center;
            justify-content:center;
            background:none;
            box-shadow:0 6px 16px rgba(0,0,0,0.3);
        }
        .mark img{
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .brand h1{font-size:18px;margin:0}
        .brand p{margin:0;font-size:12px;color:rgba(255,255,255,0.75)}

        h2{margin:8px 0 18px 0;font-size:16px;font-weight:600;color:#ffffff}

        .field{ position:relative;margin-bottom:14px; }
        .field input{
            width:100%;
            padding:14px 12px;
            background:transparent;
            border:1px solid rgba(255,255,255,0.08);
            border-radius:10px;
            color:var(--text);
            font-size:14px;
            outline:none;
            transition:border-color .15s, box-shadow .15s;
        }
        .field label{
            position:absolute;
            left:12px;
            top:50%;
            transform:translateY(-50%);
            transition: all .15s ease;
            pointer-events:none;
            color:rgba(255,255,255,0.6);
            font-size:14px;
            background:transparent;
            padding:0 6px;
        }
        .field input:focus{ 
            border-color:rgba(255, 255, 255, 0.3); 
            box-shadow: 0 6px 18px rgba(255,183,3,0.06);
        }
        .field input:focus + label,
        .field input:not(:placeholder-shown) + label{
            top:-8px; font-size:12px; color:var(--accent);
        }

        .toggle{
            position:absolute; right:10px; top:50%; transform:translateY(-50%);
            background:none;border:none;color:rgba(255,255,255,0.8); cursor:pointer; padding:6px; font-size:13px;
        }

        .btn{
            width:100%;
            background:linear-gradient(90deg,var(--accent),#ffffff);
            border:none;padding:12px;border-radius:10px;color:#07202c;font-weight:700;cursor:pointer;
            box-shadow:0 10px 24px rgba(255,107,107,0.12);
            margin-top:6px;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .foot{font-size:13px;margin-top:12px;text-align:center;color:rgb(255, 255, 255)}
        .foot a{color:inherit;text-decoration:underline}

        /* Message styles */
        .message{
            padding:12px;
            border-radius:8px;
            margin-bottom:16px;
            text-align:center;
        }
        .message.success{
            background:rgba(46, 204, 113, 0.2);
            border:1px solid rgba(46, 204, 113, 0.3);
            color:#2ecc71;
        }
        .message.error{
            background:rgba(231, 76, 60, 0.2);
            border:1px solid rgba(231, 76, 60, 0.3);
            color:#e74c3c;
        }
        
        .user-info {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }

        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #2ecc71; }

        @media(max-width:420px){
            .card{ padding:20px }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="brand">
                <div class="mark">
                    <img src="logo.jpg" alt="DC Electricals Logo">
                </div>
                <div>
                    <h1>DC Electricals</h1>
                    <p>Products • Installations • Repairs</p>
                </div>
            </div>

            <h2>Set New Password</h2>

            <?php if ($valid_token): ?>
                <div class="user-info">
                    Setting new password for: <strong><?php echo htmlspecialchars($user_data['email']); ?></strong>
                </div>
            <?php endif; ?>

            <?php if(!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token && $message_type !== 'success'): ?>
                <form method="post" novalidate>
                    <div class="field">
                        <input id="password" name="password" type="password" placeholder=" " required />
                        <label for="password">New Password</label>
                        <button type="button" class="toggle" onclick="togglePw('password', this)">Show</button>
                    </div>
                    <div class="password-strength" id="strength-indicator"></div>

                    <div class="field">
                        <input id="confirm_password" name="confirm_password" type="password" placeholder=" " required />
                        <label for="confirm_password">Confirm New Password</label>
                        <button type="button" class="toggle" onclick="togglePw('confirm_password', this)">Show</button>
                    </div>

                    <button class="btn" type="submit" id="submit-btn">Update Password</button>
                    
                    <div class="foot">
                        Remember your password? <a href="login.php">Back to Login</a>
                    </div>
                </form>
            <?php elseif (!$valid_token): ?>
                <div class="foot">
                    <a href="forgot_password.php">Request New Password Reset</a> | 
                    <a href="login.php">Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePw(fieldId, btn) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                btn.textContent = 'Hide';
            } else {
                field.type = 'password';
                btn.textContent = 'Show';
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength += 1;
            else feedback.push("at least 8 characters");

            if (/[a-z]/.test(password)) strength += 1;
            else feedback.push("lowercase letter");

            if (/[A-Z]/.test(password)) strength += 1;
            else feedback.push("uppercase letter");

            if (/[0-9]/.test(password)) strength += 1;
            else feedback.push("number");

            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            else feedback.push("special character");

            return { strength, feedback };
        }

        // Real-time password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const indicator = document.getElementById('strength-indicator');
            const submitBtn = document.getElementById('submit-btn');

            if (password.length === 0) {
                indicator.innerHTML = '';
                return;
            }

            const result = checkPasswordStrength(password);
            let strengthText = '';
            let strengthClass = '';

            if (result.strength <= 2) {
                strengthText = 'Weak';
                strengthClass = 'strength-weak';
                submitBtn.disabled = false; // Allow weak passwords but warn user
            } else if (result.strength <= 4) {
                strengthText = 'Medium';
                strengthClass = 'strength-medium';
                submitBtn.disabled = false;
            } else {
                strengthText = 'Strong';
                strengthClass = 'strength-strong';
                submitBtn.disabled = false;
            }

            indicator.innerHTML = `<span class="${strengthClass}">Password strength: ${strengthText}</span>`;
            
            if (result.feedback.length > 0 && result.strength <= 2) {
                indicator.innerHTML += `<br><small>Consider adding: ${result.feedback.join(', ')}</small>`;
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
        });

        // Auto-hide success message and redirect
        <?php if ($message_type === 'success'): ?>
        let countdown = 3;
        const message = document.querySelector('.message.success');
        const interval = setInterval(function() {
            countdown--;
            if (countdown > 0) {
                message.innerHTML += `<br><small>Redirecting to login in ${countdown} seconds...</small>`;
            } else {
                clearInterval(interval);
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
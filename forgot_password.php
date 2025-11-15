<?php
session_start();
include("connection.php");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT userID, name, email FROM USER WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Generate unique reset token
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
            
            // Store token in database (you'll need to create this table)
            $token_stmt = $conn->prepare("
                INSERT INTO PASSWORD_RESET_TOKENS (user_id, token, expires_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                expires_at = VALUES(expires_at), 
                created_at = NOW()
            ");
            $token_stmt->bind_param("iss", $user['userID'], $reset_token, $token_expiry);
            
            if ($token_stmt->execute()) {
                // Create reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . $reset_token;
                
                // Email content
                $subject = "Password Reset Request - DC Electricals";
                $email_body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #1e1e2f, #4a90e2); color: white; padding: 20px; text-align: center; }
                        .content { background: #f9f9f9; padding: 30px; }
                        .button { display: inline-block; background: #4a90e2; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>DC Electricals</h2>
                            <p>Password Reset Request</p>
                        </div>
                        <div class='content'>
                            <h3>Hello " . htmlspecialchars($user['name']) . ",</h3>
                            <p>You have requested to reset your password for your DC Electricals account.</p>
                            <p>Click the button below to reset your password:</p>
                            <p><a href='" . $reset_link . "' class='button'>Reset Password</a></p>
                            <p>Or copy and paste this link in your browser:</p>
                            <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 4px;'>" . $reset_link . "</p>
                            <p><strong>This link will expire in 1 hour.</strong></p>
                            <p>If you did not request this password reset, please ignore this email.</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; 2024 DC Electricals. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Email headers
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: noreply@dcelectricals.com" . "\r\n";
                $headers .= "Reply-To: support@dcelectricals.com" . "\r\n";
                
                // Send email
                if (mail($email, $subject, $email_body, $headers)) {
                    $message = "Password reset instructions have been sent to your email address.";
                    $message_type = "success";
                } else {
                    // For development - show the reset link directly if email fails
                    $message = "Email sending failed. For testing: <a href='" . $reset_link . "' target='_blank'>Reset Password Link</a>";
                    $message_type = "error";
                }
            } else {
                $message = "Something went wrong. Please try again.";
                $message_type = "error";
            }
        } else {
            // Don't reveal if email exists or not for security
            $message = "If your email is registered, you will receive password reset instructions.";
            $message_type = "success";
        }
    } else {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>DC Electricals – Forgot Password</title>
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

        .btn{
            width:100%;
            background:linear-gradient(90deg,var(--accent),#ffffff);
            border:none;padding:12px;border-radius:10px;color:#07202c;font-weight:700;cursor:pointer;
            box-shadow:0 10px 24px rgba(255,107,107,0.12);
            margin-top:6px;
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
        
        .description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.5;
        }

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

            <h2>Reset Your Password</h2>
            <p class="description">
                Enter your email address and we'll send you instructions to reset your password.
            </p>

            <?php if(!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="field">
                    <input id="email" name="email" type="email" placeholder=" " required 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />
                    <label for="email">Email Address</label>
                </div>

                <button class="btn" type="submit">Send Reset Instructions</button>
                
                <div class="foot">
                    Remember your password? <a href="login.php">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e){
            const email = document.getElementById('email').value.trim();
            
            if(!email || !email.includes('@')){
                e.preventDefault();
                alert('Please enter a valid email address.');
                document.getElementById('email').focus();
            }
        });
    </script>
</body>
</html>
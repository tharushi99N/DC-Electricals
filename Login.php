<?php
session_start();
include("connection.php");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (!empty($username) && !empty($password)) {
        // Check user credentials
        $stmt = $conn->prepare("SELECT userID, name, email, password, role FROM USER WHERE email = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Update last login
                $update_login = $conn->prepare("UPDATE USER SET lastLogin = NOW() WHERE userID = ?");
                $update_login->bind_param("i", $user['userID']);
                $update_login->execute();
                
                // Set session variables
                $_SESSION['user_id'] = $user['userID'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Set remember me cookie if checked
                if ($remember) {
                    $cookie_value = base64_encode($user['userID'] . '|' . $user['email']);
                    setcookie('remember_user', $cookie_value, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                }
                
                // Redirect based on role
                switch($user['role']) {
                    case 'Admin':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'Customer':
                        header("Location: customer/dashboard.php");
                        break;
                    case 'Technician':
                        header("Location: technician/dashboard.php");
                        break;
                    case 'Storekeeper':
                        header("Location: storekeeper/dashboard.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit();
            } else {
                $message = "Invalid password!";
                $message_type = "error";
            }
        } else {
            $message = "User not found!";
            $message_type = "error";
        }
    } else {
        $message = "Please fill in all fields!";
        $message_type = "error";
    }
}

// Check for remember me cookie
if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_user'])) {
    $cookie_data = base64_decode($_COOKIE['remember_user']);
    list($user_id, $email) = explode('|', $cookie_data);
    
    $stmt = $conn->prepare("SELECT userID, name, email, role FROM USER WHERE userID = ? AND email = ?");
    $stmt->bind_param("is", $user_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['userID'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Redirect based on role
        switch($user['role']) {
            case 'Admin':
                header("Location: admin/dashboard.php");
                break;
            case 'Customer':
                header("Location: customer/dashboard.php");
                break;
            case 'Technician':
                header("Location: technician/dashboard.php");
                break;
            case 'Storekeeper':
                header("Location: storekeeper/dashboard.php");
                break;
        }
        exit();
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>DC Electricals – Login</title>
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
    .field input, .field select{
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
    .field input:focus, .field select:focus{ 
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

    .row{display:flex;gap:12px;align-items:center;margin-bottom:8px}
    .actions{display:flex;justify-content:space-between;align-items:center;margin-top:6px}
    label.rem{font-size:13px;color:rgba(255, 255, 255, 0.85)}
    .btn{
      background:linear-gradient(90deg,var(--accent),#ffffff);
      border:none;padding:10px 18px;border-radius:10px;color:#07202c;font-weight:700;cursor:pointer;
      box-shadow:0 10px 24px rgba(255,107,107,0.12);
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

    @media(max-width:420px){
      .card{ padding:20px }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card" role="region" aria-label="DC Electricals login">
      <div class="brand">
        <div class="mark">
          <img src="logo.jpg" alt="DC Electricals Logo">
        </div>
        <div>
          <h1>DC Electricals</h1>
          <p>Products • Installations • Repairs</p>
        </div>
      </div>

      <h2>Login to your account</h2>

      <?php if(!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <form id="loginForm" method="post" novalidate>
        <div class="field">
          <input id="username" name="username" type="email" placeholder=" " value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required />
          <label for="username">Email</label>
        </div>

        <div class="field" style="margin-bottom:6px;position:relative;">
          <input id="password" name="password" type="password" placeholder=" " required />
          <label for="password">Password</label>
          <button type="button" class="toggle" aria-pressed="false" onclick="togglePw()">Show</button>
        </div>

        <div class="actions">
          <label class="rem">
            <input type="checkbox" name="remember" style="margin-right:6px">Remember me
          </label>
          <button class="btn" type="submit">Login</button>
        </div>
        <div class="foot">
          <a href="forgot_password.php">Forgot password?</a> | 
          <a href="register.php">Create account</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePw(){
      const p = document.getElementById('password');
      const btn = document.querySelector('.toggle');
      if(!p) return;
      if(p.type === 'password'){ 
        p.type = 'text'; 
        btn.textContent = 'Hide'; 
        btn.setAttribute('aria-pressed','true'); 
      } else { 
        p.type = 'password'; 
        btn.textContent = 'Show'; 
        btn.setAttribute('aria-pressed','false'); 
      }
    }

    document.getElementById('loginForm').addEventListener('submit', function(e){
      const u = document.getElementById('username').value.trim();
      const p = document.getElementById('password').value;
      
      if(u.length < 3 || p.length < 1){
        e.preventDefault();
        alert('Please enter valid email and password.');
        if(u.length < 3) document.getElementById('username').focus();
        else document.getElementById('password').focus();
      }
    });
  </script>
</body>
</html>
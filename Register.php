<?php
session_start();
include("connection.php");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $role = $_POST['role'];
    
    // Validation
    $errors = [];
    
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($role)) {
        $errors[] = "Role selection is required";
    }
    
    if (empty($errors)) {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT email FROM USER WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $message = "Email already exists!";
            $message_type = "error";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into USER table
            $stmt = $conn->prepare("INSERT INTO USER (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Insert into respective role table
                $role_inserted = false;
                
                switch($role) {
                    case 'Customer':
                        $role_stmt = $conn->prepare("INSERT INTO CUSTOMER (UserID, Name) VALUES (?, ?)");
                        $role_stmt->bind_param("is", $user_id, $fullname);
                        $role_inserted = $role_stmt->execute();
                        break;
                        
                    case 'Admin':
                        $role_stmt = $conn->prepare("INSERT INTO ADMIN (UserID, Name) VALUES (?, ?)");
                        $role_stmt->bind_param("is", $user_id, $fullname);
                        $role_inserted = $role_stmt->execute();
                        break;
                        
                    case 'Technician':
                        $role_stmt = $conn->prepare("INSERT INTO TECHNICIAN (UserID, Name) VALUES (?, ?)");
                        $role_stmt->bind_param("is", $user_id, $fullname);
                        $role_inserted = $role_stmt->execute();
                        break;
                        
                    case 'Storekeeper':
                        $role_stmt = $conn->prepare("INSERT INTO STOREKEEPER (UserID, Name) VALUES (?, ?)");
                        $role_stmt->bind_param("is", $user_id, $fullname);
                        $role_inserted = $role_stmt->execute();
                        break;
                }
                
                if ($role_inserted) {
                    $message = "Registration successful! You can now login.";
                    $message_type = "success";
                    // Clear form data
                    $fullname = $email = $role = "";
                } else {
                    $message = "Registration failed. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Registration failed. Please try again.";
                $message_type = "error";
            }
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
  <title>DC Electricals – Register</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    :root{
      --accent:#ffffff;
      --text:#fff;
    }
    body{
      margin:0;
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      font-family:'Poppins',sans-serif;
      background:url("bg.jpg") no-repeat center center fixed;
      background-size:cover;
      color:var(--text);
      padding:20px;
    }
    body::before{
      content:"";
      position:fixed;inset:0;
      background:rgba(0,0,0,0.55);
      z-index:-1;
    }
    .container{ width:100%; max-width:450px; }
    .card{
      background:rgba(255,255,255,0.06);
      border-radius:14px;
      padding:28px;
      backdrop-filter:blur(8px);
      box-shadow:0 12px 40px rgba(0,0,0,0.5);
      border:1px solid rgba(255,255,255,0.08);
    }
    .brand{ text-align:center; margin-bottom:16px; }
    .brand img{ width:70px; border-radius:12px; box-shadow:0 6px 16px rgba(0,0,0,0.3); }
    h2{margin:10px 0 18px;text-align:center;font-weight:600}
    .field{ position:relative;margin-bottom:14px; }
    .field input,.field select{
      width:100%;padding:14px 12px;
      background:transparent;border:1px solid rgba(255,255,255,0.15);
      border-radius:10px;color:var(--text);font-size:14px;
      outline:none;
    }
    .field label{
      position:absolute;left:12px;top:50%;transform:translateY(-50%);
      color:rgb(255, 255, 255);transition:.15s;font-size:14px;
      pointer-events:none;
    }
    .field input:focus+label,
    .field input:not(:placeholder-shown)+label,
    .field select:focus+label,
    .field select:not([value=""])+label{
      top:-8px;font-size:12px;color:var(--accent);
    }
    .toggle{
      position:absolute;right:10px;top:50%;transform:translateY(-50%);
      background:none;border:none;color:rgba(255,255,255,0.8);cursor:pointer;
    }
    .btn{
      width:100%;background:linear-gradient(90deg,var(--accent),#ffffff);
      border:none;padding:12px;border-radius:10px;color:#07202c;font-weight:700;cursor:pointer;
      margin-top:6px;
    }
    .foot{font-size:13px;text-align:center;margin-top:12px}
    .foot a{color:#ffffff;text-decoration:underline}
    
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
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="brand">
        <img src="logo.jpg" alt="DC Electricals Logo">
      </div>
      <h2>Create an Account</h2>
      
      <?php if(!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <form id="regForm" method="post" novalidate>
        <div class="field">
          <input type="text" id="fullname" name="fullname" placeholder=" " value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>" required>
          <label for="fullname">Full Name</label>
        </div>
        <div class="field">
          <input type="email" id="email" name="email" placeholder=" " value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
          <label for="email">Email</label>
        </div>
        <div class="field">
          <input type="contact" id="contact" name="contact" placeholder=" " value="<?php echo isset($contact) ? htmlspecialchars($contact) : ''; ?>" required>
          <label for="contact">Contact Number</label>
        </div>
        <div class="field">
          <input type="text" id="address" name="address" placeholder=" " value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>" required>
          <label for="address">Address</label>
        </div>
        <div class="field">
          <input type="password" id="password" name="password" placeholder=" " required>
          <label for="password">Password</label>
          <button type="button" class="toggle" onclick="togglePw('password',this)">Show</button>
        </div>
        <div class="field">
          <input type="password" id="confirm" name="confirm" placeholder=" " required>
          <label for="confirm">Confirm Password</label>
          <button type="button" class="toggle" onclick="togglePw('confirm',this)">Show</button>
        </div>
        <div class="field">
          <select name="role" required>
            <option value="">Select role</option>
            <option value="Admin" <?php echo (isset($role) && $role == 'Admin') ? 'selected' : ''; ?>>Admin</option>
            <option value="Customer" <?php echo (isset($role) && $role == 'Customer') ? 'selected' : ''; ?>>Customer</option>
            <option value="Storekeeper" <?php echo (isset($role) && $role == 'Storekeeper') ? 'selected' : ''; ?>>Storekeeper</option>
            <option value="Technician" <?php echo (isset($role) && $role == 'Technician') ? 'selected' : ''; ?>>Technician</option>
          </select>
        </div>
        <button type="submit" class="btn">Register</button>
        <div class="foot">
          Already have an account? <a href="login.php">Sign in</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePw(id,btn){
      const p=document.getElementById(id);
      if(p.type==="password"){ p.type="text"; btn.textContent="Hide"; }
      else { p.type="password"; btn.textContent="Show"; }
    }
    
    // Client-side validation
    document.getElementById('regForm').addEventListener('submit',function(e){
      const pw=document.getElementById('password').value;
      const cf=document.getElementById('confirm').value;
      const role=document.querySelector('select[name="role"]').value;
      
      if(pw.length < 6){
        e.preventDefault();
        alert("Password must be at least 6 characters long!");
        return;
      }
      
      if(pw!==cf){
        e.preventDefault();
        alert("Passwords do not match!");
        return;
      }
      
      if(!role){
        e.preventDefault();
        alert("Please select a role!");
        return;
      }
    });
  </script>
</body>
</html>
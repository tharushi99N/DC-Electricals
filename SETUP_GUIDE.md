# DC Electricals Management System - Setup Guide

## Project Overview
This is a comprehensive PHP/MySQL web application for managing an electrical company with the following features:
- User authentication & authorization
- Role-based access (Admin, Customer, Technician, Storekeeper)
- Admin dashboard with analytics
- Product & service management
- Service booking system
- Invoice & feedback management

## Requirements
- ✅ PHP 8.2.12 (Already installed via XAMPP)
- MySQL Server (Need to verify/start)
- Local web server (XAMPP Apache)
- Browser

## Database Setup

### 1. Start MySQL Server
```powershell
# If using XAMPP, start MySQL from XAMPP Control Panel
# OR run from command line:
"C:\xampp\mysql\bin\mysqld.exe"
```

### 2. Create Database & Tables

Open MySQL CLI or MySQL Workbench and run:
```sql
-- Create database
CREATE DATABASE DCElectricals;
USE DCElectricals;

-- Then run all the CREATE TABLE statements from README.md
```

**Alternative:** Import the SQL schema from `README.md` or `MySQL Local.session.sql`

### 3. Database Connection
Database credentials (already configured in `connection.php`):
- Host: localhost
- Username: root
- Password: 1234
- Database: DCElectricals

## Running the Project

### Option 1: Using PHP Built-in Server (Quickest)
```powershell
cd c:\Users\tharu\Desktop\HNDIS\Final_Project\DC_Elec
php -S localhost:8000
```
Then open: http://localhost:8000

### Option 2: Using XAMPP Apache
1. Copy project to `C:\xampp\htdocs\DC_Elec`
2. Start Apache from XAMPP Control Panel
3. Access: http://localhost/DC_Elec

### Option 3: Using Visual Studio Code
1. Install PHP Server extension
2. Right-click on `index.php` → "PHP Server: Open in browser"

## Project Structure
```
DC_Elec/
├── index.php              # Landing page
├── Login.php              # User login
├── Register.php           # User registration
├── forgot_password.php    # Password recovery
├── reset_password.php     # Password reset
├── logout.php             # Session logout
├── connection.php         # Database connection
├── auth_check.php         # Authentication helper
├── admin/
│   └── dashboard.php      # Admin dashboard
├── .vscode/
│   └── settings.json      # VS Code MySQL settings
└── README.md              # Database schema
```

## Test Accounts
After creating the database and sample data:
- Admin: admin@example.com / password123
- Customer: customer@example.com / password123
- Technician: tech@example.com / password123
- Storekeeper: store@example.com / password123

## Important Notes
⚠️ **Before running:**
1. Ensure MySQL is running
2. Database `DCElectricals` must exist
3. All tables must be created (run the SQL from README.md)
4. Files `logo.jpg` and `bg.jpg` may need to be added to the project root

## Troubleshooting

### "Connection failed: Unknown database"
- Create the database: `CREATE DATABASE DCElectricals;`
- Import the schema from README.md

### "Connection failed: Access denied for user 'root'"
- Check `connection.php` credentials
- Verify MySQL password is `1234`

### "Headers already sent" error
- Check for spaces/newlines before `<?php` tags

### Password reset emails not sending
- Email functionality requires mail server
- For testing, the reset link is displayed as fallback

## Next Steps
1. ✅ Create the database
2. ✅ Start MySQL & Apache servers
3. ✅ Run the project on `localhost:8000`
4. ✅ Login with test credentials
5. ✅ Explore admin dashboard

---
**Project Type:** PHP + MySQL Web Application  
**Status:** Development Ready

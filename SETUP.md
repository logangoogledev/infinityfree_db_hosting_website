# Quick Setup Guide

## Before You Begin

Make sure you have:
- An InfinityFree account (https://infinityfree.com)
- An FTP client like FileZilla (https://filezilla-project.org/)
- Access to phpMyAdmin in your control panel

## Step-by-Step Setup

### 1. Database Setup (5 minutes)

1. Log into your InfinityFree control panel
2. Click "MySQL Databases"
3. Create a new database - note the credentials:
   - Database name
   - Username
   - Password
   - Host (usually localhost)

4. Click "phpMyAdmin" to open the database manager
5. Select your database from the left sidebar
6. Click the "SQL" tab
7. Copy this entire content and paste it into the SQL tab:

```sql
-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Databases table
CREATE TABLE databases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_db (user_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes
CREATE INDEX idx_user_id ON databases(user_id);
CREATE INDEX idx_created_at ON databases(created_at);
```

8. Click "Go" to execute the SQL

### 2. Upload Files (10 minutes)

1. Download all files from this project
2. Open your FTP client (FileZilla)
3. Connect to your InfinityFree FTP server:
   - Host: Your FTP hostname (from control panel)
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21

4. Navigate to `public_html` folder
5. Upload all files and folders to `public_html`
6. Create a new folder called `data` in `public_html`

Your folder structure should look like:
```
public_html/
├── index.php
├── dashboard.php
├── database.php
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── api/
│   ├── create_database.php
│   ├── delete_database.php
│   ├── upload_csv.php
│   ├── add_row.php
│   └── delete_row.php
├── config/
│   ├── db.php
│   └── database.sql
├── css/
│   └── style.css
├── js/
│   └── script.js
├── data/  (this folder you create)
└── README.md
```

### 3. Configure Database Connection (5 minutes)

1. In your FTP client, right-click on `config/db.php` and click "View/Edit"
2. Update these lines with YOUR database credentials:

```php
define('DB_HOST', 'localhost');        // Usually: localhost
define('DB_USER', 'your_username');    // Your MySQL username
define('DB_PASS', 'your_password');    // Your MySQL password
define('DB_NAME', 'your_database');    // Your database name
```

3. Save and close the file (it will upload automatically)

### 4. Test Your Installation

1. Open your browser
2. Go to: `https://yourdomain.infinityfree.com/` (replace with your domain)
3. You should see the login/register page
4. Try registering a test account
5. Log in and create a test database

## Verification Checklist

✅ Database tables created in phpMyAdmin
✅ All files uploaded to public_html
✅ Database credentials in config/db.php are correct
✅ data/ folder exists and is writable
✅ Can register a new account
✅ Can log in with the account
✅ Can create a database
✅ Can upload CSV files

## Troubleshooting

### "Connection failed" error
- Check DB credentials in config/db.php
- Verify database exists in phpMyAdmin
- Check database is selected in phpMyAdmin

### Cannot upload files
- Make sure data/ folder exists
- Check folder permissions (should be 755)
- Verify file is valid CSV format

### White screen / no output
- Check browser console for errors (F12)
- Check server error logs in InfinityFree control panel
- Make sure PHP is enabled for your account

### Files not uploading via FTP
- Try passive mode in FTP client (FileZilla: Edit > Settings > Connection > FTP)
- Use text mode for .php files
- Use binary mode for .csv files

## Next Steps

1. Customize the design in `css/style.css`
2. Test with the sample.csv file provided
3. Create a few test databases
4. Invite others to register
5. Back up your data regularly

## Security Reminders

⚠️ Important:
- Always use a strong password for your database
- Do not share database credentials
- Keep PHP version updated
- Regularly backup your data
- Use HTTPS when possible

## Support Resources

- InfinityFree Help: https://support.infinityfree.com/
- PHP Documentation: https://www.php.net/manual/
- CSV Format Guide: https://tools.ietf.org/html/rfc4180

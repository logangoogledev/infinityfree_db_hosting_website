# Quick Reference Guide

## 🚀 Getting Started (TL;DR)

1. Create MySQL database on InfinityFree
2. Run `config/database.sql` in PhpMyAdmin
3. Upload all files to `public_html` via FTP
4. Edit `config/db.php` with your credentials
5. Visit your domain and register!

## 📁 File Guide

| File | Purpose |
|------|---------|
| `index.php` | Login/Register landing page |
| `dashboard.php` | User dashboard (list databases) |
| `database.php` | View/edit specific database |
| `config/db.php` | Database connection (EDIT THIS!) |
| `config/database.sql` | Database setup script |
| `auth/login.php` | Handle login |
| `auth/register.php` | Handle registration |
| `auth/logout.php` | Handle logout |
| `api/create_database.php` | Create database API |
| `api/delete_database.php` | Delete database API |
| `api/upload_csv.php` | Upload CSV API |
| `api/add_row.php` | Add row API |
| `api/delete_row.php` | Delete row API |
| `css/style.css` | All styling |
| `js/script.js` | Frontend functionality |

## 🔑 Important Configuration

**Only file you MUST edit:**
```php
// config/db.php
define('DB_HOST', 'localhost');      // InfinityFree host
define('DB_USER', 'username');       // YOUR MySQL username
define('DB_PASS', 'password');       // YOUR MySQL password
define('DB_NAME', 'database');       // YOUR database name
```

Get these from InfinityFree Control Panel → MySQL Databases

## 📊 User Flow

```
START
  ↓
User Registration (auth/register.php)
  ↓
Login (auth/login.php) → Session created
  ↓
Dashboard (dashboard.php) → List databases
  ↓
Create Database (api/create_database.php)
  ↓
View Database (database.php)
  ↓
Add/Edit/Delete Data
  ├─ Upload CSV (api/upload_csv.php)
  ├─ Add Row (api/add_row.php)
  └─ Delete Row (api/delete_row.php)
  ↓
Logout (auth/logout.php) → Session destroyed
  ↓
END
```

## 🗄️ Database Tables

**users table:**
- Stores user accounts
- One row per user
- Password hashed with bcrypt

**databases table:**
- Stores database records
- One row per database
- Points to user via user_id

**CSV Files:**
- Stored in `data/user_{id}/database_{id}.csv`
- One CSV per database
- Auto-created in data folder

## 🔒 Security Checklist

- [x] Passwords bcrypt hashed
- [x] SQL injection prevented (prepared statements)
- [x] Session-based auth
- [x] User data isolated
- [x] File permissions set correctly
- [x] HTTPS recommended (use with InfinityFree)

## 📱 Responsive Design

- Desktop (1200px+): 3+ database columns
- Tablet (768px-1199px): 2 database columns
- Mobile (< 768px): 1 database column, stacked layout

## 🐛 Common Issues & Fixes

| Error | Fix |
|-------|-----|
| "Connection failed" | Check DB credentials in config/db.php |
| "Cannot upload CSV" | Ensure data/ folder exists and is writable |
| "Undefined variable" | Check $_SESSION is set (user logged in) |
| "Permission denied" | Set folder permissions to 755 via FTP |
| White screen | Check PHP error logs in control panel |
| CSV not loading | Verify file format (commas, UTF-8) |

## 📊 CSV File Format

✅ **Correct:**
```
Name,Email,Age
John,john@example.com,28
Jane,jane@example.com,32
```

❌ **Incorrect:**
```
Name | Email | Age        (pipes instead of commas)
Name,Email,Age,           (trailing comma)
"Name","Email","Age"      (quotes - not needed)
```

## 🎨 Customization

**Change Colors:**
Edit `css/style.css`, look for color values like `#667eea`

**Change Logo/Title:**
Edit text in navbar section of HTML files

**Change Table Design:**
Modify `.data-table` styles in `css/style.css`

**Add New Fields:**
Requires modifying database schema and forms

## 📈 Performance Tips

1. Keep CSV files < 1MB
2. Create separate databases for different data types
3. Delete old unused databases
4. Avoid thousands of rows in single database
5. Use Chrome DevTools to check load times

## 🔄 Development Workflow

1. Make changes locally
2. Test in browser
3. Check browser console (F12)
4. Upload via FTP
5. Test on live server
6. Verify in PhpMyAdmin

## 📞 Support Resources

| Topic | Resource |
|-------|----------|
| InfinityFree Issues | https://support.infinityfree.com/ |
| PHP Help | https://www.php.net/manual/ |
| MySQL Help | https://dev.mysql.com/doc/ |
| CSV Format | https://tools.ietf.org/html/rfc4180 |
| FTP Help | https://filezilla-project.org/wiki/ |

## 🚀 Deployment Checklist

- [ ] SQL script executed in PhpMyAdmin
- [ ] All files uploaded via FTP
- [ ] config/db.php updated with credentials
- [ ] data/ folder created
- [ ] Registration works
- [ ] Login works
- [ ] Can create database
- [ ] Can upload CSV
- [ ] Can add rows
- [ ] Can delete rows
- [ ] Can delete database

## 💾 Backup Recommendations

**Weekly:**
- Download CSV files from each database
- Export user list from PhpMyAdmin

**Monthly:**
- Full database backup from InfinityFree control panel
- Store in secure location

**Before Major Changes:**
- Backup entire public_html folder

## 🔐 Default Security Settings

```php
PASSWORD_HASH: bcrypt
SESSION_TIMEOUT: Server default (~24 min)
CSRF_TOKENS: Not explicitly implemented (consider adding)
FILE_PERMISSIONS: 755 (folders), 644 (files)
DATABASE_CHARSET: utf8mb4
```

## ⚡ Quick Commands (via Terminal)

```bash
# List all PHP files
find . -name "*.php" -type f

# Check file permissions
ls -la config/db.php

# Count lines of code
wc -l *.php auth/*.php api/*.php

# Verify SQL syntax
mysql --user=root --password < config/database.sql
```

## 📝 API Response Format

All API endpoints return JSON:

```json
Success:
{ "success": true, "db_id": 123 }

Error:
{ "success": false, "error": "Error message" }
```

## 🎯 Feature Priorities (If Customizing)

**High Priority:**
- Export CSV functionality
- Row search/filter
- Database sharing

**Medium Priority:**
- Advanced permissions
- Database templates
- Automatic backups

**Low Priority:**
- Real-time collaboration
- Mobile app
- API endpoints

## 📋 Monthly Maintenance

- [ ] Review error logs
- [ ] Check database storage usage
- [ ] Backup important data
- [ ] Test login/registration
- [ ] Verify all features working
- [ ] Check for PHP updates
- [ ] Remove old test accounts

## 🎓 Learning Resources

**PHP Tutorials:**
- https://www.w3schools.com/php/
- https://www.codecademy.com/learn/learn-php

**MySQL Tutorials:**
- https://www.w3schools.com/sql/
- https://www.tutorialspoint.com/mysql/

**Web Development:**
- https://developer.mozilla.org/en-US/
- https://www.freecodecamp.org/

---

**Last Updated:** December 2024
**Version:** 1.0.0

# Database Hosting Website

A web application for hosting and managing databases using CSV files. Built with HTML, JavaScript, and PHP. Perfect for InfinityFree hosting!

## Features

✅ **User Authentication** - Register and login with secure password hashing
✅ **Database Management** - Create, view, and delete databases
✅ **CSV Storage** - Store databases as CSV files in separate user directories
✅ **Row Management** - Add and delete rows from your databases
✅ **CSV Upload** - Upload CSV files to populate databases
✅ **Responsive Design** - Works on desktop and mobile devices
✅ **InfinityFree Compatible** - Uses MySQL (available on InfinityFree)

## Project Structure

```
.
├── index.php                 # Login/Register page
├── dashboard.php             # User dashboard
├── database.php              # Database view page
├── config/
│   ├── db.php               # Database connection
│   └── database.sql         # SQL setup script
├── auth/
│   ├── login.php            # Login handler
│   ├── register.php         # Registration handler
│   └── logout.php           # Logout handler
├── api/
│   ├── create_database.php  # Create database API
│   ├── delete_database.php  # Delete database API
│   ├── upload_csv.php       # Upload CSV API
│   ├── add_row.php          # Add row API
│   └── delete_row.php       # Delete row API
├── css/
│   └── style.css            # Styling
├── js/
│   └── script.js            # Client-side JavaScript
├── data/                    # User data directories (auto-created)
└── README.md                # This file
```

## Installation Guide for InfinityFree

### Step 1: Set up the Database

1. Go to your **InfinityFree Control Panel** (infinityfree.com)
2. Navigate to **MySQL Databases**
3. Create a new database (note the name, username, and password)
4. Go to **phpMyAdmin**
5. Select your database
6. Click the **SQL** tab
7. Copy and paste the contents of `config/database.sql`
8. Click **Go** to execute

### Step 2: Upload Files

1. Connect to your FTP server using an FTP client (Filezilla recommended)
2. Upload all files to your **public_html** directory (the root of your website)
3. The directory structure should be:
   ```
   public_html/
   ├── index.php
   ├── dashboard.php
   ├── database.php
   ├── config/
   ├── auth/
   ├── api/
   ├── css/
   ├── js/
   └── data/  (create this folder via FTP)
   ```

### Step 3: Configure Database Connection

1. Edit `config/db.php`
2. Replace the placeholder values with your InfinityFree database credentials:
   ```php
   define('DB_HOST', 'your_host');      // Usually: localhost
   define('DB_USER', 'your_username');   // Your MySQL username
   define('DB_PASS', 'your_password');   // Your MySQL password
   define('DB_NAME', 'your_database');   // Your database name
   ```
3. Save and upload the file

### Step 4: Create Data Directory

1. Using your FTP client, create a new folder called `data` in your `public_html` directory
2. The application will automatically create user subdirectories here

## Usage

### Creating an Account

1. Navigate to your website (e.g., `yourdomain.infinityfree.com`)
2. Click the **Register** tab
3. Fill in username, email, and password
4. Click **Register**

### Creating a Database

1. Log in with your credentials
2. Click **+ Create Database**
3. Enter a database name
4. Optionally upload a CSV file
5. Click **Create Database**

### Managing Data

- **View Database**: Click the "View" button on any database card
- **Upload CSV**: Click "Upload CSV" to import data
- **Add Rows**: Click "Add Row" to manually add entries
- **Delete Rows**: Click "Delete" to remove rows
- **Delete Database**: Click "Delete" on the dashboard to remove a database

## CSV File Format

Your CSV files should follow this format:

```
Name,Email,Age
John Doe,john@example.com,28
Jane Smith,jane@example.com,32
```

- First row is headers
- Use commas to separate columns
- No special formatting needed

## Technical Details

### Backend
- **Language**: PHP
- **Database**: MySQL
- **Server**: Supports any PHP-enabled hosting (tested with InfinityFree)

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Responsive design with Flexbox/Grid
- **JavaScript**: Vanilla JS (no dependencies)

### Data Storage
- User credentials: MySQL database
- Database records: MySQL table
- CSV data: Files stored in `data/user_{id}/database_{id}.csv`

## Important Notes

### For InfinityFree Users

1. **File Size Limits**: InfinityFree has file upload limits. Most CSV files should work fine.
2. **Database Storage**: Your CSV files are stored in the `data/` directory on the server.
3. **Backups**: Regularly download your CSV files as backups.
4. **Database Limits**: Free accounts have database size limits. Monitor your usage.

### Security Considerations

1. Passwords are hashed using bcrypt (industry standard)
2. Sessions are used for authentication
3. User data is isolated per account
4. Change default database credentials after setup

### Optional: MongoDB Integration

If your hosting supports MongoDB (or if you use MongoDB Atlas):

1. Install MongoDB PHP driver
2. Uncomment the MongoDB section in `config/db.php`
3. Update connection string with your MongoDB Atlas credentials
4. Modify queries to use MongoDB syntax

## Troubleshooting

### "Connection failed"
- Check your database credentials in `config/db.php`
- Verify database exists in InfinityFree control panel
- Ensure MySQL is enabled for your account

### "Permission denied" errors
- Check folder permissions (should be 755 for directories, 644 for files)
- Ensure `data/` directory exists and is writable

### CSV not uploading
- Check file format is valid CSV
- Verify file size is under InfinityFree limits
- Ensure `data/` directory exists and is writable

### "Database not found" errors
- Run the SQL setup script again
- Check database name in `config/db.php`

## Performance Tips

1. Keep CSV files reasonably sized (< 1MB recommended)
2. Create separate databases for different data types
3. Regularly clean up old/unused databases
4. Use meaningful database names for organization

## Future Enhancements

- [ ] Export databases to CSV
- [ ] Search and filter functionality
- [ ] Database sharing between users
- [ ] Advanced query builder
- [ ] Real-time collaboration
- [ ] Data validation rules
- [ ] Automatic backups

## Support

For issues with:
- **InfinityFree hosting**: Visit https://infinityfree.com/
- **PHP/MySQL**: Check PHP and MySQL documentation
- **CSV format**: Use standard RFC 4180 CSV format

## License

This project is open source and available for personal and commercial use.

## Contributing

Feel free to fork, modify, and improve this project!

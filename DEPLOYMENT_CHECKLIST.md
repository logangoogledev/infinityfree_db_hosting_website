# Deployment Checklist for InfinityFree

Use this checklist to ensure your application is properly deployed and working.

## Pre-Deployment

- [ ] All files are created and tested locally
- [ ] Database credentials are prepared
- [ ] FTP client is ready (FileZilla or similar)
- [ ] InfinityFree account is active
- [ ] PhpMyAdmin access verified

## Database Setup

- [ ] MySQL database created in InfinityFree control panel
- [ ] Database username and password noted
- [ ] PhpMyAdmin accessed successfully
- [ ] SQL setup script executed successfully
- [ ] Both tables (users, databases) are visible in PhpMyAdmin

## File Upload

- [ ] All PHP files uploaded to public_html/
- [ ] All CSS files in css/ folder uploaded
- [ ] All JS files in js/ folder uploaded
- [ ] All auth/ files uploaded
- [ ] All api/ files uploaded
- [ ] config/database.sql uploaded (reference file)
- [ ] config/db.php uploaded with correct credentials
- [ ] README.md uploaded
- [ ] SETUP.md uploaded
- [ ] data/ folder created in public_html
- [ ] .gitignore created

## Configuration

- [ ] config/db.php updated with database credentials
- [ ] DB_HOST set correctly
- [ ] DB_USER set correctly
- [ ] DB_PASS set correctly
- [ ] DB_NAME set correctly
- [ ] data/ folder exists and is writable
- [ ] Folder permissions set correctly (755)

## Testing - Account Creation

- [ ] Navigate to https://yourdomain.infinityfree.com
- [ ] See login/register page
- [ ] Click Register tab
- [ ] Enter test username: "testuser"
- [ ] Enter test email: "test@example.com"
- [ ] Enter test password: "testpass123"
- [ ] Confirm password matches
- [ ] Click Register button
- [ ] See success message
- [ ] User appears in PhpMyAdmin users table

## Testing - Login

- [ ] Click Login tab
- [ ] Enter test email: "test@example.com"
- [ ] Enter test password: "testpass123"
- [ ] Click Login button
- [ ] Redirected to dashboard
- [ ] See "Welcome, testuser" in navbar
- [ ] See "Your Databases" heading

## Testing - Database Creation

- [ ] On dashboard, click "+ Create Database"
- [ ] Enter database name: "Test Database"
- [ ] Do NOT upload CSV yet
- [ ] Click Create Database
- [ ] See new database card on dashboard
- [ ] Click View on the new database
- [ ] See empty database message
- [ ] Check PhpMyAdmin - new record in databases table

## Testing - CSV Upload

- [ ] Go back to dashboard
- [ ] Click View on Test Database
- [ ] Click "Upload CSV"
- [ ] Upload sample.csv file
- [ ] See data table with headers
- [ ] Verify all sample data rows are visible

## Testing - Add Row Manually

- [ ] Click "Add Row" button
- [ ] Fill in form fields with test data
- [ ] Click "Add Row" button
- [ ] See new row in table
- [ ] Verify data persisted on page reload

## Testing - Delete Row

- [ ] Click Delete on a row
- [ ] Confirm deletion
- [ ] See row removed from table
- [ ] Verify deletion persisted on page reload

## Testing - Delete Database

- [ ] Go back to dashboard
- [ ] Click Delete on a database
- [ ] Confirm deletion
- [ ] See database removed from dashboard
- [ ] Check PhpMyAdmin - record deleted from databases table
- [ ] Check FTP - CSV file deleted from data folder

## Testing - Logout

- [ ] Click Logout in navbar
- [ ] Redirected to login page
- [ ] Session cleared

## Security Testing

- [ ] Try accessing dashboard.php without login - redirected to index.php
- [ ] Try accessing database.php directly - redirected
- [ ] Try accessing another user's database - should fail
- [ ] Try accessing another user's API - should fail
- [ ] Check HTML source - no sensitive data exposed

## Performance Testing

- [ ] Large CSV upload (test with 1000+ rows)
- [ ] Multiple databases created
- [ ] Dashboard loads in reasonable time
- [ ] Page responsive on mobile browser

## Browser Compatibility

- [ ] Chrome/Edge - working
- [ ] Firefox - working
- [ ] Safari - working
- [ ] Mobile browser - responsive and working

## Final Checks

- [ ] All links are working
- [ ] No console errors (F12 > Console)
- [ ] No error emails from InfinityFree
- [ ] All redirects are working
- [ ] Session timeout working appropriately
- [ ] Password hashing verified (bcrypt)

## Backup & Maintenance

- [ ] Set up automatic backups
- [ ] Download user CSV files regularly
- [ ] Monitor database storage usage
- [ ] Check error logs weekly
- [ ] Review InfinityFree terms for data retention

## Go Live

- [ ] All tests passed ✅
- [ ] Announce website to users
- [ ] Monitor for issues first week
- [ ] Collect user feedback
- [ ] Plan feature enhancements

## Troubleshooting Notes

If any test fails, refer to these resources:
- SETUP.md - detailed setup instructions
- README.md - general documentation
- InfinityFree Control Panel - check error logs
- Browser Console (F12) - frontend errors
- PhpMyAdmin - verify database state


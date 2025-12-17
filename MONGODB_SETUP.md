# MongoDB Setup Guide

This guide will help you set up your application to use **MongoDB** instead of MySQL.

## Why MongoDB?

- ✅ NoSQL - Flexible schema
- ✅ Cloud-hosted - No server setup needed
- ✅ Scalable - Handles growth easily
- ✅ Free tier - MongoDB Atlas free tier has plenty of capacity
- ✅ Easy integration - Works great with PHP

## Prerequisites

- MongoDB account (free at https://www.mongodb.com/cloud/atlas)
- Composer installed on your server or locally
- PHP 7.2+ with MongoDB extension

## Step 1: Create MongoDB Atlas Account

1. Go to https://www.mongodb.com/cloud/atlas
2. Click **Sign Up** (or login if you have an account)
3. Create a new organization and project
4. Click **Build a Database**
5. Choose **Free M0 cluster** tier
6. Select a region close to your users
7. Click **Create Cluster** (takes 2-3 minutes)

## Step 2: Create Database User

After cluster is created:

1. Go to **Security** → **Database Access**
2. Click **Add New Database User**
3. Create username and password (save these!)
4. Set permissions: **Atlas admin**
5. Click **Add User**

Example credentials:
```
Username: db_admin
Password: MySecure@Password123
```

## Step 3: Allow IP Access

1. Go to **Security** → **Network Access**
2. Click **Add IP Address**
3. Select **Allow Access from Anywhere** (0.0.0.0/0)
4. Click **Confirm**

> ⚠️ **Note**: In production, restrict to your server's IP address

## Step 4: Get Connection String

1. Go back to **Databases**
2. Click **Connect** on your cluster
3. Select **Connect your application**
4. Choose **PHP** driver
5. Copy the connection string

Example:
```
mongodb+srv://db_admin:MySecure@Password123@cluster0.abc123.mongodb.net/?retryWrites=true&w=majority
```

## Step 5: Install MongoDB PHP Driver

### Option A: If you can install packages on InfinityFree

Run via terminal or cPanel terminal:
```bash
composer require mongodb/mongodb
```

This creates a `vendor/` folder with MongoDB libraries.

### Option B: If Composer is not available

Ask your hosting provider to install the MongoDB extension, or use MongoDB Atlas with the PHP MongoDB driver already installed.

## Step 6: Update config/db.php

Open `config/db.php` and update:

```php
// Set to true to use MongoDB
define('USE_MONGODB', true);

// Your MongoDB Atlas connection string
define('MONGODB_URI', 'mongodb+srv://db_admin:MySecure@Password123@cluster0.abc123.mongodb.net/?retryWrites=true&w=majority');

// Database name
define('MONGODB_DB', 'db_hosting');
```

Replace:
- `db_admin` with your database user
- `MySecure@Password123` with your password
- `cluster0.abc123.mongodb.net` with your connection string
- `db_hosting` with your database name

## Step 7: Upload Files

1. Upload all files via FTP to `public_html/`
2. Make sure `vendor/` folder is included if using Composer
3. Ensure `data/` folder is created (for CSV files)

## Step 8: Test Connection

1. Visit your website
2. Try registering a new account
3. If successful, user data is stored in MongoDB!
4. Check MongoDB Atlas console to verify data

### Verify in MongoDB Atlas

1. Go to **Databases**
2. Click **Browse Collections** on your cluster
3. Look for `db_hosting` database
4. View `users` collection
5. You should see your registered user!

## Database Collections in MongoDB

### users collection
```json
{
  "_id": ObjectId("..."),
  "username": "john_doe",
  "email": "john@example.com",
  "password": "$2y$10$...",
  "created_at": ISODate("2024-12-17T10:30:00Z"),
  "updated_at": ISODate("2024-12-17T10:30:00Z")
}
```

### databases collection
```json
{
  "_id": ObjectId("..."),
  "user_id": "user_object_id_string",
  "name": "My Database",
  "created_at": ISODate("2024-12-17T10:35:00Z"),
  "updated_at": ISODate("2024-12-17T10:35:00Z")
}
```

## CSV File Storage

Even with MongoDB, CSV data is still stored as files in the `data/` folder:
```
data/
└── user_{user_id}/
    ├── database_{db_id}.csv
    └── database_{db_id_2}.csv
```

This is efficient because:
- CSV files are fast for tabular data
- MongoDB stores user accounts
- Best of both worlds!

## Common Issues & Solutions

### "MongoDB connection error"

**Problem**: Connection string is incorrect
**Solution**: 
- Copy connection string from MongoDB Atlas
- Replace password (don't copy from URL directly)
- Make sure username/password are URL-encoded if they contain special characters

### "Class 'MongoDB\Client' not found"

**Problem**: MongoDB PHP library not installed
**Solution**:
- Run `composer require mongodb/mongodb`
- Or ask hosting provider to install MongoDB driver

### "No suitable servers found"

**Problem**: Network access not allowed
**Solution**:
1. Go to MongoDB Atlas **Network Access**
2. Add IP address or use 0.0.0.0/0

### "Authentication failed"

**Problem**: Wrong username or password
**Solution**:
1. Go to MongoDB Atlas **Database Access**
2. Verify your username/password
3. Reset password if needed

## Switching Between MySQL and MongoDB

You can easily switch between databases:

**To use MySQL:**
```php
// config/db.php
// Comment out or set to false:
define('USE_MONGODB', false);

// MySQL configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'user');
define('DB_PASS', 'pass');
define('DB_NAME', 'database');
```

**To use MongoDB:**
```php
// config/db.php
// Set to true:
define('USE_MONGODB', true);

// MongoDB configuration
define('MONGODB_URI', 'mongodb+srv://...');
define('MONGODB_DB', 'db_hosting');
```

## Monitoring MongoDB Usage

Monitor your free tier usage:

1. Log into MongoDB Atlas
2. Go to **Databases**
3. View **Metrics** tab
4. Check:
   - Documents stored
   - Connections
   - Storage usage
   - Operations per second

Free tier limits:
- 512 MB storage
- 100 connections

For most applications, this is plenty!

## Upgrading Your Cluster

If you need more storage:

1. Go to **Databases**
2. Click **Edit Configuration** on your cluster
3. Change tier to **M2** or higher (paid)
4. Update automatically applies

## Security Best Practices

1. **Change default password** in Database Access
2. **Restrict IP access** to your server only
3. **Use strong passwords** (at least 12 characters)
4. **Enable IP Whitelist** in Network Access
5. **Rotate credentials** every 90 days
6. **Enable two-factor authentication** on MongoDB account

## Performance Tips

1. MongoDB is fast for user lookups
2. CSV files are efficient for tabular data
3. Consider indexing frequently-searched fields
4. Monitor usage on free tier

## Backup & Recovery

### Automatic Backups
MongoDB Atlas provides:
- Daily backups (free tier)
- 7-day retention
- Available in **Backup** section

### Manual Export
```bash
# Export users collection
mongoexport --uri "mongodb+srv://..." --collection users --out users.json
```

## MongoDB Compass (Desktop GUI)

For easier data management:

1. Download MongoDB Compass: https://www.mongodb.com/products/compass
2. Connect using your connection string
3. Browse collections visually
4. Edit documents easily
5. Run queries

## Support & Resources

- **MongoDB Docs**: https://docs.mongodb.com/
- **MongoDB PHP Driver**: https://www.mongodb.com/docs/drivers/php/
- **MongoDB Atlas Help**: https://docs.atlas.mongodb.com/
- **Stack Overflow**: Tag `mongodb`

## Next Steps

1. ✅ Create MongoDB Atlas account
2. ✅ Create cluster and database user
3. ✅ Get connection string
4. ✅ Update config/db.php
5. ✅ Upload files
6. ✅ Test registration/login
7. ✅ Monitor in MongoDB Atlas console

Congratulations! You now have a scalable, cloud-based database solution! 🎉

---

**Note**: The application still uses CSV files for storing table data. If you want to store all data in MongoDB, you would need additional modifications to the API endpoints. Contact support for guidance on full MongoDB integration.

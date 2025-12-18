#!/bin/bash
# Security Setup Script
# Run this script to initialize security tables and features

echo "==================================="
echo "Database Hosting - Security Setup"
echo "==================================="
echo ""

# Create logs directory
mkdir -p logs
chmod 700 logs
echo "✓ Created logs directory"

# Set proper file permissions
chmod 700 config/
chmod 600 config/db.php
chmod 600 config/security.php
echo "✓ Set proper file permissions"

echo ""
echo "==================================="
echo "Database Tables Setup"
echo "==================================="
echo ""
echo "To complete security setup, run the following SQL in your database:"
echo ""
echo "1. Log into PhpMyAdmin or your database management tool"
echo "2. Select your database: if0_40374989_db_storage"
echo "3. Go to SQL tab"
echo "4. Copy and paste the entire contents of: config/security_schema.sql"
echo "5. Click Execute"
echo ""
echo "After running the SQL, your security system will be fully activated!"
echo ""

echo "==================================="
echo "Configuration"
echo "==================================="
echo ""
echo "Set these environment variables for email alerts:"
echo "export ADMIN_EMAIL='your-admin-email@example.com'"
echo "export SECURITY_EMAIL='security@example.com'"
echo ""

echo "==================================="
echo "Features Enabled"
echo "==================================="
echo "✓ Rate limiting (100 requests/hour per IP)"
echo "✓ Login attempt tracking (5 attempts max, then lock)"
echo "✓ Comprehensive audit logging"
echo "✓ Security breach detection"
echo "✓ Email alerts for suspicious activity"
echo "✓ User folder isolation (/data/user_{id}/)"
echo "✓ API access logging"
echo "✓ IP address tracking"
echo "✓ User agent logging"
echo "✓ CSRF token generation"
echo ""

echo "==================================="
echo "User-Facing Features"
echo "==================================="
echo "✓ New Security Center page: /security.php"
echo "✓ View audit logs"
echo "✓ Monitor API usage"
echo "✓ Check recent activity"
echo "✓ Security alerts via email"
echo ""

echo "==================================="
echo "Setup Complete!"
echo "==================================="

# LILAC System - Quick Installation Guide

## 🚀 Quick Setup (5 minutes)

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install with default settings
3. Start Apache and MySQL in XAMPP Control Panel

### Step 2: Deploy Files
1. Extract LILAC files to: `C:\xampp\htdocs\LILAC\`
2. Ensure all files are in the correct location

### Step 3: Setup Database
1. Open browser: http://localhost/phpmyadmin
2. Create new database: `lilac_system`
3. Import file: `sql/schema.sql` (found in LILAC folder)

### Step 4: Access System
1. Open browser: http://localhost/LILAC/dashboard.html
2. System is ready to use!

## ✅ Verification Checklist
- [ ] XAMPP installed and running
- [ ] Apache service: ✅ Running
- [ ] MySQL service: ✅ Running  
- [ ] Files in: `xampp/htdocs/LILAC/`
- [ ] Database `lilac_system` created
- [ ] Schema imported successfully
- [ ] Dashboard loads: http://localhost/LILAC/dashboard.html

## 🔧 Troubleshooting
**Problem: Page shows "No tables found"**
- Solution: Import `sql/schema.sql` in phpMyAdmin

**Problem: Blank page**
- Solution: Check Apache is running in XAMPP

**Problem: Database connection error**  
- Solution: Verify MySQL is running and database exists

## 📁 What You Get
- Complete document management system
- Meeting scheduler
- Budget tracking
- MOU/MOA management
- Awards tracking
- Template system
- Registrar file management

## 💡 No Technical Skills Required
This system is designed to work out-of-the-box with XAMPP. Just follow the 4 steps above! 
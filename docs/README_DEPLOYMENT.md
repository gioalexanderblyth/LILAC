# LILAC System Deployment Guide

## Overview
LILAC (Learning Institution Liaison and Administration Center) is a web-based management system for educational institutions.

## Requirements
- **Web Server**: Apache with PHP support
- **Database**: MySQL 5.7 or higher
- **PHP**: Version 7.4 or higher
- **Browser**: Modern web browser (Chrome, Firefox, Edge, Safari)

## Installation Options

### Option 1: XAMPP Installation (Recommended for Development)

1. **Download and Install XAMPP**
   - Visit: https://www.apachefriends.org/
   - Download XAMPP for your operating system
   - Install with Apache, MySQL, and PHP components

2. **Deploy LILAC Files**
   ```
   Extract LILAC files to: xampp/htdocs/LILAC/
   ```

3. **Start Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

4. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create database named: `lilac_system`
   - Import schema: `sql/schema.sql`

5. **Configure Database Connection**
   - Edit `config/database.php`
   - Update connection settings if needed:
     ```php
     $host = 'localhost';
     $dbname = 'lilac_system';
     $username = 'root';
     $password = '';
     ```

6. **Access System**
   - Open browser: http://localhost/LILAC/dashboard.html

### Option 2: Shared Hosting Deployment

1. **Upload Files**
   - Upload all LILAC files to your web hosting public folder
   - Maintain directory structure

2. **Create Database**
   - Create MySQL database via hosting control panel
   - Import `sql/schema.sql`
   - Note database credentials

3. **Update Configuration**
   - Edit `config/database.php` with hosting database details

4. **Set Permissions**
   - Ensure PHP files are executable
   - Set proper folder permissions for uploads

### Option 3: Docker Deployment (Advanced)

Create a `docker-compose.yml`:
```yaml
version: '3.8'
services:
  web:
    image: php:7.4-apache
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
  
  db:
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: lilac_system
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - ./sql/schema.sql:/docker-entrypoint-initdb.d/schema.sql
```

Run: `docker-compose up`

## File Structure
```
LILAC/
├── dashboard.html          # Main dashboard
├── documents.html          # Document management
├── meetings.html           # Meeting scheduler
├── funds.html             # Budget tracking
├── mou-moa.html           # MOU/MOA management
├── awards.html            # Awards tracking
├── templates.html         # Template management
├── registrar_files.html   # Registrar files
├── api/                   # Backend API endpoints
│   ├── dashboard.php
│   ├── documents.php
│   ├── meetings.php
│   ├── funds.php
│   ├── mous.php
│   └── awards.php
├── config/                # Configuration files
│   └── database.php
├── sql/                   # Database schema
│   └── schema.sql
└── uploads/               # File upload directory
```

## Database Tables
- `budgets` - Budget tracking
- `transactions` - Financial transactions
- `documents` - Document management
- `meetings` - Meeting scheduling
- `templates` - Document templates
- `registrar_files` - Registrar file management
- `awards` - Awards and recognition
- `mous` - MOUs and MOAs
- `budget_requests` - Budget request tracking

## Security Notes
- Change default database passwords in production
- Set up proper file upload restrictions
- Configure SSL for production environments
- Regularly backup the database

## Troubleshooting

### Common Issues:
1. **Blank pages**: Check if Apache and MySQL are running
2. **Database connection errors**: Verify credentials in `config/database.php`
3. **File upload issues**: Check folder permissions on `uploads/` directory
4. **API not working**: Ensure PHP is properly configured

### Error Logs:
- Apache errors: `xampp/apache/logs/error.log`
- PHP errors: Enable error reporting in development

## Support
For issues or questions:
1. Check this deployment guide
2. Verify all requirements are met
3. Check error logs for specific issues
4. Ensure database schema is properly imported

## Quick Start Checklist
- [ ] XAMPP installed and running
- [ ] Files copied to htdocs/LILAC
- [ ] Database created and schema imported
- [ ] Configuration file updated
- [ ] Apache and MySQL services started
- [ ] Accessed http://localhost/LILAC/dashboard.html

## Production Deployment
For production use:
- Use proper web hosting service
- Configure SSL certificates
- Set up regular database backups
- Configure proper security headers
- Monitor system performance 
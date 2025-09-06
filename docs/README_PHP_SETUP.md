# LILAC System - PHP Backend Setup

This guide explains how to set up the PHP backend with SQL database for the LILAC Fund Tracking System.

## Requirements

- **PHP 7.4 or higher**
- **MySQL 5.7+ or MariaDB 10.2+**
- **Apache/Nginx web server** (or PHP built-in server for development)
- **PDO MySQL extension** (usually included with PHP)

## Installation

### Method 1: Automatic Installation (Recommended)

1. **Set up a web server** pointing to the LILAC directory
2. **Navigate to the installation page** in your browser:
   ```
   http://localhost/LILAC/install.php
   ```
3. **Fill in your database credentials**:
   - Database Host: `localhost` (usually)
   - Database Username: Your MySQL username (default: `root`)
   - Database Password: Your MySQL password
   - Database Name: `lilac_system` (or choose your own)

4. **Click "Install LILAC System"**
5. **Success!** The system will create the database and tables automatically.

### Method 2: Manual Installation

1. **Create the database manually**:
   ```sql
   CREATE DATABASE lilac_system;
   ```

2. **Import the schema**:
   ```bash
   mysql -u root -p lilac_system < sql/schema.sql
   ```

3. **Update database configuration** in `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'lilac_system';
   private $username = 'root';
   private $password = 'your_password';
   ```

## File Structure

```
LILAC/
├── api/
│   └── funds.php           # Main API endpoint
├── classes/
│   ├── Budget.php          # Budget management class
│   └── Transaction.php     # Transaction management class
├── config/
│   └── database.php        # Database configuration
├── sql/
│   └── schema.sql          # Database schema
├── install.php             # Installation script
├── funds.html              # Updated frontend (uses PHP backend)
└── README_PHP_SETUP.md     # This file
```

## API Endpoints

The system uses a single API endpoint (`api/funds.php`) with different actions:

### Budget Operations
- **Get Budget**: `GET api/funds.php?action=get_budget`
- **Update Budget**: `POST api/funds.php` with `action=update_budget&amount=1000`

### Transaction Operations
- **Get Transactions**: `GET api/funds.php?action=get_transactions`
- **Add Transaction**: `POST api/funds.php` with:
  ```
  action=add_transaction
  description=Office Supplies
  amount=150.50
  type=expense
  date=2024-01-15
  ```
- **Delete Transaction**: `POST api/funds.php` with `action=delete_transaction&id=1`

### Budget Calculation
- **Get Remaining Budget**: `GET api/funds.php?action=get_remaining_budget`

## Database Schema

### `budgets` table
```sql
id INT AUTO_INCREMENT PRIMARY KEY
amount DECIMAL(15,2) NOT NULL DEFAULT 0.00
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### `transactions` table
```sql
id INT AUTO_INCREMENT PRIMARY KEY
description VARCHAR(255) NOT NULL
amount DECIMAL(15,2) NOT NULL
type ENUM('income', 'expense') NOT NULL
transaction_date DATE NOT NULL
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

## Development Server

For development, you can use PHP's built-in server:

```bash
cd LILAC
php -S localhost:8000
```

Then access: `http://localhost:8000/install.php`

## Features

- ✅ **Budget Management**: Set and update budget amounts
- ✅ **Transaction Tracking**: Add income and expense transactions
- ✅ **Real-time Calculations**: Automatically calculate remaining budget
- ✅ **Data Persistence**: All data stored in MySQL database
- ✅ **AJAX Interface**: Smooth user experience without page reloads
- ✅ **Responsive Design**: Works on desktop and mobile devices

## Migration from localStorage

The system has been upgraded from localStorage to a PHP backend with SQL database:

- **Before**: Data stored in browser's localStorage (temporary)
- **After**: Data stored in MySQL database (permanent)
- **Benefit**: Data persists across different browsers and devices

## Troubleshooting

### Common Issues

1. **"Connection error"**: Check database credentials in `config/database.php`
2. **"500 Internal Server Error"**: Enable PHP error reporting or check server logs
3. **CORS issues**: Ensure the API is on the same domain as the frontend
4. **Permission denied**: Check file permissions for the `config/` directory

### Enable PHP Error Reporting
Add to the top of `api/funds.php`:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## Security Notes

- Change default database credentials in production
- Use environment variables for sensitive configuration
- Implement user authentication for production use
- Validate and sanitize all input data
- Use prepared statements (already implemented)

## Next Steps

1. **Test the installation** by accessing `funds.html`
2. **Add some sample transactions** to verify functionality
3. **Customize the database configuration** for your environment
4. **Consider adding user authentication** for multi-user scenarios

## Support

If you encounter any issues:
1. Check the browser console for JavaScript errors
2. Check the server error logs
3. Verify database connection and credentials
4. Ensure all PHP extensions are installed 
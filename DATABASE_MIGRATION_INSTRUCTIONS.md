# Database Migration Instructions

## Problem
The file upload system is showing the error:
```
Server error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'file_size' in 'field list'
```

This happens because the database table is missing the `file_size` column that was added in the PHP code.

## Solution Options

### Option 1: Automatic Migration (Recommended)
Run the PHP migration script from your terminal:

```bash
# Navigate to your LILAC directory
cd C:\xampp\htdocs\LILAC

# Run the migration script
php apply_migration.php
```

This will:
- ✅ Check if the column already exists
- ✅ Add the `file_size` column if missing
- ✅ Update existing records with default values
- ✅ Verify the migration was successful

### Option 2: Manual SQL Migration
If you prefer to run SQL commands manually:

1. **Open phpMyAdmin** or your MySQL client
2. **Select the `lilac_system` database**
3. **Run this SQL command:**

```sql
ALTER TABLE `documents` 
ADD COLUMN `file_size` BIGINT(20) DEFAULT NULL 
AFTER `filename`;
```

4. **Update existing records:**

```sql
UPDATE `documents` 
SET `file_size` = 0 
WHERE `file_size` IS NULL;
```

### Option 3: Import Migration File
1. Open phpMyAdmin
2. Select your `lilac_system` database
3. Go to the "Import" tab
4. Choose the file `sql/migration_add_file_size.sql`
5. Click "Go" to execute

## Verification

After running the migration, verify it worked:

1. **Check the table structure:**
```sql
DESCRIBE documents;
```

You should see these columns:
- `id` (int)
- `filename` (varchar)
- `file_size` (bigint) ← **This should now exist**
- `document_name` (varchar)
- `category` (varchar)
- `description` (text)
- `file_url` (varchar)
- `upload_date` (datetime)

2. **Test file upload:**
- Go to the Documents page in LILAC
- Try uploading a file
- The file should upload successfully without any errors
- The file size should display correctly in the documents list

## Troubleshooting

### If migration fails:
1. **Check database connection** in `config/database.php`
2. **Verify database permissions** - your MySQL user needs ALTER privileges
3. **Check if column already exists** - you might have already run the migration

### If you still get errors:
1. **Clear any cached database connections** by restarting your web server
2. **Check the PHP error logs** for more detailed error messages
3. **Verify the table structure** matches the expected schema

## What This Migration Does

- **Adds `file_size` column** to store actual file sizes in bytes
- **Updates existing records** with default file_size of 0
- **Maintains data integrity** - no existing data is lost
- **Enables proper file upload tracking** - prevents 0-byte file issues

## Future Database Updates

For new installations, the updated schema file `sql/lilac_system.sql` now includes the `file_size` column, so this migration won't be needed. 
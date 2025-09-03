# Trash Bin Implementation for LILAC Scheduler

## Overview
The trash bin functionality has been implemented for the LILAC Scheduler to provide a safe way to delete meetings. Instead of permanently deleting meetings, they are moved to a trash bin where they can be restored or permanently deleted later.

## Features

### 1. Safe Deletion
- When a user clicks "Delete" on a meeting, it's moved to the trash bin instead of being permanently deleted
- Users receive a confirmation message explaining that the meeting will be moved to trash
- The meeting is no longer visible in the main meetings list or calendar

### 2. Trash Bin Management
- **View Trash**: Access deleted meetings through the trash bin view
- **Restore Meetings**: Restore meetings from trash back to the active meetings list
- **Permanently Delete**: Permanently delete meetings from the trash (cannot be undone)
- **Empty Trash**: Clear all meetings from the trash at once

### 3. User Interface
- **Floating Button**: Cycle through Calendar → Meetings → Trash views
- **Trash Icon**: Visual indicator for trash bin view
- **Confirmation Dialogs**: Safe deletion with clear warnings
- **Status Indicators**: Shows when meetings were deleted and by whom

## Database Changes

### New Table: `meetings_trash`
```sql
CREATE TABLE `meetings_trash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) NOT NULL COMMENT 'Original meeting ID before deletion',
  `title` varchar(255) NOT NULL,
  `meeting_date` date NOT NULL,
  `meeting_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `deleted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the meeting was moved to trash',
  `deleted_by` varchar(100) DEFAULT NULL COMMENT 'User who deleted the meeting',
  `original_created_at` timestamp NULL DEFAULT NULL COMMENT 'Original creation timestamp',
  `original_updated_at` timestamp NULL DEFAULT NULL COMMENT 'Original update timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_original_id` (`original_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_meeting_date` (`meeting_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Trash bin for deleted meetings';
```

## API Endpoints

### New Endpoints Added to `api/scheduler.php`:

1. **GET Trash Meetings**
   ```
   GET api/scheduler.php?action=get_trash
   ```

2. **Restore Meeting**
   ```
   POST api/scheduler.php
   action=restore&trash_id={id}
   ```

3. **Permanently Delete from Trash**
   ```
   POST api/scheduler.php
   action=permanently_delete&trash_id={id}
   ```

4. **Empty Trash**
   ```
   POST api/scheduler.php
   action=empty_trash
   ```

### Modified Endpoint:
- **Delete Meeting**: Now moves to trash instead of permanent deletion

## Installation

### 1. Run Database Migration
```bash
php apply_trash_migration.php
```

### 2. Verify Installation
- Check that the `meetings_trash` table was created
- Test the trash bin functionality in the scheduler

## Usage

### For Users:
1. **Delete a Meeting**: Click the delete button on any meeting
2. **Access Trash**: Use the floating button to cycle to the trash view
3. **Restore Meeting**: Click "Restore" on any meeting in trash
4. **Permanently Delete**: Click "Delete" on a meeting in trash (with confirmation)
5. **Empty Trash**: Use the "Empty Trash" button to clear all deleted meetings

### For Developers:
- The trash bin system uses database transactions to ensure data integrity
- All operations are logged with timestamps and user information
- The system maintains referential integrity by preserving original IDs

## Security Features

1. **Transaction Safety**: All trash operations use database transactions
2. **Confirmation Dialogs**: Users must confirm destructive actions
3. **Audit Trail**: All deletions are logged with timestamps
4. **Data Preservation**: Original meeting data is preserved in trash

## Error Handling

- Database errors are caught and logged
- User-friendly error messages are displayed
- Failed operations are rolled back automatically
- Network errors are handled gracefully

## Future Enhancements

1. **Auto-cleanup**: Automatically delete meetings older than X days from trash
2. **Bulk Operations**: Select multiple meetings for restore/delete
3. **Search/Filter**: Search and filter capabilities in trash view
4. **Export**: Export deleted meetings for audit purposes
5. **Notifications**: Notify users when meetings are restored

## Troubleshooting

### Common Issues:

1. **Migration Fails**: Check database permissions and connection
2. **Trash Not Loading**: Verify the `meetings_trash` table exists
3. **Restore Fails**: Check for duplicate IDs or database constraints
4. **UI Not Updating**: Clear browser cache and refresh page

### Debug Mode:
Enable error reporting in PHP to see detailed error messages during development.

## Support

For issues or questions about the trash bin implementation, refer to:
- Database logs for SQL errors
- Browser console for JavaScript errors
- PHP error logs for server-side issues 
# MOU/MOA Date Extraction System

## Overview

The LILAC document management system now includes automatic date extraction from MOU/MOA PDF documents. This feature uses the `smalot/pdfparser` library to extract text from PDFs and applies regex patterns to identify signed dates and end/validity dates.

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB database
- Composer (for dependency management)
- Write permissions on the project directory

### Installation Steps

1. **Run the installation script:**
   ```bash
   php install_mou_features.php
   ```
   
   This script will:
   - Check PHP version compatibility
   - Install Composer dependencies (smalot/pdfparser)
   - Create the `mou_moa` database table
   - Test the installation

2. **Manual installation (if needed):**
   ```bash
   # Install Composer dependencies
   composer install
   
   # Run database migration
   mysql -u username -p database_name < sql/migration_create_mou_moa_table.sql
   ```

## Features

### Automatic Date Extraction

When a PDF is uploaded with the category "MOUs & MOAs", the system automatically:

1. **Extracts text** from the PDF using smalot/pdfparser
2. **Searches for dates** using multiple regex patterns:
   - "Signed on: May 15, 2024"
   - "Effective until: December 31, 2026" 
   - "Valid from: YYYY-MM-DD to YYYY-MM-DD"
   - "Date: May 15, 2024"
   - "Executed on: Date"
3. **Normalizes dates** to MySQL DATE format (YYYY-MM-DD)
4. **Stores results** in the `mou_moa` table
5. **Flags for review** if no dates are found

### Visual Indicators

On the MOU/MOA page, documents display visual indicators:
- **Green checkmark (✓)**: Dates successfully extracted
- **Yellow exclamation (!)**: Requires manual review
- **Highlighted rows**: Documents needing attention

### Database Schema

The `mou_moa` table stores:
```sql
- id (Primary Key)
- document_id (Foreign Key to documents table)
- signed_date (DATE)
- end_date (DATE) 
- requires_manual_review (BOOLEAN)
- extraction_notes (TEXT)
- extracted_text_snippet (TEXT)
- created_at, updated_at (TIMESTAMPS)
```

## Supported Date Patterns

The system recognizes various date formats:

### Signed Date Patterns
- "Signed on: May 15, 2024"
- "Date signed: May 15, 2024"
- "Signed: 2024-05-15"
- "Signed: 15/05/2024"
- "Date: May 15, 2024"
- "This MOU is signed on May 15, 2024"
- "Executed on: May 15, 2024"

### End Date Patterns
- "Effective until: December 31, 2026"
- "Valid until: December 31, 2026"
- "Expires on: December 31, 2026"
- "Valid from: 2024-05-15 to 2026-12-31"
- "End date: December 31, 2026"
- "Term: 3 years from signing"

### Supported Date Formats
- `YYYY-MM-DD` (2024-05-15)
- `MM/DD/YYYY` (05/15/2024)
- `DD/MM/YYYY` (15/05/2024)
- `Month DD, YYYY` (May 15, 2024)
- `DD Month YYYY` (15 May 2024)
- `Month DDth, YYYY` (May 15th, 2024)

## API Endpoints

### Get MOU Data
```http
GET /api/documents.php?action=get_mou_data&mou_action=all
GET /api/documents.php?action=get_mou_data&mou_action=by_document&document_id=123
GET /api/documents.php?action=get_mou_data&mou_action=requiring_review
GET /api/documents.php?action=get_mou_data&mou_action=statistics
```

### Update MOU Data (Manual Review)
```http
POST /api/documents.php?action=update_mou_data
Content-Type: application/x-www-form-urlencoded

document_id=123&signed_date=2024-05-15&end_date=2026-12-31&requires_manual_review=0&extraction_notes=Manually corrected dates
```

## Usage Workflow

### 1. Upload MOU/MOA Document
1. Go to the Documents page
2. Upload a PDF file
3. Select category "MOUs & MOAs" (or let auto-classification handle it)
4. The system automatically extracts dates during upload

### 2. Review Results
1. Visit the MOU/MOA page
2. Look for visual indicators:
   - Green checkmarks: Successfully extracted
   - Yellow warnings: Need manual review
3. Click "View" to see extraction details

### 3. Manual Review Process
For documents requiring review:
1. Check the extraction notes for details
2. Review the text snippet that was analyzed
3. Manually enter correct dates if needed
4. Update the review status

### 4. Monitor Statistics
The system provides statistics on:
- Total MOUs processed
- Successful extractions
- Documents requiring review
- Extraction success rate

## Troubleshooting

### Common Issues

1. **PDF text extraction fails**
   - Ensure the PDF contains searchable text (not scanned images)
   - Check if the PDF is password protected
   - Verify file permissions on uploads directory

2. **No dates found**
   - Check if the document uses non-standard date formats
   - Review the extracted text snippet
   - Add custom regex patterns if needed

3. **Composer dependencies missing**
   ```bash
   composer install
   # or
   php install_mou_features.php
   ```

4. **Database connection errors**
   - Check `config/database.php` settings
   - Verify MySQL/MariaDB is running
   - Ensure database user has proper permissions

### Error Logs
Check PHP error logs for detailed information:
```bash
tail -f /var/log/php_errors.log
```

### Testing
Run the test script to verify installation:
```bash
php test_mou_features.php
```

## Development

### Adding New Date Patterns
To add new regex patterns, edit `classes/MOUDateExtractor.php`:

```php
// In extractSignedDate() method
$patterns = [
    // Add new pattern here
    '/your_new_pattern[:\s]+([a-zA-Z]+\s+\d{1,2},?\s+\d{4})/i',
    // ... existing patterns
];
```

### Customizing Extraction Logic
The main extraction logic is in:
- `classes/MOUDateExtractor.php` - PDF parsing and date extraction
- `classes/MOUData.php` - Database operations
- `api/documents.php` - Integration with upload process

### Performance Considerations
- PDF parsing can be memory-intensive for large files
- Consider implementing background processing for large uploads
- Monitor database performance with many MOU records

## Security

- All database queries use prepared statements
- File uploads are validated and sanitized
- Extracted text is limited to prevent excessive database storage
- PDF parsing is done server-side with proper error handling

## Limitations

1. **Text-based PDFs only**: Scanned images require OCR
2. **English language**: Regex patterns are optimized for English
3. **Date format dependency**: May miss unconventional date formats
4. **Memory usage**: Large PDFs consume more memory during processing

## Future Enhancements

- OCR support for scanned documents
- Multi-language date pattern support
- Machine learning for improved date detection
- Background processing for large files
- Email notifications for review requirements

## Support

For issues or questions:
1. Check the error logs
2. Run the test script
3. Review the extraction notes in the database
4. Verify PDF text content manually

## Version History

- **v1.0**: Initial implementation with basic regex patterns
- **v1.1**: Added visual indicators and improved error handling
- **v1.2**: Enhanced date format support and normalization 
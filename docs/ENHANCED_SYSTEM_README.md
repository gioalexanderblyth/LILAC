# Enhanced Document & Event Management System

A comprehensive system for managing documents and events with automatic award assignment, content analysis, and real-time tracking.

## 🚀 Features

### 📄 Document Management
- **Automatic File Content Reading**: Extracts text from PDF, Word, Text, and Image files
- **OCR Support**: Optical Character Recognition for images
- **Award Assignment**: Automatically assigns documents to relevant awards based on content analysis
- **Manual Override**: Allows manual reassignment of documents to different awards

### 🏆 Award System
- **5 Award Types**: 
  - Internationalization (IZN) Leadership Award
  - Outstanding International Education Program Award
  - Emerging Leadership Award
  - Best Regional Office for Internationalization Award
  - Global Citizenship Award
- **Readiness Tracking**: Monitors progress towards award completion
- **Criteria Analysis**: Checks satisfaction of award criteria
- **Recommendations**: Suggests missing content for award completion

### 📅 Event Management
- **Event Creation**: Add events with date, time, location, and description
- **File Uploads**: Attach documents or images to events
- **Status Tracking**: Automatically updates event status (upcoming/completed)
- **Calendar Integration**: Events appear on both scheduler and display calendars
- **Counters**: Real-time counters for upcoming and completed events

### 📊 Dashboard & Analytics
- **Real-time Updates**: Live counters and status updates
- **Comprehensive Overview**: Summary of all system metrics
- **Award Progress**: Visual progress bars and readiness indicators
- **Event Management**: Full CRUD operations for events
- **Calendar View**: Monthly calendar with event display

## 🛠️ Technical Architecture

### Database Schema
- **enhanced_documents**: Stores document metadata and extracted content
- **enhanced_events**: Stores event information and status
- **award_types**: Configuration for award types and criteria
- **document_award_assignments**: Many-to-many relationship between documents and awards
- **event_award_assignments**: Many-to-many relationship between events and awards
- **award_readiness**: Tracks readiness status for each award
- **event_counters**: Maintains real-time event counters
- **file_processing_log**: Logs file processing operations

### File Processing
- **PDF**: Text extraction using PDF.js and pdftotext
- **Word**: Content extraction from DOCX files
- **Text**: Direct text file reading
- **Images**: OCR using Tesseract.js and tesseract command-line tool

### Award Analysis
- **Keyword Matching**: Analyzes content against award-specific keywords
- **Criteria Satisfaction**: Checks if content satisfies award criteria
- **Confidence Scoring**: Calculates confidence levels for assignments
- **Threshold Management**: Tracks minimum requirements for award readiness

## 📁 File Structure

```
├── api/
│   ├── database_schema.php          # Database table creation
│   ├── file_processor.php           # File content extraction
│   ├── award_analyzer.php           # Award assignment logic
│   ├── event_manager.php            # Event management
│   └── enhanced_management.php      # Main API endpoint
├── docs/
│   └── ENHANCED_SYSTEM_README.md    # This documentation
├── enhanced-dashboard.php           # Main dashboard interface
├── setup_enhanced_system.php        # System setup script
└── config/
    └── database.php                 # Database configuration
```

## 🚀 Installation & Setup

### 1. Run Setup Script
Visit `setup_enhanced_system.php` in your browser to initialize the system:
```
http://your-domain.com/setup_enhanced_system.php
```

### 2. Access Dashboard
After setup, access the main dashboard:
```
http://your-domain.com/enhanced-dashboard.php
```

### 3. System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Optional: Tesseract OCR for image text extraction
- Optional: pdftotext for PDF text extraction

## 📖 Usage Guide

### Document Upload
1. Click "Upload Document" button
2. Fill in document name and description
3. Select file (PDF, Word, Text, or Image)
4. System automatically extracts content and assigns to awards
5. View assignments and manually override if needed

### Event Management
1. Click "Add Event" button
2. Fill in event details (title, date, time, location, description)
3. Optionally upload a file
4. Event is automatically added to calendar and counters updated
5. System analyzes content for award assignment

### Award Tracking
1. View award progress on the Awards tab
2. See readiness percentage and satisfied criteria
3. Get recommendations for missing content
4. Monitor document and event assignments

### Calendar Integration
1. View events in calendar format
2. See upcoming and completed events
3. Events automatically update status based on date
4. Filter events by date range or status

## 🔧 API Endpoints

### Document Management
- `POST /api/enhanced_management.php?action=upload_document` - Upload document
- `GET /api/enhanced_management.php?action=get_award_status` - Get award status

### Event Management
- `POST /api/enhanced_management.php?action=create_event` - Create event
- `GET /api/enhanced_management.php?action=get_events` - Get events
- `GET /api/enhanced_management.php?action=get_upcoming_events` - Get upcoming events
- `GET /api/enhanced_management.php?action=get_completed_events` - Get completed events
- `GET /api/enhanced_management.php?action=get_calendar_events` - Get calendar events
- `GET /api/enhanced_management.php?action=get_event_counters` - Get event counters

### Award Management
- `GET /api/enhanced_management.php?action=get_award_details&award_key=X` - Get award details
- `POST /api/enhanced_management.php?action=manual_override_document` - Manual document override
- `POST /api/enhanced_management.php?action=manual_override_event` - Manual event override

## 🎯 Award Types & Criteria

### 1. Internationalization (IZN) Leadership Award
**Criteria:**
- Champion Bold Innovation
- Cultivate Global Citizens
- Nurture Lifelong Learning
- Lead with Purpose
- Ethical and Inclusive Leadership

**Threshold:** 3 documents/events minimum

### 2. Outstanding International Education Program Award
**Criteria:**
- Expand Access to Global Opportunities
- Foster Collaborative Innovation
- Embrace Inclusivity and Beyond

**Threshold:** 2 documents/events minimum

### 3. Emerging Leadership Award
**Criteria:**
- Innovation
- Strategic and Inclusive Growth
- Empowerment of Others

**Threshold:** 2 documents/events minimum

### 4. Best Regional Office for Internationalization Award
**Criteria:**
- Comprehensive Internationalization Efforts
- Cooperation and Collaboration
- Measurable Impact

**Threshold:** 2 documents/events minimum

### 5. Global Citizenship Award
**Criteria:**
- Ignite Intercultural Understanding
- Empower Changemakers
- Cultivate Active Engagement

**Threshold:** 2 documents/events minimum

## 🔄 Real-time Features

### Automatic Updates
- Event status updates based on current date
- Counter updates when events are added/removed
- Award readiness recalculation when content is added
- Real-time dashboard updates

### Synchronization
- Database and UI stay in sync
- API calls update all relevant counters
- Calendar views reflect current data
- Award progress updates immediately

## 🛡️ Security Features

### File Upload Security
- File type validation
- File size limits (50MB)
- Filename sanitization
- Secure file storage

### Data Protection
- Prepared statements for database queries
- Input sanitization
- XSS protection
- CSRF protection

## 📈 Performance Features

### Optimization
- Efficient database queries
- Caching of frequently accessed data
- Lazy loading of large datasets
- Optimized file processing

### Monitoring
- File processing logs
- Performance metrics
- Error tracking
- Usage statistics

## 🔧 Configuration

### Award Configuration
Award types and criteria can be modified in the `award_types` database table or by editing the configuration in `api/database_schema.php`.

### File Processing
File processing settings can be adjusted in `api/file_processor.php`:
- Maximum file size
- Allowed file types
- OCR settings
- Processing timeouts

### Dashboard Settings
Dashboard behavior can be customized in `enhanced-dashboard.php`:
- Auto-refresh intervals
- Display limits
- UI themes
- Notification settings

## 🐛 Troubleshooting

### Common Issues

1. **File Upload Fails**
   - Check file size limits
   - Verify file type is supported
   - Ensure uploads directory is writable

2. **OCR Not Working**
   - Install Tesseract OCR
   - Check Tesseract.js library loading
   - Verify image file formats

3. **Database Connection Issues**
   - Check database configuration
   - Verify database credentials
   - Ensure database server is running

4. **Award Assignment Not Working**
   - Check content extraction
   - Verify keyword matching
   - Review confidence thresholds

### Debug Mode
Enable debug mode by setting error reporting in PHP:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📞 Support

For technical support or feature requests:
1. Check the troubleshooting section
2. Review the API documentation
3. Examine the database schema
4. Check file permissions and server logs

## 🔄 Updates & Maintenance

### Regular Maintenance
- Update event statuses (automatic)
- Clean up old processing logs
- Optimize database performance
- Update file processing libraries

### System Updates
- Backup database before updates
- Test in development environment
- Update configuration files
- Verify all features work correctly

## 📊 System Metrics

The system tracks various metrics:
- Document processing success rates
- Event creation and completion rates
- Award readiness progress
- File processing performance
- User activity patterns

## 🎉 Conclusion

The Enhanced Document & Event Management System provides a comprehensive solution for managing documents and events with automatic award assignment and real-time tracking. The system is designed to be scalable, secure, and user-friendly while providing powerful analytics and reporting capabilities.

For more information or support, please refer to the individual component documentation or contact the development team.

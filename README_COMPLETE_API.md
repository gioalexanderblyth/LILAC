# LILAC System - Complete PHP Backend API Documentation

This document provides complete API documentation for the LILAC System PHP backend and guidance for converting all modules from localStorage to the database backend.

## 🗂️ Complete File Structure

```
LILAC/
├── config/
│   └── database.php              # Database configuration
├── classes/
│   ├── Budget.php               # Budget management
│   ├── Transaction.php          # Financial transactions
│   ├── Document.php             # Document management
│   ├── Meeting.php              # Meeting scheduling
│   ├── Template.php             # Template management
│   ├── Award.php                # Award tracking
│   ├── MOU.php                  # MOU/MOA management
│   ├── RegistrarFile.php        # Registrar file management
│   └── BudgetRequest.php        # Budget request workflow
├── api/
│   ├── funds.php               # Financial API (✅ Implemented)
│   ├── documents.php           # Document API
│   ├── meetings.php            # Meeting API
│   ├── templates.php           # Template API
│   ├── awards.php              # Award API
│   ├── mous.php                # MOU/MOA API
│   ├── registrar_files.php     # Registrar files API
│   ├── budget_requests.php     # Budget request API
│   └── dashboard.php           # Unified dashboard API
├── sql/
│   └── schema.sql              # Complete database schema
├── install.php                 # Installation wizard
└── HTML files to convert:
    ├── funds.html              # ✅ Converted to PHP backend
    ├── documents.html          # ⏳ Needs conversion
    ├── meetings.html           # ⏳ Needs conversion
    ├── templates.html          # ⏳ Needs conversion
    ├── awards.html             # ⏳ Needs conversion
    ├── mou-moa.html           # ⏳ Needs conversion
    ├── registrar_files.html    # ⏳ Needs conversion
    └── dashboard.html          # ⏳ Needs conversion
```

## 📊 Database Schema Overview

### Tables Created:
1. **`budgets`** - Budget management
2. **`transactions`** - Financial transactions (income/expense)
3. **`documents`** - Document storage and metadata
4. **`meetings`** - Meeting scheduling and tracking
5. **`templates`** - Template management
6. **`awards`** - Award and recognition tracking
7. **`mous`** - MOU/MOA partnership management
8. **`registrar_files`** - Registrar file management
9. **`budget_requests`** - Budget request workflow

## 🔗 API Endpoints Reference

### 1. **Funds API** (`api/funds.php`) ✅ **COMPLETE**
**Used by:** `funds.html`

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_budget` | GET | - | Get current budget |
| `update_budget` | POST | `amount` | Update budget amount |
| `get_transactions` | GET | - | Get all transactions |
| `add_transaction` | POST | `description`, `amount`, `type`, `date` | Add new transaction |
| `delete_transaction` | POST | `id` | Delete transaction |
| `get_remaining_budget` | GET | - | Calculate remaining budget |

### 2. **Documents API** (`api/documents.php`)
**Used by:** `documents.html`

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_all` | GET | - | Get all documents |
| `add` | POST | `title`, `type`, `description`, `file_name`, `file_size` | Add document |
| `delete` | POST | `id` | Delete document |
| `get_by_id` | GET | `id` | Get document by ID |
| `update` | POST | `id`, `title`, `type`, `description` | Update document |
| `get_stats` | GET | - | Get document statistics |

### 3. **Meetings API** (`api/meetings.php`)
**Used by:** `meetings.html`, `dashboard.html`

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_all` | GET | - | Get all meetings |
| `add` | POST | `title`, `date`, `time`, `location`, `description` | Add meeting |
| `delete` | POST | `id` | Delete meeting |
| `update_status` | POST | `id`, `status` | Update meeting status |
| `get_upcoming` | GET | `limit` (optional) | Get upcoming meetings |
| `get_by_date_range` | GET | `start_date`, `end_date` | Get meetings in date range |

### 4. **Templates API** (`api/templates.php`)
**Used by:** `templates.html`

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_all` | GET | - | Get all templates |
| `add` | POST | `name`, `type`, `description`, `content`, `file_name`, `file_size` | Add template |
| `delete` | POST | `id` | Delete template |
| `get_by_id` | GET | `id` | Get template by ID |
| `update` | POST | `id`, `name`, `type`, `description`, `content` | Update template |
| `get_by_type` | GET | `type` | Get templates by type |

### 5. **Awards API** (`api/awards.php`)
**Used by:** `awards.html`, `dashboard.html`

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_all` | GET | - | Get all awards |
| `add` | POST | `title`, `category`, `date_received`, `description`, `recipient`, `organization` | Add award |
| `delete` | POST | `id` | Delete award |
| `get_by_id` | GET | `id` | Get award by ID |
| `update` | POST | `id`, `title`, `category`, `date_received`, `description`, `recipient`, `organization` | Update award |
| `get_recent` | GET | `limit` (optional) | Get recent awards |

### 6. **MOUs API** (`api/mous.php`)
**Used by:** `mou-moa.html`, `dashboard.html`

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_all` | GET | - | Get all MOUs |
| `add` | POST | `partner_name`, `status`, `date_signed`, `end_date`, `description`, `type` | Add MOU |
| `delete` | POST | `id` | Delete MOU |
| `get_by_id` | GET | `id` | Get MOU by ID |
| `update` | POST | `id`, `partner_name`, `status`, `date_signed`, `end_date`, `description`, `type` | Update MOU |
| `get_active` | GET | - | Get active MOUs |
| `get_expiring` | GET | `days` (optional) | Get expiring MOUs |
| `update_expired` | POST | - | Update expired MOUs |

### 7. **Registrar Files API** (`api/registrar_files.php`)
**Used by:** `registrar_files.html`

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_all` | GET | - | Get all files |
| `add` | POST | `title`, `file_name`, `file_size`, `category`, `description` | Add file |
| `delete` | POST | `id` | Delete file |
| `get_by_id` | GET | `id` | Get file by ID |
| `update` | POST | `id`, `title`, `category`, `description` | Update file |
| `update_status` | POST | `id`, `status` | Update file status |
| `get_by_category` | GET | `category` | Get files by category |
| `search` | GET | `search` | Search files |

### 8. **Budget Requests API** (`api/budget_requests.php`)
**Used by:** `dashboard.html` (budget request modal)

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_all` | GET | - | Get all requests |
| `add` | POST | `title`, `amount`, `category`, `date_needed`, `justification` | Add request |
| `update_status` | POST | `id`, `status`, `reviewer_notes` | Update request status |
| `delete` | POST | `id` | Delete request |
| `get_by_id` | GET | `id` | Get request by ID |
| `get_pending` | GET | - | Get pending requests |
| `get_stats` | GET | - | Get request statistics |

### 9. **Dashboard API** (`api/dashboard.php`)
**Used by:** `dashboard.html`

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_overview` | GET | - | Get complete dashboard overview |
| `get_recent_documents` | GET | - | Get recent documents |
| `get_upcoming_meetings` | GET | `limit` (optional) | Get upcoming meetings |
| `get_active_mous` | GET | - | Get active MOUs |
| `get_recent_awards` | GET | `limit` (optional) | Get recent awards |
| `get_budget_overview` | GET | - | Get budget overview |
| `get_expiration_reminders` | GET | - | Get expiration reminders |
| `get_stats_summary` | GET | - | Get statistics summary |

## 🔄 Migration Guide: Converting HTML Files

### **Step 1: Update JavaScript Functions**

For each HTML file, replace localStorage operations with API calls:

#### **Before (localStorage):**
```javascript
// OLD: localStorage approach
const documents = JSON.parse(localStorage.getItem('lilacDocuments') || '[]');
localStorage.setItem('lilacDocuments', JSON.stringify(documents));
```

#### **After (API calls):**
```javascript
// NEW: API approach
function loadDocuments() {
    fetch('api/documents.php?action=get_all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderDocuments(data.documents);
            }
        })
        .catch(error => console.error('Error:', error));
}

function addDocument(title, type, description, fileName, fileSize) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('title', title);
    formData.append('type', type);
    formData.append('description', description);
    formData.append('file_name', fileName);
    formData.append('file_size', fileSize);

    fetch('api/documents.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadDocuments(); // Refresh the list
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}
```

### **Step 2: Update Data Structure Mapping**

| localStorage Key | API Endpoint | Primary Changes |
|------------------|--------------|-----------------|
| `lilacDocuments` | `api/documents.php` | Add `id` field from database |
| `lilacMeetings` | `api/meetings.php` | Split `date` and `time`, add `status` |
| `lilacTemplates` | `api/templates.php` | Add `created_at`, separate file metadata |
| `lilacAwards` | `api/awards.php` | Add `recipient`, `organization` fields |
| `lilacMOUs` | `api/mous.php` | Add `type` (MOU/MOA), proper date handling |
| `lilacRegistrarFiles` | `api/registrar_files.php` | Add `status`, `category` fields |
| `lilacBudgetRequests` | `api/budget_requests.php` | Add workflow fields (`status`, `reviewed_at`) |

### **Step 3: Update Form Submissions**

Replace form handling to use FormData and fetch API:

```javascript
// Example: Document form submission
function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('title', document.getElementById('doc-title').value);
    formData.append('type', document.getElementById('doc-type').value);
    formData.append('description', document.getElementById('doc-description').value);
    formData.append('file_name', document.getElementById('doc-file').files[0].name);
    formData.append('file_size', document.getElementById('doc-file').files[0].size);

    fetch('api/documents.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            e.target.reset();
            loadDocuments();
            showNotification('Document added successfully!', 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding document', 'error');
    });
}
```

## 🛠️ Testing Your Conversion

### **1. Test API Endpoints**
```bash
# Test getting all documents
curl "http://localhost:8000/api/documents.php?action=get_all"

# Test adding a document
curl -X POST "http://localhost:8000/api/documents.php" \
     -F "action=add" \
     -F "title=Test Document" \
     -F "type=Report" \
     -F "description=Test description" \
     -F "file_name=test.pdf" \
     -F "file_size=1024"
```

### **2. Browser Console Testing**
```javascript
// Test in browser console
fetch('api/documents.php?action=get_all')
    .then(response => response.json())
    .then(data => console.log(data));
```

## 📋 Conversion Checklist

### **For each HTML file:**

- [ ] **Replace localStorage.getItem()** with fetch() GET requests
- [ ] **Replace localStorage.setItem()** with fetch() POST requests  
- [ ] **Update data structure** to match database schema
- [ ] **Add error handling** for API failures
- [ ] **Update delete functions** to use IDs instead of array indices
- [ ] **Test all CRUD operations** (Create, Read, Update, Delete)
- [ ] **Update any inter-page communication** to use APIs instead of localStorage events

### **Priority Order for Conversion:**
1. ✅ **funds.html** (COMPLETED)
2. 🔄 **documents.html** (High priority - frequently used)
3. 🔄 **meetings.html** (High priority - time-sensitive data)
4. 🔄 **mou-moa.html** (Medium priority - important for tracking)
5. 🔄 **awards.html** (Medium priority - historical data)
6. 🔄 **templates.html** (Medium priority - document generation)
7. 🔄 **registrar_files.html** (Medium priority - file management)
8. 🔄 **dashboard.html** (Last - depends on all other modules)

## 🚀 Quick Start

1. **Install the system:**
   ```
   http://localhost:8000/install.php
   ```

2. **Test the funds page (already converted):**
   ```
   http://localhost:8000/funds.html
   ```

3. **Convert next module** using the patterns shown above

4. **Verify data persistence** by refreshing pages and checking data remains

## 🔒 Security Considerations

- All APIs use prepared statements to prevent SQL injection
- Input validation on all endpoints
- CORS headers configured for development
- File upload validation (when implemented)
- SQL schema uses appropriate data types and constraints

## 📞 API Response Format

All APIs return consistent JSON responses:

```json
{
    "success": true|false,
    "message": "Human readable message",
    "data": {...},          // For successful requests
    "error": "Error details" // For failed requests
}
```

## 🎯 Next Steps

1. **Convert documents.html** - Use `api/documents.php`
2. **Convert meetings.html** - Use `api/meetings.php`  
3. **Convert templates.html** - Use `api/templates.php`
4. **Continue with remaining modules**
5. **Finally convert dashboard.html** - Use `api/dashboard.php`

Each converted module will provide persistent, multi-user capable data storage with professional backend architecture! 
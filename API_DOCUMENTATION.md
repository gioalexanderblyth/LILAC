# LILAC System - Complete Backend API Guide

## 🎯 What's Been Created

You now have a **complete PHP backend system** that replaces localStorage with a robust MySQL database. Here's what we've built:

### ✅ **Completed Modules:**
1. **Funds System** (`funds.html` + `api/funds.php`) - Fully converted
2. **Complete Backend Infrastructure** - All APIs and database tables

### 📁 **Backend Architecture:**

```
LILAC/
├── classes/           # PHP Data Models
│   ├── Budget.php     ├── Document.php    ├── Meeting.php
│   ├── Transaction.php├── Template.php    ├── Award.php
│   ├── MOU.php        ├── RegistrarFile.php├── BudgetRequest.php
├── api/              # REST API Endpoints  
│   ├── funds.php      ├── documents.php   ├── meetings.php
│   ├── templates.php  ├── awards.php      ├── mous.php
│   ├── registrar_files.php ├── budget_requests.php ├── dashboard.php
├── config/
│   └── database.php   # Database connection
├── sql/
│   └── schema.sql     # Complete database schema (9 tables)
└── install.php        # Automated installer
```

## 🗄️ **Database Tables Created:**

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `budgets` | Budget management | `amount`, `created_at` |
| `transactions` | Income/Expense tracking | `description`, `amount`, `type`, `transaction_date` |
| `documents` | Document storage | `title`, `type`, `file_name`, `file_size` |
| `meetings` | Meeting scheduling | `title`, `meeting_date`, `meeting_time`, `status` |
| `templates` | Template management | `name`, `type`, `content`, `file_name` |
| `awards` | Award tracking | `title`, `category`, `date_received`, `recipient` |
| `mous` | MOU/MOA management | `partner_name`, `status`, `date_signed`, `end_date` |
| `registrar_files` | Registrar file management | `title`, `file_name`, `category`, `status` |
| `budget_requests` | Budget request workflow | `title`, `amount`, `status`, `justification` |

## 🔌 **API Endpoints Summary:**

### **Funds API** (✅ Already Implemented)
- `GET/POST api/funds.php` - Budget & transaction management
- **Actions:** `get_budget`, `update_budget`, `get_transactions`, `add_transaction`, `delete_transaction`, `get_remaining_budget`

### **Documents API**
- `GET/POST api/documents.php` - Document management  
- **Actions:** `get_all`, `add`, `delete`, `get_by_id`, `update`, `get_stats`

### **Meetings API** 
- `GET/POST api/meetings.php` - Meeting scheduling
- **Actions:** `get_all`, `add`, `delete`, `update_status`, `get_upcoming`, `get_by_date_range`

### **Templates API**
- `GET/POST api/templates.php` - Template management
- **Actions:** `get_all`, `add`, `delete`, `get_by_id`, `update`, `get_by_type`

### **Awards API**
- `GET/POST api/awards.php` - Award tracking
- **Actions:** `get_all`, `add`, `delete`, `get_by_id`, `update`, `get_recent`

### **MOUs API**
- `GET/POST api/mous.php` - MOU/MOA management
- **Actions:** `get_all`, `add`, `delete`, `get_by_id`, `update`, `get_active`, `get_expiring`, `update_expired`

### **Registrar Files API**
- `GET/POST api/registrar_files.php` - File management
- **Actions:** `get_all`, `add`, `delete`, `get_by_id`, `update`, `update_status`, `get_by_category`, `search`

### **Budget Requests API**
- `GET/POST api/budget_requests.php` - Budget request workflow
- **Actions:** `get_all`, `add`, `update_status`, `delete`, `get_by_id`, `get_pending`, `get_stats`

### **Dashboard API**
- `GET/POST api/dashboard.php` - Unified dashboard data
- **Actions:** `get_overview`, `get_recent_documents`, `get_upcoming_meetings`, `get_budget_overview`, etc.

## 🔄 **Files That Need Conversion:**

| File | localStorage Key | API Endpoint | Status |
|------|------------------|--------------|---------|
| `funds.html` | `lilacBudget`, `lilacTransactions` | `api/funds.php` | ✅ **DONE** |
| `documents.html` | `lilacDocuments` | `api/documents.php` | ⏳ Ready to convert |
| `meetings.html` | `lilacMeetings` | `api/meetings.php` | ⏳ Ready to convert |
| `templates.html` | `lilacTemplates` | `api/templates.php` | ⏳ Ready to convert |
| `awards.html` | `lilacAwards` | `api/awards.php` | ⏳ Ready to convert |
| `mou-moa.html` | `lilacMOUs` | `api/mous.php` | ⏳ Ready to convert |
| `registrar_files.html` | `lilacRegistrarFiles` | `api/registrar_files.php` | ⏳ Ready to convert |
| `dashboard.html` | Multiple keys | `api/dashboard.php` | ⏳ Ready to convert |

## 📋 **How to Convert Each File:**

### **Pattern to Follow (using funds.html as example):**

#### **1. Replace localStorage reads:**
```javascript
// OLD
const data = JSON.parse(localStorage.getItem('lilacKey') || '[]');

// NEW  
function loadData() {
    fetch('api/endpoint.php?action=get_all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderData(data.items);
            }
        })
        .catch(error => console.error('Error:', error));
}
```

#### **2. Replace localStorage writes:**
```javascript
// OLD
localStorage.setItem('lilacKey', JSON.stringify(data));

// NEW
function addItem(itemData) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('field1', itemData.field1);
    formData.append('field2', itemData.field2);

    fetch('api/endpoint.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadData(); // Refresh
        } else {
            alert('Error: ' + data.message);
        }
    });
}
```

#### **3. Update delete functions:**
```javascript
// OLD - used array index
function deleteItem(index) {
    data.splice(index, 1);
    localStorage.setItem('lilacKey', JSON.stringify(data));
}

// NEW - use database ID
function deleteItem(id) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    fetch('api/endpoint.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadData(); // Refresh
        }
    });
}
```

#### **4. Update rendering to use database IDs:**
```javascript
// OLD
item.forEach((item, index) => {
    html += `<button onclick="deleteItem(${index})">Delete</button>`;
});

// NEW
items.forEach((item) => {
    html += `<button onclick="deleteItem(${item.id})">Delete</button>`;
});
```

## 🚀 **Quick Start:**

### **1. Install the System:**
```bash
cd LILAC
php -S localhost:8000
```
Then visit: `http://localhost:8000/install.php`

### **2. Test Current Implementation:**
Visit: `http://localhost:8000/funds.html` 
- Add/edit budget amounts
- Add income/expense transactions  
- Delete transactions
- Verify data persists on page refresh

### **3. Convert Next Module:**
I recommend starting with `documents.html` as it's commonly used:

1. Replace localStorage calls with `api/documents.php` calls
2. Update the `renderDocuments()` function to use `item.id` instead of array index  
3. Test all CRUD operations work
4. Verify data persistence

## 🎯 **Benefits of This Backend:**

- ✅ **Persistent Data** - No more lost data on browser cache clear
- ✅ **Multi-User Support** - Multiple people can access same data
- ✅ **Professional Architecture** - Proper separation of concerns
- ✅ **Scalable** - Can handle growth in data and users
- ✅ **Secure** - SQL injection protection, input validation
- ✅ **Consistent** - Unified API patterns across all modules
- ✅ **Future-Ready** - Easy to add features like user authentication, file uploads, etc.

## 📞 **Need Help Converting?**

Each API follows the same pattern. Here's what to remember:

1. **GET requests** for loading data (append `?action=get_all`)
2. **POST requests** for modifications (use FormData with `action` parameter)  
3. **Always check `data.success`** in the response
4. **Use database `id` field** instead of array indices
5. **Add error handling** for network/server issues

The hard work is done - you now have a complete, professional backend! Each file conversion should take 15-30 minutes following the established patterns.

Would you like me to convert the next module for you, or do you want to try converting one yourself using this guide? 
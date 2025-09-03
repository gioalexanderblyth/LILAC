# LILAC Mobile Subdomain Setup

## 📱 **Separate Mobile & Desktop Versions Created**

Your LILAC system now has **two completely separate designs**:

### **🖥️ Desktop Version (Original)**
- **Location**: Main directory files (`dashboard.html`, `documents.html`, etc.)
- **Design**: Full sidebar navigation, desktop-optimized layouts
- **Best for**: PC, laptop, and tablet users
- **URL**: `http://localhost/LILAC/dashboard.html`

### **📱 Mobile Version (New)**
- **Location**: `/mobile/` directory
- **Design**: Bottom navigation, touch-optimized, card-based layout
- **Best for**: Smartphones and mobile devices
- **URL**: `http://localhost/LILAC/mobile/dashboard.html`

---

## 🚀 **How It Works**

### **Automatic Detection**
The `index.html` file automatically detects device type:
- **Mobile devices** → Redirected to `/mobile/dashboard.html`
- **Desktop/tablets** → Redirected to `/dashboard.html`

### **Manual Access**
Users can manually choose their preferred version:
- Desktop: `http://localhost/LILAC/dashboard.html`
- Mobile: `http://localhost/LILAC/mobile/dashboard.html`

---

## 📱 **Mobile Features**

### **Design Differences**
- **Navigation**: Bottom tab bar (Home, Docs, Meet, Funds, More)
- **Layout**: Single-column, card-based design
- **Touch-friendly**: Large buttons, simplified forms
- **Mobile-first**: Optimized for small screens

### **Mobile Pages Created**
- ✅ `mobile/dashboard.html` - Home screen with quick actions
- ✅ `mobile/documents.html` - File upload and management
- ✅ `mobile/meetings.html` - Meeting scheduling
- ✅ `mobile/funds.html` - Budget tracking
- ✅ `mobile/menu.html` - Access to all features

### **Key Mobile Features**
- Quick upload with drag-and-drop
- Touch-friendly meeting scheduling
- Simplified budget tracking
- Bottom navigation for easy thumb access
- Links to access desktop version

---

## 🔄 **Switching Between Versions**

### **From Mobile to Desktop**
- Tap "Desktop" button in mobile header
- Or manually navigate to main directory

### **From Desktop to Mobile**
- Manually navigate to `/mobile/` directory
- Or use responsive detection on `index.html`

---

## 🛠 **Technical Setup**

### **Directory Structure**
```
LILAC/
├── index.html (device detection)
├── dashboard.html (desktop)
├── documents.html (desktop)
├── ... (other desktop files)
└── mobile/
    ├── dashboard.html (mobile)
    ├── documents.html (mobile)
    ├── meetings.html (mobile)
    ├── funds.html (mobile)
    └── menu.html (mobile)
```

### **API Compatibility**
- Both versions use the same `/api/` endpoints
- No backend changes required
- Shared database and functionality

---

## 📋 **Testing Both Versions**

### **Desktop Testing**
1. Open `http://localhost/LILAC/dashboard.html`
2. Should show sidebar navigation
3. Full desktop layout

### **Mobile Testing**
1. Open `http://localhost/LILAC/mobile/dashboard.html`
2. Should show bottom navigation
3. Touch-optimized layout

### **Auto-Detection Testing**
1. Open `http://localhost/LILAC/index.html`
2. Should redirect based on device type
3. Manual selection available after 3 seconds

---

## 🔄 **Backup Information**

### **Backup Created**
- **Location**: `/backup-before-mobile-subdomain/`
- **Contains**: Original HTML files before mobile implementation
- **Purpose**: Easy restoration if needed

### **Restoring Original**
If you want to revert to the original single-version system:
```bash
copy backup-before-mobile-subdomain\*.html .
```

---

## 🎯 **Next Steps**

1. **Test both versions** in different devices
2. **Customize mobile colors/branding** if needed
3. **Add more mobile-specific features** as required
4. **Consider PWA features** for app-like experience

---

## 🔧 **For Future Development**

### **Adding New Mobile Pages**
1. Create in `/mobile/` directory
2. Follow existing mobile design patterns
3. Include bottom navigation
4. Add link from `menu.html`

### **Subdomain Setup (Optional)**
To set up `m.yourdomain.com`:
1. Point subdomain to `/mobile/` directory
2. Update links between versions
3. Configure server redirects

---

**Both desktop and mobile versions are now ready to use! 🎉** 
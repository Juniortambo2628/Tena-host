# Settings Page - Quick User Guide

**Access:** Click "Settings" in the sidebar  
**URL:** `http://localhost/Tena/admin/settings.php`

---

## 🎯 Quick Overview

The Settings page lets you customize your dashboard experience and manage your account security.

---

## 📋 What You Can Do

### 1. Update Your Account 👤
```
✓ Change your username
✓ Update email address  
✓ View your role
```

### 2. Change Your Password 🔒
```
✓ Update password securely
✓ See password strength (Weak/Fair/Good/Strong)
✓ Toggle password visibility (eye icon)
✓ Real-time password matching
```

### 3. Customize Dashboard ⚙️
```
✓ Items per page (10/25/50/100)
✓ Default sort order
✓ Your timezone
✓ Date format preference
```

### 4. Manage Notifications 🔔
```
✓ Email notifications
✓ Dashboard notifications
✓ Export completion alerts
✓ Weekly summary reports
```

### 5. Export Preferences 📄
```
✓ Default format (CSV/PDF/Excel)
✓ Include headers
✓ Timestamp filenames
✓ Auto-apply filters
```

### 6. Display Options 🎨
```
✓ Theme (Light/Dark/Auto)
✓ Font size (Small/Normal/Large)
✓ Compact view
✓ Enable/disable animations
```

### 7. Security Features 🛡️
```
✓ View session info
✓ Remember me option
✓ Two-factor auth (coming soon)
✓ Logout from all devices
```

### 8. Advanced Tools 🔧
```
✓ Clear cache
✓ Download your data (GDPR)
✓ Manage API keys
```

---

## 🚀 Quick Start

### Change Your Password

1. Go to **Settings** (sidebar)
2. Find **"Change Password"** card (top right)
3. Enter your current password
4. Enter new password (min 8 characters)
5. Confirm new password
6. Click **"Update Password"**
7. ✅ Done! You'll see a success message

**Tip:** Watch the strength indicator turn green for strong passwords!

---

### Customize Dashboard Defaults

1. Find **"Dashboard Preferences"** card
2. Set your preferred items per page
3. Choose default sort order
4. Select your timezone
5. Pick date format
6. Click **"Save Preferences"**
7. ✅ Settings persist across sessions!

---

### Switch to Dark Mode

1. Find **"Display Preferences"** card
2. Change Theme to **"Dark Mode"**
3. Click **"Save Display Settings"**
4. ✅ Dashboard instantly switches to dark theme!

---

## 💡 Pro Tips

### Password Strength
For a strong password, include:
- ✅ At least 8 characters
- ✅ Uppercase AND lowercase letters
- ✅ Numbers
- ✅ Special characters (!@#$...)

Example: `MyPass123!` = Strong ✅

---

### Best Settings for Performance

```
Items per page: 25 (balanced)
Sort order: Newest First (most relevant)
Theme: Auto (follows system)
Animations: ON (smooth experience)
```

---

### Export Settings Recommendation

```
Format: CSV (works in Excel/Google Sheets)
Include Headers: ON (easier to understand)
Timestamp: ON (organize multiple exports)
Filters: Use current (targeted exports)
```

---

## 🎨 Theme Preview

### Light Mode (Default)
- White backgrounds
- Dark text
- Tena gold accents
- High contrast

### Dark Mode
- Dark backgrounds
- Light text
- Softened colors
- Reduced eye strain

### Auto Mode
- Follows your system settings
- Switches automatically
- Best of both worlds

---

## 🔔 Notification Types

### Email Notifications
New registrations sent to your email

### Dashboard Notifications
Banners and badges in dashboard

### Export Notifications
Alerts when scheduled exports finish

### Weekly Reports
Summary of registrations and activity

**Recommendation:** Enable all for full awareness!

---

## 🛡️ Security Best Practices

### Do's ✅
- ✅ Use strong passwords
- ✅ Update password regularly
- ✅ Logout from shared devices
- ✅ Enable 2FA when available
- ✅ Download your data periodically

### Don'ts ❌
- ❌ Share your password
- ❌ Use simple passwords
- ❌ Stay logged in on public computers
- ❌ Ignore security alerts

---

## 🔧 Troubleshooting

### Password Won't Update?
**Check:**
- Current password is correct
- New password is at least 8 characters
- Passwords match
- No special characters in username

### Settings Not Saving?
**Check:**
- Browser allows localStorage
- Not in private/incognito mode
- JavaScript is enabled
- No browser extensions blocking

### Theme Not Applying?
**Try:**
- Hard refresh (Ctrl+F5)
- Clear browser cache
- Check display preferences saved
- Verify JavaScript console

---

## ⌨️ Keyboard Shortcuts

```
Tab       = Navigate between fields
Enter     = Submit active form
Esc       = Close alerts
Ctrl+S    = Save form (if focused)
```

---

## 📱 Mobile Experience

All settings work great on mobile:
- ✅ Responsive layout
- ✅ Touch-friendly buttons
- ✅ Readable text
- ✅ Easy form filling
- ✅ Smooth scrolling

---

## 🎯 Common Tasks

### I want to...

**Change my password:**
→ Go to "Change Password" card, fill form, click Update Password

**Get fewer items per page:**
→ Dashboard Preferences → Items Per Page → Select 10

**Enable dark mode:**
→ Display Preferences → Theme → Dark Mode → Save

**Stop getting emails:**
→ Notification Preferences → Uncheck Email Notifications → Save

**Export as PDF by default:**
→ Export Settings → Default Format → PDF → Save

**See system info:**
→ Scroll to bottom (admin only) → System Information card

---

## 📊 Settings Summary

| Category | Settings Count | Storage |
|----------|---------------|---------|
| Account | 3 fields | Database |
| Password | 3 fields | Database (hashed) |
| Dashboard | 4 options | localStorage |
| Notifications | 4 toggles | Database |
| Export | 4 options | localStorage |
| Display | 4 options | localStorage |
| Security | 2 options | Database + localStorage |
| Advanced | 3 actions | Various |

**Total:** 23+ customizable settings!

---

## 🆘 Need Help?

### Can't Access Settings?
- Make sure you're logged in
- Check you have proper permissions
- Try the direct URL: `/admin/settings.php`

### Forgot Password?
- Contact administrator for reset
- Or use password recovery (if implemented)

### Settings Not Working?
- Check JavaScript console for errors
- Verify browser compatibility
- Clear cache and try again
- Contact technical support

---

## ✅ Checklist

Quick checklist when first visiting settings:

- [ ] Update your email address
- [ ] Set a strong password
- [ ] Choose your timezone
- [ ] Set preferred items per page
- [ ] Configure notifications
- [ ] Choose your theme preference
- [ ] Save all changes!

---

## 🎉 You're All Set!

Your settings page has everything you need to:
- ✅ Manage your account
- ✅ Secure your login
- ✅ Customize your experience
- ✅ Control notifications
- ✅ Configure exports
- ✅ Personalize display

**Enjoy your personalized dashboard!** ✨

---

**Full Documentation:** `SETTINGS_PAGE_DOCUMENTATION.md`  
**Last Updated:** October 1, 2025


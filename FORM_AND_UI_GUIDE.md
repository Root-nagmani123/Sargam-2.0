# 📋 Employee ID Card Request - Form & UI Guide

## Form Structure Overview

### INDEX PAGE (List View)
```
┌─────────────────────────────────────────────────────────┐
│  Request Employee ID Card                               │
│  Manage employee ID card requests                        │
│                          [Generate New ID Card] Button   │
├─────────────────────────────────────────────────────────┤
│ [ACTIVE] [ARCHIVE]                                       │
├─────────────────────────────────────────────────────────┤
│ S.No | Date | Name | Designation | Status | Actions     │
├─────────────────────────────────────────────────────────┤
│  1   | Date | John | Officer | Pending | [View] [Edit]  │
│  2   | Date | Jane | Manager | Approved| [View] [Edit]  │
│  3   | Date | Mike | Staff | Issued | [View] [Edit]     │
├─────────────────────────────────────────────────────────┤
│ Pagination: 1 2 3 4 5 ... Next                           │
└─────────────────────────────────────────────────────────┘
```

### CREATE PAGE (Form)

```
┌─────────────────────────────────────────────────────────┐
│  Generate New ID Card                                    │
│  Please add the Request For Employee ID Card             │
├─────────────────────────────────────────────────────────┤
│
│  SECTION 1: Employee Type
│  ┌────────────────────────────────────────────────┐
│  │ ○ Permanent Employee                           │
│  │ ○ Contractual Employee                         │
│  └────────────────────────────────────────────────┘
│
│  SECTION 2: Request Details
│  ┌────────────────────────────────────────────────┐
│  │ Card Type*      Sub Type*      Request For*    │
│  │ [LBSNAA    ▼]  [Gazetted  ▼]   [Own ID    ▼]  │
│  └────────────────────────────────────────────────┘
│
│  SECTION 3: Personal Information
│  ┌────────────────────────────────────────────────┐
│  │ Name*                 Designation              │
│  │ [Sargam Admin......] [Administrative Officer..│
│  │ Date of Birth        Father Name               │
│  │ [DD/MM/YYYY]        [.................]        │
│  │ Academy Joining      Blood Group*              │
│  │ [DD/MM/YYYY]        [O+ ▼]                    │
│  └────────────────────────────────────────────────┘
│
│  SECTION 4: Contact & ID Information
│  ┌────────────────────────────────────────────────┐
│  │ Mobile         Telephone       Section          │
│  │ [9356753250]  [9356753250]     [...........]   │
│  │ ID Card Valid  Approval Auth   Vendor Name      │
│  │ [01/01/2027]  [..........]     [...........]   │
│  └────────────────────────────────────────────────┘
│
│  SECTION 5: Document Upload
│  ┌────────────────────────────────────────────────┐
│  │ Upload Photo           Upload Documents         │
│  │ ┌──────────────────┐ ┌──────────────────┐      │
│  │ │ 📸 Click or drag │ │ 📄 Click or drag │      │
│  │ │    Drop photo    │ │    Drop files    │      │
│  │ │ PNG, JPG max 2MB │ │ PDF, DOC max 5MB │      │
│  │ └──────────────────┘ └──────────────────┘      │
│  └────────────────────────────────────────────────┘
│
│  SECTION 6: Remarks
│  ┌────────────────────────────────────────────────┐
│  │ [Add any additional remarks.....................│
│  └────────────────────────────────────────────────┘
│
│  ℹ️ Required Fields: All marked fields are mandatory
│
│  [Cancel] [Save]
│
└─────────────────────────────────────────────────────────┘
```

### SHOW PAGE (Details View)

```
┌────────────────────────────────────────────────────────────┐
│ Employee ID Card Request Details                            │
│ Request ID: #1 | Created: 30 Jan, 2026           Status: ● │
├────────────────────────────────────────────────────────────┤
│
│ ┌─ LEFT COLUMN (8 cols) ─────────┐ ┌─ RIGHT SIDEBAR (4 cols) ┐
│ │                                │ │                         │
│ │ EMPLOYEE TYPE                  │ │ ATTACHED DOCUMENTS      │
│ │ ┌──────────────────────────┐   │ │ ┌──────────────────┐    │
│ │ │ Type: Permanent Employee │   │ │ │ 📸 Photo         │    │
│ │ │ Card: LBSNAA             │   │ │ │ [View/Download]  │    │
│ │ │ Sub Type: Gazetted       │   │ │ ├──────────────────┤    │
│ │ └──────────────────────────┘   │ │ │ 📄 Documents     │    │
│ │                                │ │ │ [Download]       │    │
│ │ PERSONAL INFORMATION           │ │ └──────────────────┘    │
│ │ ┌──────────────────────────┐   │ │                         │
│ │ │ Name: John Smith         │   │ │ QUICK INFO              │
│ │ │ Designation: Officer     │   │ │ Created: Admin          │
│ │ │ DOB: 18 Oct, 1983        │   │ │ Updated: 2 hours ago    │
│ │ │ Father: XYZ              │   │ │ Status: ● Pending       │
│ │ │ Joining: 05 Sep, 2013    │   │ │                         │
│ │ │ Blood: O+                │   │ │ ACTIONS                 │
│ │ └──────────────────────────┘   │ │ [Edit Request]          │
│ │                                │ │ [Back to List]          │
│ │ CONTACT INFORMATION            │ │ [Delete Request]        │
│ │ ┌──────────────────────────┐   │ │                         │
│ │ │ Mobile: 9356753250       │   │ │                         │
│ │ │ Phone: 9356753250        │   │ │                         │
│ │ │ Section: Admin           │   │ │                         │
│ │ │ Valid Upto: 01/01/2027   │   │ │                         │
│ │ └──────────────────────────┘   │ │                         │
│ │                                │ │                         │
│ │ ADDITIONAL DETAILS             │ │                         │
│ │ ┌──────────────────────────┐   │ │                         │
│ │ │ Authority: XYZ Officer   │   │ │                         │
│ │ │ Vendor: LBSNAA           │   │ │                         │
│ │ │ Request: Own ID Card     │   │ │                         │
│ │ │ Date: 30 Jan, 2026       │   │ │                         │
│ │ └──────────────────────────┘   │ │                         │
│ │                                │ │                         │
│ │ REMARKS                        │ │                         │
│ │ ┌──────────────────────────┐   │ │                         │
│ │ │ Remarks text here...     │   │ │                         │
│ │ └──────────────────────────┘   │ │                         │
│ │                                │ │                         │
│ └────────────────────────────────┘ └─────────────────────────┘
│
└────────────────────────────────────────────────────────────┘
```

### EDIT PAGE (Form)

```
Same as CREATE page, but with:
- Pre-filled field values
- Status dropdown (Pending, Approved, Rejected, Issued)
- File upload indicators (✓ Current photo exists)
- PUT method instead of POST
- "Update" button instead of "Save"
```

---

## Color Scheme

```
Primary Color:        #004a93 (Navy Blue)
Success (Approved):   #28a745 (Green)
Warning (Pending):    #ffc107 (Orange)
Danger (Rejected):    #dc3545 (Red)
Info (Issued):        #0073aa (Blue)
Light Background:     #f8f9fa (Gray)
Text Dark:            #212529 (Dark Gray)
Text Muted:           #6c757d (Medium Gray)
Border Color:         #dee2e6 (Light Gray)
```

---

## Status Indicators

```
PENDING  ⏱️  Orange Badge with "schedule" icon
APPROVED ✓  Green Badge with "check_circle" icon
REJECTED ✗  Red Badge with "cancel" icon
ISSUED   🎫  Blue Badge with "card_giftcard" icon
```

---

## Form Fields Grouped

### Employee Type Selection
```
Type (Required, Default: Permanent Employee)
├── Permanent Employee (Radio)
└── Contractual Employee (Radio)
```

### Request Details
```
├── Card Type (Dropdown)
│   ├── LBSNAA
│   ├── Visitor
│   └── Contractor
├── Sub Type (Dropdown)
│   ├── Gazetted A Staff
│   ├── Non-Gazetted
│   └── Support Staff
└── Request For (Dropdown)
    ├── Own ID Card
    ├── Family ID Card
    └── Replacement
```

### Personal Information
```
├── Name (Text, Required)
├── Designation (Text)
├── Date of Birth (Date)
├── Father Name (Text)
└── Academy Joining (Date)
```

### Contact & ID Information
```
├── Mobile Number (Tel)
├── Telephone Number (Tel)
├── Blood Group (Select)
│   ├── O+
│   ├── O-
│   ├── A+
│   ├── A-
│   ├── B+
│   ├── B-
│   ├── AB+
│   └── AB-
├── ID Card Valid Upto (Text)
├── Section (Text)
├── Approval Authority (Text)
└── Vendor/Organization Name (Text)
```

### Document Upload
```
├── Photo (File Upload - Image)
│   └── Max 2MB, JPEG/PNG/JPG/GIF
└── Documents (File Upload - Doc)
    └── Max 5MB, PDF/DOC/DOCX
```

### Admin Section
```
├── Status (Select)
│   ├── Pending
│   ├── Approved
│   ├── Rejected
│   └── Issued
└── Remarks (Textarea)
```

---

## Button Styles & Icons

```
Primary Actions:
[✚ Add/Generate] - btn-primary with add icon
[💾 Save] - btn-primary with save icon
[🔄 Update] - btn-primary with update icon

Secondary Actions:
[👁️ View] - btn-outline-info with visibility icon
[✏️ Edit] - btn-outline-primary with edit icon

Destructive Actions:
[🗑️ Delete] - btn-outline-danger with delete icon

Navigation:
[← Cancel] - btn-outline-secondary with cancel icon
[← Back to List] - btn-outline-secondary with back icon
```

---

## Validation Feedback

```
Error State:
┌──────────────────────────────┐
│ Name *                        │
│ [............Name is req...] │
│ ❌ Name is required          │
└──────────────────────────────┘

Success State (Pre-filled):
┌──────────────────────────────┐
│ Name *                        │
│ [John Smith...............]  │
│ ✓ Field validated           │
└──────────────────────────────┘

File Upload Success:
✓ Selected: photo.jpg
```

---

## Responsive Breakpoints

```
MOBILE (< 576px)
├── Single column layout
├── Full-width inputs
├── Stacked sections
└── Dropdown menus

TABLET (576px - 992px)
├── 2-3 column layout
├── Side-by-side sections
└── Organized spacing

DESKTOP (> 992px)
├── Multi-column layout
├── Sidebar active
├── Full features visible
└── Optimal spacing
```

---

## Table Column Layout

```
Desktop (> 992px):
S.No | Request Date | Employee Name | Designation | Status | Actions
(70px) (150px)      (250px)         (200px)      (150px) (140px)

Tablet (576-992px):
S.No | Date | Name | Status | Actions
(50px) (120px) (150px) (120px) (100px)

Mobile (< 576px):
Name | Status | Actions
(Auto) (Auto) (100px)
```

---

## Material Icons Used

```
Badge Icons:
├── badge - Main icon
├── add_circle - Add action
├── visibility - View
├── edit - Edit action
├── delete - Delete action
├── check_circle - Approved status
├── schedule - Pending status
├── cancel - Rejected status
├── card_giftcard - Issued status
│
Document Icons:
├── cloud_upload - Upload area
├── image - Photo
├── description - Document
├── folder_zip - Files
├── download - Download
│
Navigation Icons:
├── arrow_back - Back
├── info - Information
├── comment - Remarks
├── phone - Contact
├── person_badge - Employee
├── work - Designation
└── manage_accounts - Admin
```

---

## Spacing & Typography

```
Heading Hierarchy:
H4 (28px) - Page title
H6 (16px) - Section title
Label (14px) - Form label
Body (14px) - Regular text
Small (12px) - Helper text

Spacing:
Padding: 0.5rem - 2rem
Margin: 0.5rem - 2rem
Gap (Flexbox): 0.5rem - 2rem
```

---

## Accessibility Features

```
✓ Semantic HTML
✓ Proper form labels
✓ ARIA attributes ready
✓ Keyboard navigation
✓ Color + icons (not color alone)
✓ Sufficient contrast
✓ Focus indicators
✓ Error messages clear
```

---

## Animation & Transitions

```
Card Hover: 0.3s ease shadow increase
Button Hover: Bootstrap default
Input Focus: 0.15s ease border color
Modal Open: 0.3s ease fade in
Tab Switch: 0.15s ease fade
Dropdown Open: Bootstrap animation
```

---

This visual guide provides a complete overview of the UI structure, form layouts, styling, and user interactions for the Employee ID Card Request System.

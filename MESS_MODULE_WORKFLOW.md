# Mess Module - Complete Workflow Documentation

## Table of Contents
1. [Module Overview](#module-overview)
2. [System Architecture](#system-architecture)
3. [Module Components](#module-components)
4. [Step-by-Step Workflow](#step-by-step-workflow)
5. [Process Flow Diagrams](#process-flow-diagrams)
6. [Database Structure](#database-structure)
7. [User Roles & Permissions](#user-roles--permissions)

---

## 1. Module Overview

The **Mess Module** is a comprehensive inventory and mess management system designed for institutional mess operations. It handles everything from vendor management, purchase orders, inventory tracking, sales vouchers, to billing and financial reporting.

### Key Objectives
- Streamline mess operations and inventory management
- Track purchases, allocations, and sales
- Manage employee and student mess bills
- Generate comprehensive reports for decision-making
- Implement role-based access control for security

### Module Location
- **Route Prefix**: `/admin/mess`
- **Controllers**: `app/Http/Controllers/Mess/`
- **Models**: `app/Models/Mess/` and `app/Models/KitchenIssue*`
- **Views**: `resources/views/mess/`

---

## 2. System Architecture

### 2.1 Architectural Pattern
The Mess module follows the **MVC (Model-View-Controller)** architecture pattern with Laravel framework:

```
┌─────────────────────────────────────────────┐
│           User Interface (Views)            │
│    (Blade Templates - resources/views/)     │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│         Controllers (Business Logic)        │
│   (app/Http/Controllers/Mess/)             │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│          Models (Data Layer)                │
│        (app/Models/Mess/)                   │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│           Database (MySQL)                  │
│     (mess_* tables, kitchen_issue_*)        │
└─────────────────────────────────────────────┘
```

### 2.2 Module Dependencies
- **Laravel Framework**: 8.x or higher
- **Authentication**: Laravel Auth middleware
- **Database**: MySQL/MariaDB
- **External Dependencies**: 
  - Employee Master (for staff records)
  - Student Master (for OT records)
  - Course Master (for course-based transactions)
  - Faculty Master (for faculty transactions)

---

## 3. Module Components

### 3.1 Master Data Management

#### A. Vendor Management (`VendorController`)
**Purpose**: Manage mess suppliers and vendors

**Features**:
- Add/Edit/Delete vendor records
- Store vendor contact information
- Track vendor status (active/inactive)
- Link vendors to specific item categories

**Database Table**: `mess_vendors`

**Fields**:
- `id`: Primary key
- `name`: Vendor name
- `contact_person`: Contact person name
- `phone`: Phone number
- `email`: Email address
- `address`: Full address
- `status`: Active/Inactive (1/0)
- `created_at`, `updated_at`: Timestamps

---

#### B. Item Category Management (`ItemCategoryController`)
**Purpose**: Categorize mess inventory items

**Features**:
- Create item categories (e.g., Vegetables, Dairy, Spices)
- Edit/Delete categories
- Status management

**Database Table**: `mess_item_categories`

---

#### C. Item Subcategory Management (`ItemSubcategoryController`)
**Purpose**: Detailed item classification

**Features**:
- Create subcategories under categories
- Define item properties:
  - Item Name
  - Item Code
  - Unit of Measurement (kg, liter, pieces, etc.)
  - Standard Cost
- Link to parent category
- Status management (active/inactive)

**Database Table**: `mess_item_subcategories`

**Key Fields**:
- `id`: Primary key
- `category_id`: Foreign key to item categories
- `name`: Subcategory name
- `item_name`: Display name
- `item_code`: Unique item code
- `unit_measurement`: Unit (kg, liter, etc.)
- `standard_cost`: Base cost per unit
- `status`: Active/Inactive

---

#### D. Store Management (`StoreController`)
**Purpose**: Manage main storage locations

**Features**:
- Create/Edit/Delete main stores
- Store identification and naming
- Status tracking

**Database Table**: `mess_stores`

**Fields**:
- `id`: Primary key
- `store_name`: Store name
- `store_code`: Unique store code
- `location`: Physical location
- `status`: Active/Inactive
- `created_at`, `updated_at`: Timestamps

---

#### E. Sub-Store Management (`SubStoreController`)
**Purpose**: Manage sub-storage locations

**Features**:
- Create sub-stores under main stores
- Independent inventory tracking
- Sub-store allocation management

**Database Table**: `mess_sub_stores`

---

#### F. Client Type Management (`ClientTypeController`)
**Purpose**: Define client categories for transactions

**Client Types**:
1. **Employee**: Staff members
2. **OT (Officer Trainees)**: Students/Trainees
3. **Course**: Course-specific transactions
4. **Other**: Guests, visitors, etc.

**Database Table**: `mess_client_types`

---

### 3.2 Configuration Modules

#### A. Vendor-Item Mapping (`VendorItemMappingController`)
**Purpose**: Map vendors to items they supply

**Mapping Types**:
- **Item Category Mapping**: Vendor supplies entire category
- **Item Subcategory Mapping**: Vendor supplies specific items

**Database Table**: `mess_vendor_item_mappings`

---

#### B. Sale Counter Management (`SaleCounterController`, `SaleCounterMappingController`)
**Purpose**: Manage mess counters and their item mappings

**Features**:
- Create sale counters (e.g., Breakfast Counter, Lunch Counter)
- Map items to specific counters
- Track counter transactions

**Database Tables**: 
- `mess_sale_counters`
- `mess_sale_counter_mappings`

---

#### C. Credit Limit Management (`CreditLimitController`)
**Purpose**: Set credit limits for clients

**Features**:
- Define credit limits by client type
- Set credit terms
- Monitor credit utilization

**Database Table**: `mess_credit_limits`

---

#### D. Number Configuration (`NumberConfigController`)
**Purpose**: Configure document numbering sequences

**Features**:
- Purchase Order number format
- Invoice number format
- Voucher number format

**Database Table**: `mess_number_configs`

---

### 3.3 Purchase Order Management

#### Purchase Order Workflow (`PurchaseOrderController`)

**Purpose**: Manage procurement of items from vendors

**Process Flow**:

```
┌─────────────────────┐
│  Create PO          │
│  (Select Vendor,    │
│   Items, Quantities)│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Auto-Generate      │
│  PO Number          │
│  (Format: PO        │
│   YYYYMMDDxxxx)     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Add PO Items       │
│  - Item Selection   │
│  - Quantity         │
│  - Unit Price       │
│  - Tax %            │
│  - Calculate Total  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Save PO            │
│  Status: Approved   │
│  (Auto-approved)    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Items Added to     │
│  Store Inventory    │
└─────────────────────┘
```

**Key Features**:
1. **Vendor Selection**: Choose from active vendors
2. **Store Selection**: Assign to main store
3. **Item Selection**: 
   - Select from vendor-mapped items
   - Or select from all item subcategories
4. **Pricing**: 
   - Unit price per item
   - Tax percentage calculation
   - Automatic total calculation
5. **Document Fields**:
   - PO Number (auto-generated)
   - PO Date
   - Delivery Date
   - Delivery Address
   - Contact Number
   - Payment Code
   - Remarks
6. **Status Management**: 
   - Approved (default)
   - Rejected
   - Pending (future enhancement)

**Database Tables**:
- `mess_purchase_orders`: Master table
- `mess_purchase_order_items`: Line items

**Controller Methods**:
- `index()`: List all purchase orders
- `create()`: Show creation form
- `store()`: Save new purchase order
- `edit($id)`: Get PO for editing (returns JSON)
- `update($id)`: Update existing PO
- `destroy($id)`: Delete PO
- `approve($id)`: Approve PO
- `reject($id)`: Reject PO

---

### 3.4 Store Allocation Management

#### Store Allocation Workflow (`StoreAllocationController`)

**Purpose**: Transfer items from main store to sub-stores

**Process Flow**:

```
┌─────────────────────┐
│  Select Sub-Store   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Select Items       │
│  (From Main Store   │
│   Inventory)        │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Enter Quantities   │
│  & Unit Prices      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Create Allocation  │
│  Record             │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Items Available    │
│  in Sub-Store       │
└─────────────────────┘
```

**Key Features**:
1. **Sub-Store Selection**: Choose destination sub-store
2. **Allocation Date**: Date of transfer
3. **Item Selection**: Select items from main store
4. **Quantity Management**: Specify transfer quantities
5. **Pricing**: Unit price and total calculation

**Database Tables**:
- `mess_store_allocations`: Allocation master
- `mess_store_allocation_items`: Allocated items

---

### 3.5 Material Management (Kitchen Issues)

#### Material Management Workflow (`KitchenIssueController`)

**Purpose**: Create selling vouchers for mess transactions

**Also Known As**: Selling Voucher, Kitchen Issue

**Process Flow**:

```
┌─────────────────────┐
│  Select Store       │
│  (Main/Sub-Store)   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Select Client Type │
│  - Employee         │
│  - OT (Student)     │
│  - Course           │
│  - Other            │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Select Client      │
│  (Based on Type)    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Select Payment     │
│  Type:              │
│  - Cash (0)         │
│  - Credit (1)       │
│  - Online (2)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Get Available      │
│  Items from Store   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Select Items &     │
│  Quantities         │
│  - Item Name        │
│  - Available Qty    │
│  - Issue Qty        │
│  - Rate             │
│  - Amount           │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Create Voucher     │
│  Status: Approved   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Optional:          │
│  Send for Approval  │
│  (Change Status to  │
│   Processing)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Approval Process   │
│  (Separate Module)  │
└─────────────────────┘
```

**Client Type Flow**:

**1. Employee Client**:
```
Select "Employee" → Choose Employee from list → Create Voucher
```

**2. OT (Student) Client**:
```
Select "OT" → Choose Course → Select Student from Course → Create Voucher
```

**3. Course Client**:
```
Select "Course" → Enter Client Name → Create Voucher
```

**4. Other Client**:
```
Select "Other" → Select from Client Type Master → Create Voucher
```

**Key Features**:
1. **Store Selection**: Main store or sub-store
2. **Dynamic Client Selection**: Based on client type
3. **Item Availability Check**: Shows available quantities
4. **Real-time Calculation**: Automatic amount calculation
5. **Multi-item Support**: Add multiple items per voucher
6. **Return Management**: Track returned items
7. **Approval Workflow**: Optional approval process

**Database Tables**:
- `kitchen_issue_master`: Voucher master
- `kitchen_issue_items`: Voucher line items
- `kitchen_issue_payment_details`: Payment records

**Status Values**:
- `0`: Pending
- `1`: Processing
- `2`: Approved
- `3`: Rejected
- `4`: Completed

**Payment Types**:
- `0`: Cash
- `1`: Credit (Deduct from Salary)
- `2`: Online
- `5`: Deduct from Salary (Alternative)

**Client Types**:
- `1`: Employee
- `2`: OT (Officer Trainee/Student)
- `3`: Course
- `4`: Other

---

#### Material Management Approval (`KitchenIssueApprovalController`)

**Purpose**: Review and approve pending material requests

**Process Flow**:

```
┌─────────────────────┐
│  View Pending       │
│  Requests           │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Review Request     │
│  Details            │
└──────────┬──────────┘
           │
           ▼
       ┌───┴───┐
       │       │
       ▼       ▼
┌──────────┐ ┌──────────┐
│ Approve  │ │ Reject   │
└────┬─────┘ └────┬─────┘
     │            │
     │            ▼
     │     ┌──────────────┐
     │     │ Add Remarks  │
     │     │ Update Status│
     │     └──────────────┘
     │
     ▼
┌─────────────────────┐
│  Update Status to   │
│  Approved           │
└─────────────────────┘
```

---

### 3.6 Selling Voucher Date Range

#### Selling Voucher Date Range Workflow (`SellingVoucherDateRangeController`)

**Purpose**: Standalone module for date-range based selling vouchers (similar design to Material Management, separate data)

**Key Differences from Material Management**:
- Separate database tables (`sv_date_range_reports`, `sv_date_range_report_items`)
- Date range support (from-to dates)
- Primarily used for reporting and historical analysis
- Similar UI and workflow pattern

**Process Flow**: Same as Material Management

**Database Tables**:
- `sv_date_range_reports`: Report master
- `sv_date_range_report_items`: Report line items

---

### 3.7 Billing & Finance

#### A. Process Mess Bills (Employee) (`ProcessMessBillsEmployeeController`)

**Purpose**: Generate and manage employee mess bills

**Process Flow**:

```
┌─────────────────────┐
│  Select Date Range  │
│  (Default: Current  │
│   Month)            │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Fetch Employee     │
│  Selling Vouchers   │
│  (From Date Range   │
│   Reports)          │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Display Bills      │
│  - Employee Name    │
│  - Total Amount     │
│  - Payment Type     │
│  - Status           │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Actions:           │
│  1. Generate Invoice│
│  2. Generate Payment│
│  3. Print Receipt   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Mark as Paid       │
│  (Status = 2)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Send Notification  │
│  to Employee        │
└─────────────────────┘
```

**Key Features**:
1. **Date Range Filter**: Filter bills by date range
2. **Search Functionality**: Search by employee name or ID
3. **Pagination**: Handle large datasets
4. **Bill Generation**: Create invoices for employees
5. **Payment Processing**: Mark bills as paid
6. **Receipt Printing**: Generate printable receipts

**Status Codes**:
- `0`: Pending
- `1`: Processing
- `2`: Paid/Approved

---

#### B. Monthly Bill Management (`MonthlyBillController`)

**Purpose**: Generate monthly consolidated bills

**Features**:
- Generate monthly bills for all clients
- Consolidate transactions by month
- Export reports

---

#### C. Finance Booking (`FinanceBookingController`)

**Purpose**: Financial transaction recording and approval

**Features**:
- Record financial bookings
- Approval workflow
- Financial reporting

---

### 3.8 Permission Management

#### Mess RBAC (`MessPermissionController`)

**Purpose**: Role-based access control for mess module

**Features**:
1. **Permission Actions**:
   - Create Purchase Order
   - Approve Purchase Order
   - Create Selling Voucher
   - Approve Material Request
   - View Reports
   - Manage Master Data
   - Billing Operations

2. **Role Assignment**:
   - Assign permissions to user roles
   - User-specific permissions

3. **Permission Check**:
   - Middleware-based permission checking
   - Dynamic permission validation

**Database Tables**:
- `mess_permissions`: Permission definitions
- `mess_permission_users`: User permission mappings

---

### 3.9 Reporting Module

#### Report Types (`ReportController`)

**1. Items List Report**
- List all items with categories
- Stock quantities
- Pricing information

**2. Mess Summary Report**
- Overall mess operations summary
- Transaction summaries
- Financial overview

**3. Category Material Report**
- Category-wise material consumption
- Trend analysis

**4. Pending Orders Report**
- Pending purchase orders
- Pending approvals

**5. Payment Overdue Report**
- Overdue payments
- Credit tracking

**6. Approved Inbound Report**
- Approved incoming stock
- Delivery tracking

**7. Invoice & Bill Reports**
- Invoice generation report
- Bill summary report

**8. Purchase Order Report**
- PO history
- Vendor-wise PO analysis

**9. OT Not Taking Food Report**
- Student attendance in mess
- Food consumption tracking

**10. Sale Counter Report**
- Counter-wise sales
- Item-wise sales by counter

**11. Store Due Report**
- Outstanding amounts by store
- Payment pending report

**12. Stock Purchase Details**
- Detailed stock purchase history
- Vendor-wise purchase analysis

**13. Client Invoice Report**
- Client-wise invoice summary
- Payment tracking

**14. Stock Issue Detail Report**
- Detailed issue history
- Item-wise consumption

---

## 4. Step-by-Step Workflow

### Workflow 1: Complete Purchase to Sale Cycle

#### Step 1: Setup Master Data
```
1. Login as Admin
2. Navigate to: Admin > Mess > Vendors
3. Add Vendor:
   - Name: ABC Suppliers
   - Contact: John Doe
   - Phone: 9876543210
   - Status: Active
4. Navigate to: Item Categories
5. Add Category: Vegetables
6. Navigate to: Item Subcategories
7. Add Items:
   - Tomato (kg, ₹50/kg)
   - Onion (kg, ₹40/kg)
   - Potato (kg, ₹30/kg)
8. Navigate to: Vendor-Item Mapping
9. Map ABC Suppliers → Vegetables Category
```

#### Step 2: Setup Stores
```
1. Navigate to: Admin > Mess > Stores
2. Add Main Store:
   - Store Name: Main Mess Store
   - Store Code: MS001
   - Location: Ground Floor
3. Navigate to: Sub-Stores
4. Add Sub-Store:
   - Name: Kitchen Sub-Store
   - Code: SS001
```

#### Step 3: Create Purchase Order
```
1. Navigate to: Admin > Mess > Purchase Orders
2. Click "Add New Purchase Order"
3. Fill Details:
   - Vendor: ABC Suppliers
   - Store: Main Mess Store
   - PO Date: [Current Date]
   - Delivery Date: [Future Date]
4. Add Items:
   Item 1:
   - Item: Tomato
   - Quantity: 100 kg
   - Unit Price: ₹50
   - Tax: 5%
   - Total: ₹5,250
   
   Item 2:
   - Item: Onion
   - Quantity: 50 kg
   - Unit Price: ₹40
   - Tax: 5%
   - Total: ₹2,100
   
5. Grand Total: ₹7,350
6. Click "Save"
7. PO Status: Approved (auto)
8. Items added to Main Mess Store inventory
```

#### Step 4: Allocate to Sub-Store
```
1. Navigate to: Admin > Mess > Store Allocations
2. Click "Add Allocation"
3. Select:
   - Sub-Store: Kitchen Sub-Store
   - Allocation Date: [Current Date]
4. Add Items:
   - Tomato: 50 kg @ ₹50/kg
   - Onion: 25 kg @ ₹40/kg
5. Click "Allocate"
6. Items transferred from Main Store to Kitchen Sub-Store
```

#### Step 5: Create Selling Voucher (Employee)
```
1. Navigate to: Admin > Mess > Material Management
2. Click "Add Selling Voucher"
3. Fill Details:
   - Store: Kitchen Sub-Store
   - Client Type: Employee
   - Select Employee: John Smith
   - Payment Type: Credit (Deduct from Salary)
   - Issue Date: [Current Date]
4. Add Items:
   - Tomato: 2 kg @ ₹55/kg = ₹110
   - Onion: 1 kg @ ₹45/kg = ₹45
5. Total: ₹155
6. Click "Create Voucher"
7. Status: Approved
```

#### Step 6: Process Employee Bill
```
1. Navigate to: Admin > Mess > Process Mess Bills (Employee)
2. Select Date Range: Current Month
3. View Bill for John Smith: ₹155
4. Click "Generate Payment"
5. Bill Status: Paid (Status = 2)
6. Notification sent to John Smith
```

---

### Workflow 2: OT (Student) Mess Transaction

#### Complete Flow:
```
Step 1: Navigate to Material Management
↓
Step 2: Select Store (Kitchen Sub-Store)
↓
Step 3: Select Client Type: OT
↓
Step 4: Select Course (e.g., Foundation Course 2024)
↓
Step 5: Select Student from course list
↓
Step 6: Payment Type: Cash
↓
Step 7: Add Items:
  - Tomato: 1 kg @ ₹55 = ₹55
  - Onion: 0.5 kg @ ₹45 = ₹22.50
↓
Step 8: Total: ₹77.50
↓
Step 9: Create Voucher
↓
Step 10: Transaction Complete
```

---

### Workflow 3: Material Request Approval Process

#### Flow:
```
Step 1: User creates Selling Voucher
↓
Step 2: Click "Send for Approval"
↓
Step 3: Status changes to "Processing" (1)
↓
Step 4: Approver receives notification
↓
Step 5: Navigate to: Material Management Approvals
↓
Step 6: Review request details
↓
Step 7a: APPROVE                   Step 7b: REJECT
  - Status → Approved (2)            - Add rejection remarks
  - Notification sent                - Status → Rejected (3)
  - Transaction complete             - Notification sent
```

---

## 5. Process Flow Diagrams

### 5.1 High-Level System Flow

```
┌─────────────────────────────────────────────────────────────┐
│                     MESS MODULE SYSTEM                       │
└─────────────────────────────────────────────────────────────┘

┌──────────────┐
│ MASTER DATA  │
│ SETUP        │
│              │
│ • Vendors    │
│ • Items      │
│ • Stores     │
│ • Clients    │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│ PROCUREMENT  │
│              │
│ • Purchase   │
│   Orders     │
│ • Vendor     │
│   Selection  │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│ INVENTORY    │
│ MANAGEMENT   │
│              │
│ • Main Store │
│ • Sub-Stores │
│ • Allocation │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│ SALES &      │
│ CONSUMPTION  │
│              │
│ • Selling    │
│   Vouchers   │
│ • Material   │
│   Issues     │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│ BILLING &    │
│ FINANCE      │
│              │
│ • Bills      │
│ • Payments   │
│ • Reports    │
└──────────────┘
```

---

### 5.2 Purchase Order Flow Diagram

```
START
  │
  ▼
┌────────────────────┐
│ Select Vendor      │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Select Store       │
│ (Destination)      │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Enter PO Details   │
│ • PO Date          │
│ • Delivery Date    │
│ • Payment Terms    │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Add Items          │
│ • Select Item      │◄─────┐
│ • Enter Quantity   │      │
│ • Enter Unit Price │      │
│ • Calculate Tax    │      │
│ • Calculate Total  │      │
└─────────┬──────────┘      │
          │                 │
          ├─────────────────┘
          │ (Add More Items)
          │
          ▼
┌────────────────────┐
│ Calculate Grand    │
│ Total              │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Save Purchase      │
│ Order              │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Auto-Approve PO    │
│ (Status = Approved)│
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Update Store       │
│ Inventory          │
│ (+Quantities)      │
└─────────┬──────────┘
          │
          ▼
        END
```

---

### 5.3 Material Management (Selling Voucher) Flow

```
START
  │
  ▼
┌──────────────────────┐
│ Select Store         │
│ (Main/Sub-Store)     │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│ Select Payment Type  │
│ • Cash (0)           │
│ • Credit (1)         │
│ • Online (2)         │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│ Select Client Type   │
└─────────┬────────────┘
          │
     ┌────┴────┬─────────┬─────────┐
     │         │         │         │
     ▼         ▼         ▼         ▼
┌─────────┐ ┌──────┐ ┌────────┐ ┌───────┐
│Employee │ │  OT  │ │ Course │ │ Other │
└────┬────┘ └───┬──┘ └───┬────┘ └───┬───┘
     │          │        │          │
     │          ▼        │          │
     │    ┌─────────┐   │          │
     │    │ Select  │   │          │
     │    │ Course  │   │          │
     │    └────┬────┘   │          │
     │         │        │          │
     │         ▼        │          │
     │    ┌─────────┐   │          │
     │    │ Select  │   │          │
     │    │ Student │   │          │
     │    └────┬────┘   │          │
     │         │        │          │
     └─────────┼────────┼──────────┘
               │        │
               ▼        ▼
          ┌──────────────────┐
          │ Client Selected  │
          └────────┬─────────┘
                   │
                   ▼
          ┌──────────────────┐
          │ Get Available    │
          │ Items from Store │
          └────────┬─────────┘
                   │
                   ▼
          ┌──────────────────┐
          │ Add Items        │◄─────┐
          │ • Select Item    │      │
          │ • Check Avail Qty│      │
          │ • Enter Quantity │      │
          │ • Enter Rate     │      │
          │ • Calc Amount    │      │
          └────────┬─────────┘      │
                   │                │
                   ├────────────────┘
                   │ (Add More)
                   │
                   ▼
          ┌──────────────────┐
          │ Calculate Total  │
          │ Amount           │
          └────────┬─────────┘
                   │
                   ▼
          ┌──────────────────┐
          │ Create Voucher   │
          │ (Status=Approved)│
          └────────┬─────────┘
                   │
                   ▼
          ┌──────────────────┐
          │ Optional:        │
          │ Send for Approval│
          └────────┬─────────┘
                   │
                   ▼
                 END
```

---

### 5.4 Store Allocation Flow

```
START
  │
  ▼
┌────────────────────┐
│ Select Sub-Store   │
│ (Destination)      │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Enter Allocation   │
│ Date               │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Get Items from     │
│ Main Store         │
│ (Show available    │
│  quantities)       │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Select Items       │◄─────┐
│ • Choose Item      │      │
│ • Check Avail Qty  │      │
│ • Enter Alloc Qty  │      │
│ • Enter Unit Price │      │
│ • Calculate Total  │      │
└─────────┬──────────┘      │
          │                 │
          ├─────────────────┘
          │ (Add More Items)
          │
          ▼
┌────────────────────┐
│ Save Allocation    │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ Update Inventory:  │
│ • Main Store (-Qty)│
│ • Sub-Store (+Qty) │
└─────────┬──────────┘
          │
          ▼
        END
```

---

### 5.5 Billing & Payment Flow

```
START
  │
  ▼
┌────────────────────────┐
│ Select Date Range      │
│ (Default: Current Month)│
└─────────┬──────────────┘
          │
          ▼
┌────────────────────────┐
│ Fetch Employee         │
│ Transactions from      │
│ Selling Voucher        │
│ Date Range Reports     │
└─────────┬──────────────┘
          │
          ▼
┌────────────────────────┐
│ Group by Employee      │
│ Calculate Total Amount │
└─────────┬──────────────┘
          │
          ▼
┌────────────────────────┐
│ Display Bill List      │
│ • Employee Name        │
│ • Total Amount         │
│ • Payment Type         │
│ • Status               │
└─────────┬──────────────┘
          │
          ▼
┌────────────────────────┐
│ Select Bill            │
└─────────┬──────────────┘
          │
     ┌────┴────┬──────────────┐
     │         │              │
     ▼         ▼              ▼
┌─────────┐ ┌──────────┐ ┌────────────┐
│ Generate│ │ Generate │ │   Print    │
│ Invoice │ │ Payment  │ │  Receipt   │
└────┬────┘ └─────┬────┘ └──────┬─────┘
     │            │             │
     ▼            ▼             │
┌─────────────────────┐         │
│ Send Notification   │         │
│ to Employee         │         │
└─────────┬───────────┘         │
          │                     │
          ▼                     │
     ┌─────────┐                │
     │ Mark as │                │
     │  Paid   │                │
     │(Status=2)│               │
     └────┬────┘                │
          │                     │
          └─────────────────────┘
                  │
                  ▼
                END
```

---

### 5.6 Approval Workflow

```
START (User Creates Request)
  │
  ▼
┌────────────────────┐
│ Create Selling     │
│ Voucher            │
│ (Status=Approved)  │
└─────────┬──────────┘
          │
          ▼
     ┌────────────┐
     │ Optional:  │
     │ Send for   │
     │ Approval?  │
     └─────┬──────┘
           │
      ┌────┴────┐
      │   NO    │   YES
      │         │    │
      ▼         │    ▼
   ┌──────┐    │ ┌───────────────────┐
   │ END  │    │ │ Status→Processing │
   └──────┘    │ │ (Status = 1)      │
               │ └────────┬──────────┘
               │          │
               │          ▼
               │ ┌───────────────────┐
               │ │ Notification to   │
               │ │ Approver          │
               │ └────────┬──────────┘
               │          │
               │          ▼
               │ ┌───────────────────┐
               │ │ Approver Reviews  │
               │ │ Request           │
               │ └────────┬──────────┘
               │          │
               │     ┌────┴────┐
               │     │         │
               │     ▼         ▼
               │ ┌─────────┐ ┌────────┐
               │ │ APPROVE │ │ REJECT │
               │ └────┬────┘ └───┬────┘
               │      │          │
               │      ▼          ▼
               │ ┌─────────┐ ┌──────────────┐
               │ │ Status→ │ │ Status→      │
               │ │Approved │ │ Rejected     │
               │ │(Status=2)│ │ (Status=3)  │
               │ └────┬────┘ └───┬──────────┘
               │      │          │
               │      ▼          ▼
               │ ┌─────────────────┐
               │ │ Notification to │
               │ │ Requester       │
               │ └────────┬────────┘
               │          │
               └──────────┘
                          │
                          ▼
                        END
```

---

## 6. Database Structure

### 6.1 Core Tables

#### Master Data Tables

**1. mess_vendors**
```sql
- id (PK)
- name
- contact_person
- phone
- email
- address
- status (1=Active, 0=Inactive)
- created_at
- updated_at
```

**2. mess_item_categories**
```sql
- id (PK)
- name
- description
- status
- created_at
- updated_at
```

**3. mess_item_subcategories**
```sql
- id (PK)
- category_id (FK → mess_item_categories)
- name
- item_name
- item_code (UNIQUE)
- unit_measurement (kg, liter, pieces, etc.)
- standard_cost
- status
- created_at
- updated_at
```

**4. mess_stores**
```sql
- id (PK)
- store_name
- store_code (UNIQUE)
- location
- status
- created_at
- updated_at
```

**5. mess_sub_stores**
```sql
- id (PK)
- sub_store_name
- sub_store_code
- status
- created_at
- updated_at
```

**6. mess_client_types**
```sql
- id (PK)
- client_type (employee, ot, course, other)
- client_name
- status
- created_at
- updated_at
```

---

#### Transaction Tables

**7. mess_purchase_orders**
```sql
- id (PK)
- po_number (UNIQUE)
- vendor_id (FK → mess_vendors)
- store_id (FK → mess_stores)
- po_date
- delivery_date
- total_amount
- payment_code
- delivery_address
- contact_number
- remarks
- status (pending, approved, rejected)
- created_by (FK → users)
- approved_by (FK → users)
- approved_at
- created_at
- updated_at
```

**8. mess_purchase_order_items**
```sql
- id (PK)
- purchase_order_id (FK → mess_purchase_orders)
- inventory_id (FK → mess_inventories) [deprecated]
- item_subcategory_id (FK → mess_item_subcategories)
- quantity
- unit
- unit_price
- tax_percent
- total_price
- description
- created_at
- updated_at
```

**9. mess_store_allocations**
```sql
- id (PK)
- sub_store_id (FK → mess_sub_stores)
- allocation_date
- created_at
- updated_at
```

**10. mess_store_allocation_items**
```sql
- id (PK)
- store_allocation_id (FK → mess_store_allocations)
- item_subcategory_id (FK → mess_item_subcategories)
- quantity
- unit
- unit_price
- total_price
- created_at
- updated_at
```

**11. kitchen_issue_master** (Material Management / Selling Voucher)
```sql
- pk (PK)
- store_id (FK → mess_stores)
- payment_type (0=Cash, 1=Credit, 2=Online)
- client_type (1=Employee, 2=OT, 3=Course, 4=Other)
- client_type_pk (FK → mess_client_types)
- client_id (Employee/Student PK)
- name_id
- client_name
- issue_date
- kitchen_issue_type (1=Selling Voucher, 2=Date Range)
- status (0=Pending, 1=Processing, 2=Approved, 3=Rejected, 4=Completed)
- remarks
- created_at
- updated_at
```

**12. kitchen_issue_items**
```sql
- pk (PK)
- kitchen_issue_master_pk (FK → kitchen_issue_master)
- item_subcategory_id (FK → mess_item_subcategories)
- item_name
- quantity
- available_quantity
- return_quantity
- rate
- amount
- unit
- return_date
- created_at
- updated_at
```

**13. sv_date_range_reports** (Selling Voucher Date Range)
```sql
- id (PK)
- date_from
- date_to
- store_id (FK → mess_stores)
- report_title
- status
- total_amount
- remarks
- client_type_slug
- client_type_pk
- client_name
- payment_type
- issue_date
- created_by
- updated_by
- created_at
- updated_at
```

**14. sv_date_range_report_items**
```sql
- id (PK)
- sv_date_range_report_id (FK → sv_date_range_reports)
- item_subcategory_id (FK → mess_item_subcategories)
- item_name
- quantity
- available_quantity
- return_quantity
- rate
- amount
- unit
- return_date
- created_at
- updated_at
```

---

#### Configuration Tables

**15. mess_vendor_item_mappings**
```sql
- id (PK)
- vendor_id (FK → mess_vendors)
- mapping_type (category/subcategory)
- item_category_id (FK → mess_item_categories)
- item_subcategory_id (FK → mess_item_subcategories)
- created_at
- updated_at
```

**16. mess_credit_limits**
```sql
- id (PK)
- client_type
- credit_limit
- credit_terms
- created_at
- updated_at
```

**17. mess_sale_counters**
```sql
- id (PK)
- counter_name
- counter_code
- status
- created_at
- updated_at
```

**18. mess_sale_counter_mappings**
```sql
- id (PK)
- sale_counter_id (FK → mess_sale_counters)
- item_subcategory_id (FK → mess_item_subcategories)
- created_at
- updated_at
```

**19. mess_permissions**
```sql
- id (PK)
- permission_name
- permission_code
- description
- created_at
- updated_at
```

**20. mess_permission_users**
```sql
- id (PK)
- permission_id (FK → mess_permissions)
- user_id (FK → users)
- role_id (FK → roles)
- created_at
- updated_at
```

---

### 6.2 Entity Relationships

```
mess_vendors ──┬─── mess_purchase_orders
               │
               └─── mess_vendor_item_mappings

mess_item_categories ──┬─── mess_item_subcategories
                       │
                       └─── mess_vendor_item_mappings

mess_item_subcategories ──┬─── mess_purchase_order_items
                          ├─── mess_store_allocation_items
                          ├─── kitchen_issue_items
                          ├─── sv_date_range_report_items
                          └─── mess_sale_counter_mappings

mess_stores ──┬─── mess_purchase_orders
              ├─── kitchen_issue_master
              └─── sv_date_range_reports

mess_sub_stores ─── mess_store_allocations

mess_purchase_orders ─── mess_purchase_order_items

mess_store_allocations ─── mess_store_allocation_items

kitchen_issue_master ─── kitchen_issue_items

sv_date_range_reports ─── sv_date_range_report_items

mess_client_types ──┬─── kitchen_issue_master
                    └─── sv_date_range_reports

users ──┬─── mess_purchase_orders (created_by, approved_by)
        ├─── kitchen_issue_master
        ├─── sv_date_range_reports
        └─── mess_permission_users
```

---

## 7. User Roles & Permissions

### 7.1 Role Definitions

#### 1. Mess Admin
**Full access to all mess operations**

Permissions:
- ✅ Manage Master Data (Vendors, Items, Stores, Client Types)
- ✅ Create/Edit/Delete Purchase Orders
- ✅ Approve/Reject Purchase Orders
- ✅ Manage Store Allocations
- ✅ Create/Edit/Delete Selling Vouchers
- ✅ Approve/Reject Material Requests
- ✅ Process Employee Bills
- ✅ Generate Payments
- ✅ View All Reports
- ✅ Manage Permissions

---

#### 2. Store Manager
**Manages inventory and transactions**

Permissions:
- ✅ View Master Data
- ✅ Create Purchase Orders
- ❌ Approve Purchase Orders (requires admin)
- ✅ Manage Store Allocations
- ✅ Create Selling Vouchers
- ❌ Approve Material Requests (requires admin)
- ✅ View Reports (Store-level)
- ❌ Manage Permissions

---

#### 3. Billing Officer
**Handles financial operations**

Permissions:
- ❌ Manage Master Data
- ❌ Create Purchase Orders
- ❌ Approve Purchase Orders
- ❌ Manage Store Allocations
- ✅ View Selling Vouchers
- ❌ Create Selling Vouchers
- ✅ Process Employee Bills
- ✅ Generate Invoices
- ✅ Generate Payments
- ✅ View Financial Reports
- ❌ Manage Permissions

---

#### 4. Mess Staff
**Basic operational access**

Permissions:
- ❌ Manage Master Data
- ❌ Create Purchase Orders
- ❌ Approve Purchase Orders
- ❌ Manage Store Allocations
- ✅ Create Selling Vouchers (limited)
- ❌ Approve Material Requests
- ✅ View own transactions
- ❌ Process Bills
- ❌ View Reports
- ❌ Manage Permissions

---

#### 5. Approver
**Reviews and approves requests**

Permissions:
- ❌ Manage Master Data
- ❌ Create Purchase Orders
- ✅ Approve/Reject Purchase Orders
- ❌ Manage Store Allocations
- ❌ Create Selling Vouchers
- ✅ Approve/Reject Material Requests
- ✅ View Pending Approvals
- ✅ View Reports (Approval-related)
- ❌ Manage Permissions

---

### 7.2 Permission Matrix

| Permission | Mess Admin | Store Manager | Billing Officer | Mess Staff | Approver |
|-----------|-----------|---------------|----------------|-----------|---------|
| View Master Data | ✅ | ✅ | ✅ | ❌ | ✅ |
| Manage Master Data | ✅ | ❌ | ❌ | ❌ | ❌ |
| Create PO | ✅ | ✅ | ❌ | ❌ | ❌ |
| Approve PO | ✅ | ❌ | ❌ | ❌ | ✅ |
| Manage Allocations | ✅ | ✅ | ❌ | ❌ | ❌ |
| Create Voucher | ✅ | ✅ | ❌ | ✅ | ❌ |
| Approve Voucher | ✅ | ❌ | ❌ | ❌ | ✅ |
| Process Bills | ✅ | ❌ | ✅ | ❌ | ❌ |
| Generate Payment | ✅ | ❌ | ✅ | ❌ | ❌ |
| View All Reports | ✅ | ✅ | ✅ | ❌ | ✅ |
| Manage Permissions | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 8. API Endpoints Reference

### Master Data Endpoints

#### Vendors
- `GET /admin/mess/vendors` - List all vendors
- `GET /admin/mess/vendors/create` - Show create form
- `POST /admin/mess/vendors` - Store new vendor
- `GET /admin/mess/vendors/{id}/edit` - Show edit form
- `PUT /admin/mess/vendors/{id}` - Update vendor
- `DELETE /admin/mess/vendors/{id}` - Delete vendor

#### Item Categories
- `GET /admin/mess/itemcategories` - List categories
- `POST /admin/mess/itemcategories` - Create category
- `PUT /admin/mess/itemcategories/{id}` - Update category
- `DELETE /admin/mess/itemcategories/{id}` - Delete category

#### Item Subcategories
- `GET /admin/mess/itemsubcategories` - List subcategories
- `POST /admin/mess/itemsubcategories` - Create subcategory
- `PUT /admin/mess/itemsubcategories/{id}` - Update subcategory
- `DELETE /admin/mess/itemsubcategories/{id}` - Delete subcategory

#### Stores
- `GET /admin/mess/stores` - List stores
- `POST /admin/mess/stores` - Create store
- `PUT /admin/mess/stores/{id}` - Update store
- `DELETE /admin/mess/stores/{id}` - Delete store

#### Sub-Stores
- `GET /admin/mess/sub-stores` - List sub-stores
- `POST /admin/mess/sub-stores` - Create sub-store
- `PUT /admin/mess/sub-stores/{id}` - Update sub-store
- `DELETE /admin/mess/sub-stores/{id}` - Delete sub-store

---

### Transaction Endpoints

#### Purchase Orders
- `GET /admin/mess/purchaseorders` - List purchase orders
- `GET /admin/mess/purchaseorders/create` - Show create form
- `POST /admin/mess/purchaseorders` - Create PO
- `GET /admin/mess/purchaseorders/{id}` - View PO
- `GET /admin/mess/purchaseorders/{id}/edit` - Get PO for editing (JSON)
- `PUT /admin/mess/purchaseorders/{id}` - Update PO
- `DELETE /admin/mess/purchaseorders/{id}` - Delete PO
- `POST /admin/mess/purchaseorders/{id}/approve` - Approve PO
- `POST /admin/mess/purchaseorders/{id}/reject` - Reject PO

#### Store Allocations
- `GET /admin/mess/storeallocations` - List allocations
- `POST /admin/mess/storeallocations` - Create allocation
- `GET /admin/mess/storeallocations/{id}/edit` - Get allocation (JSON)
- `PUT /admin/mess/storeallocations/{id}` - Update allocation
- `DELETE /admin/mess/storeallocations/{id}` - Delete allocation

#### Material Management (Kitchen Issues / Selling Vouchers)
- `GET /admin/mess/material-management` - List vouchers
- `GET /admin/mess/material-management/create` - Show create form
- `POST /admin/mess/material-management` - Create voucher
- `GET /admin/mess/material-management/{id}` - View voucher (JSON)
- `GET /admin/mess/material-management/{id}/edit` - Get voucher for editing (JSON)
- `PUT /admin/mess/material-management/{id}` - Update voucher
- `DELETE /admin/mess/material-management/{id}` - Delete voucher
- `GET /admin/mess/material-management/{id}/return` - Get return data (JSON)
- `PUT /admin/mess/material-management/{id}/return` - Update return
- `POST /admin/mess/material-management/{id}/send-for-approval` - Send for approval
- `GET /admin/mess/material-management/records/ajax` - Get records (AJAX)
- `GET /admin/mess/material-management/students-by-course/{course_pk}` - Get students by course
- `GET /admin/mess/material-management/store/{storeIdentifier}/items` - Get store items

#### Selling Voucher Date Range
- `GET /admin/mess/selling-voucher-date-range` - List reports
- `POST /admin/mess/selling-voucher-date-range` - Create report
- `GET /admin/mess/selling-voucher-date-range/{id}` - View report (JSON)
- `GET /admin/mess/selling-voucher-date-range/{id}/edit` - Get report for editing (JSON)
- `PUT /admin/mess/selling-voucher-date-range/{id}` - Update report
- `DELETE /admin/mess/selling-voucher-date-range/{id}` - Delete report
- `GET /admin/mess/selling-voucher-date-range/{id}/return` - Get return data
- `PUT /admin/mess/selling-voucher-date-range/{id}/return` - Update return
- `GET /admin/mess/selling-voucher-date-range/students-by-course/{course_pk}` - Get students
- `GET /admin/mess/selling-voucher-date-range/store/{storeIdentifier}/items` - Get store items

#### Material Management Approvals
- `GET /admin/mess/material-management-approvals` - List pending approvals
- `GET /admin/mess/material-management-approvals/{id}` - View approval request
- `POST /admin/mess/material-management-approvals/{id}/approve` - Approve request
- `POST /admin/mess/material-management-approvals/{id}/reject` - Reject request

---

### Billing & Finance Endpoints

#### Process Mess Bills (Employee)
- `GET /admin/mess/process-mess-bills-employee` - List employee bills
- `GET /admin/mess/process-mess-bills-employee/modal-data` - Get modal data (AJAX)
- `POST /admin/mess/process-mess-bills-employee/{id}/generate-invoice` - Generate invoice
- `POST /admin/mess/process-mess-bills-employee/{id}/generate-payment` - Generate payment
- `GET /admin/mess/process-mess-bills-employee/{id}/print-receipt` - Print receipt

#### Monthly Bills
- `GET /admin/mess/monthly-bills` - List monthly bills
- `POST /admin/mess/monthly-bills/generate` - Generate monthly bills

#### Finance Bookings
- `GET /admin/mess/finance-bookings` - List bookings
- `POST /admin/mess/finance-bookings` - Create booking
- `POST /admin/mess/finance-bookings/{id}/approve` - Approve booking
- `POST /admin/mess/finance-bookings/{id}/reject` - Reject booking

---

### Report Endpoints

- `GET /admin/mess/reports/items-list` - Items list report
- `GET /admin/mess/reports/mess-summary` - Mess summary
- `GET /admin/mess/reports/category-material` - Category material report
- `GET /admin/mess/reports/pending-orders` - Pending orders
- `GET /admin/mess/reports/payment-overdue` - Payment overdue
- `GET /admin/mess/reports/approved-inbound` - Approved inbound
- `GET /admin/mess/reports/invoice-bill` - Invoice & bill report
- `GET /admin/mess/reports/purchase-orders` - Purchase orders report
- `GET /admin/mess/reports/ot-not-taking-food` - OT not taking food
- `GET /admin/mess/reports/sale-counter` - Sale counter report
- `GET /admin/mess/reports/store-due` - Store due report
- `GET /admin/mess/reports/mess-bill` - Mess bill report
- `GET /admin/mess/reports/mess-invoice` - Mess invoice report
- `GET /admin/mess/reports/stock-purchase-details` - Stock purchase details
- `GET /admin/mess/reports/client-invoice` - Client invoice
- `GET /admin/mess/reports/stock-issue-detail` - Stock issue detail

---

### Permission Endpoints

- `GET /admin/mess/permissions` - List permissions
- `POST /admin/mess/permissions` - Create permission
- `PUT /admin/mess/permissions/{id}` - Update permission
- `DELETE /admin/mess/permissions/{id}` - Delete permission
- `GET /admin/mess/permissions/users-by-role` - Get users by role
- `GET /admin/mess/permissions/check/{action}` - Check permission

---

## 9. Frontend UI Components

### 9.1 Common UI Patterns

#### Modal-Based CRUD
Most operations use Bootstrap modals for Create/Edit/View:

**Example: Purchase Order Modal Flow**
```
1. Click "Add New Purchase Order" button
   ↓
2. Modal opens with form
   ↓
3. Fill details and items
   ↓
4. Submit via AJAX
   ↓
5. Modal closes on success
   ↓
6. DataTable refreshes automatically
```

#### Dynamic Item Tables
All modules with line items (PO, Allocations, Vouchers) use dynamic table rows:

**Features**:
- Add Row button
- Remove Row button (per row)
- Auto-calculation on quantity/price change
- Real-time grand total update
- Validation before submission

#### DataTables Integration
All list views use DataTables for:
- Pagination
- Sorting
- Searching
- Export (Excel, PDF)
- Filtering

---

### 9.2 Key UI Screens

#### 1. Purchase Order Index Page
**Location**: `resources/views/mess/purchaseorders/index.blade.php`

**Features**:
- List all purchase orders
- Filter by vendor, store, status, date range
- Actions: View, Edit, Delete, Approve, Reject
- Add New PO button (opens modal)
- Export to Excel/PDF

---

#### 2. Material Management Index
**Location**: `resources/views/mess/kitchen-issues/index.blade.php`

**Features**:
- List all selling vouchers
- Filter by store, client type, payment type, status, date range
- Actions: View, Edit, Delete, Return, Send for Approval
- Add Selling Voucher button (opens modal)
- Client type-specific forms

---

#### 3. Store Allocations Index
**Location**: `resources/views/mess/storeallocations/index.blade.php`

**Features**:
- List all allocations
- Filter by sub-store, date
- Actions: Edit, Delete
- Add Allocation button (opens modal)

---

#### 4. Process Mess Bills (Employee)
**Location**: `resources/views/admin/mess/process-mess-bills-employee/index.blade.php`

**Features**:
- Date range filter (defaults to current month)
- Search by employee name
- Bill list with employee details
- Actions: Generate Invoice, Generate Payment, Print Receipt
- Payment status indicators

---

## 10. Technical Implementation Details

### 10.1 Database Transactions
All multi-table operations use database transactions:

```php
DB::transaction(function () use ($request) {
    // Create master record
    $po = PurchaseOrder::create([...]);
    
    // Create line items
    foreach ($request->items as $item) {
        PurchaseOrderItem::create([...]);
    }
});
```

### 10.2 Auto-Number Generation
Purchase Orders use auto-generated numbers:

```php
$po_number = 'PO' . date('Ymd') . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);
// Example: PO202602090001
```

### 10.3 Status Management
All transactions use numeric status codes:

```php
// Kitchen Issue Master Status
const STATUS_PENDING = 0;
const STATUS_PROCESSING = 1;
const STATUS_APPROVED = 2;
const STATUS_REJECTED = 3;
const STATUS_COMPLETED = 4;
```

### 10.4 Inventory Tracking
Inventory is tracked at two levels:

**1. Main Store Inventory**:
- Populated from Purchase Orders
- Query: SUM(purchase_order_items.quantity) WHERE purchase_order.store_id = X

**2. Sub-Store Inventory**:
- Populated from Store Allocations
- Query: SUM(store_allocation_items.quantity) WHERE store_allocation.sub_store_id = Y

### 10.5 Dynamic Item Loading (AJAX)
Store items are loaded dynamically based on store selection:

```javascript
$('#store_id').change(function() {
    let storeId = $(this).val();
    $.get('/admin/mess/material-management/store/' + storeId + '/items', function(data) {
        // Populate item dropdown
    });
});
```

---

## 11. Best Practices & Guidelines

### 11.1 Data Entry Best Practices

1. **Always validate quantities**: Check available quantity before issuing items
2. **Use proper units**: Ensure unit consistency (kg, liters, pieces)
3. **Add meaningful remarks**: Document special cases or exceptions
4. **Verify client details**: Double-check employee/student selection
5. **Regular reconciliation**: Match physical stock with system inventory

### 11.2 Approval Workflow Guidelines

1. **Review before approval**: Check quantities, pricing, client details
2. **Add rejection reasons**: Always provide explanation for rejections
3. **Timely approvals**: Process pending requests within 24 hours
4. **Escalation path**: Define clear escalation for high-value transactions

### 11.3 Security Considerations

1. **Role-based access**: Use appropriate user roles
2. **Audit logging**: Track all critical operations
3. **Data validation**: Validate all inputs on server-side
4. **Permission checks**: Always check permissions before operations
5. **Secure transactions**: Use database transactions for data integrity

---

## 12. Troubleshooting & FAQ

### Common Issues

**Q1: Items not showing in dropdown after store selection**
- **Cause**: No inventory in selected store
- **Solution**: 
  - For main stores: Create purchase orders first
  - For sub-stores: Create store allocations first

**Q2: Can't approve purchase order**
- **Cause**: Insufficient permissions
- **Solution**: Check user role and permissions

**Q3: Grand total not calculating**
- **Cause**: JavaScript error or missing rate/quantity
- **Solution**: Check browser console, ensure all fields are filled

**Q4: Client not appearing in dropdown**
- **Cause**: 
  - For employees: Employee status may be inactive
  - For OT: Student not mapped to selected course
- **Solution**: Verify master data and mappings

**Q5: Billing report shows wrong amounts**
- **Cause**: Date range mismatch or incorrect data filtering
- **Solution**: 
  - Check date range selection
  - Verify transaction dates
  - Run data reconciliation

---

## 13. Future Enhancements

### Planned Features

1. **Real-time Stock Alerts**
   - Low stock notifications
   - Expiry date tracking
   - Automated reorder points

2. **Mobile Application**
   - Mobile-friendly interface
   - Barcode scanning for items
   - Quick voucher creation

3. **Advanced Analytics**
   - Consumption trends
   - Predictive ordering
   - Cost optimization suggestions

4. **Integration with Finance Module**
   - Automated salary deductions
   - Direct bank transfers
   - Accounting system integration

5. **Vendor Portal**
   - Vendor login
   - PO acknowledgment
   - Delivery scheduling
   - Invoice submission

6. **Inventory Optimization**
   - ABC analysis
   - Dead stock identification
   - Batch tracking
   - FIFO/LIFO management

---

## 14. Support & Documentation

### Contact Information
- **Technical Support**: [Your contact]
- **Documentation**: This file
- **User Manual**: [To be created]
- **Training Videos**: [To be created]

### Version History
- **v1.0** (Current): Initial comprehensive workflow documentation
- Date: February 9, 2026

---

## 15. Appendices

### Appendix A: Keyboard Shortcuts
(To be defined based on implementation)

### Appendix B: Sample Data
(To be added for testing purposes)

### Appendix C: Migration Guide
(For upgrading from older versions)

### Appendix D: Performance Optimization
(Database indexing and query optimization tips)

---

## Conclusion

This Mess Module provides a comprehensive solution for institutional mess management, covering:
- Complete inventory tracking
- Purchase order management
- Store allocations
- Sales vouchers
- Billing and payments
- Comprehensive reporting
- Role-based access control

The modular architecture allows for easy maintenance and future enhancements while the workflow-based design ensures smooth operations for all user roles.

For any questions or clarifications, please refer to the specific sections above or contact the technical support team.

---

**Document End**

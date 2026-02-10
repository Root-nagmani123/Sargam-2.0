# Mess Module - Visual Flowcharts & Diagrams

This document contains detailed visual flowcharts for the Mess Module operations. These diagrams can be rendered using Mermaid or similar tools.

---

## Table of Contents
1. [Overall System Architecture](#overall-system-architecture)
2. [Master Data Setup Flow](#master-data-setup-flow)
3. [Purchase Order Complete Flow](#purchase-order-complete-flow)
4. [Store Allocation Flow](#store-allocation-flow)
5. [Material Management Flow](#material-management-flow)
6. [Approval Workflow](#approval-workflow)
7. [Billing & Payment Flow](#billing--payment-flow)
8. [Client Type Selection Flow](#client-type-selection-flow)

---

## 1. Overall System Architecture

```mermaid
graph TB
    A[User Login] --> B{User Role?}
    B -->|Admin| C[Full Access]
    B -->|Store Manager| D[Store Operations]
    B -->|Billing Officer| E[Billing Operations]
    B -->|Mess Staff| F[Basic Operations]
    B -->|Approver| G[Approval Operations]
    
    C --> H[Master Data Management]
    C --> I[Procurement]
    C --> J[Inventory]
    C --> K[Sales]
    C --> L[Billing]
    C --> M[Reports]
    
    D --> I
    D --> J
    D --> K
    
    E --> L
    E --> M
    
    F --> K
    
    G --> N[Approval Queue]
    
    H --> H1[Vendors]
    H --> H2[Items]
    H --> H3[Stores]
    H --> H4[Client Types]
    
    I --> I1[Purchase Orders]
    I --> I2[Vendor Management]
    
    J --> J1[Main Store]
    J --> J2[Sub-Stores]
    J --> J3[Allocations]
    
    K --> K1[Selling Vouchers]
    K --> K2[Material Issues]
    
    L --> L1[Employee Bills]
    L --> L2[Monthly Bills]
    L --> L3[Payments]
    
    M --> M1[Inventory Reports]
    M --> M2[Financial Reports]
    M --> M3[Transaction Reports]
    
    N --> N1[PO Approvals]
    N --> N2[Material Approvals]
    
    style A fill:#e1f5ff
    style C fill:#d4edda
    style D fill:#d4edda
    style E fill:#d4edda
    style F fill:#d4edda
    style G fill:#d4edda
```

---

## 2. Master Data Setup Flow

```mermaid
flowchart TD
    Start([Start: System Setup]) --> A[Login as Admin]
    A --> B[Navigate to Mess Module]
    B --> C{Setup What?}
    
    C -->|Vendors| D[Create Vendor]
    D --> D1[Enter Name, Contact]
    D1 --> D2[Enter Phone, Email]
    D2 --> D3[Enter Address]
    D3 --> D4[Set Status Active]
    D4 --> D5[Save Vendor]
    D5 --> VIM[Create Vendor-Item Mapping]
    
    C -->|Items| E[Create Item Category]
    E --> E1[Enter Category Name]
    E1 --> E2[Set Status]
    E2 --> E3[Save Category]
    E3 --> F[Create Item Subcategory]
    F --> F1[Select Category]
    F1 --> F2[Enter Item Details]
    F2 --> F3[Item Name, Code]
    F3 --> F4[Unit of Measurement]
    F4 --> F5[Standard Cost]
    F5 --> F6[Save Item]
    F6 --> VIM
    
    VIM --> VIM1{Mapping Type?}
    VIM1 -->|Category| VIM2[Map Vendor to Category]
    VIM1 -->|Subcategory| VIM3[Map Vendor to Item]
    VIM2 --> G
    VIM3 --> G
    
    C -->|Stores| G[Create Main Store]
    G --> G1[Enter Store Name]
    G1 --> G2[Enter Store Code]
    G2 --> G3[Enter Location]
    G3 --> G4[Set Status]
    G4 --> G5[Save Store]
    G5 --> H[Create Sub-Store]
    H --> H1[Enter Sub-Store Name]
    H1 --> H2[Enter Sub-Store Code]
    H2 --> H3[Set Status]
    H3 --> H4[Save Sub-Store]
    H4 --> I
    
    C -->|Client Types| I[Create Client Types]
    I --> I1[Add Employee Type Clients]
    I1 --> I2[Add OT Type Clients]
    I2 --> I3[Add Course Type Clients]
    I3 --> I4[Add Other Type Clients]
    I4 --> J[Setup Complete]
    
    J --> End([Master Data Ready])
    
    style Start fill:#e3f2fd
    style End fill:#c8e6c9
    style D5 fill:#fff9c4
    style F6 fill:#fff9c4
    style G5 fill:#fff9c4
    style H4 fill:#fff9c4
    style J fill:#c8e6c9
```

---

## 3. Purchase Order Complete Flow

```mermaid
flowchart TD
    Start([Start: Create Purchase Order]) --> A[Navigate to Purchase Orders]
    A --> B[Click Add New PO Button]
    B --> C[Modal Opens]
    C --> D[Auto-generate PO Number]
    D --> E[Select Vendor from Dropdown]
    E --> E1{Vendor Mapping Exists?}
    E1 -->|Yes| E2[Load Vendor-Mapped Items]
    E1 -->|No| E3[Load All Items]
    E2 --> F
    E3 --> F
    F[Select Store Destination]
    F --> G[Enter PO Date]
    G --> H[Enter Delivery Date Optional]
    H --> I[Enter Payment Code]
    I --> J[Enter Delivery Address]
    J --> K[Enter Contact Number]
    K --> L[Add Items Section]
    
    L --> M[Click Add Item Row]
    M --> N[Select Item from Dropdown]
    N --> O[Enter Quantity]
    O --> P[Enter Unit Price]
    P --> Q[Enter Tax Percentage]
    Q --> R[Auto-calculate Line Total]
    R --> S{Add More Items?}
    S -->|Yes| M
    S -->|No| T[Calculate Grand Total]
    
    T --> U[Enter Remarks Optional]
    U --> V[Click Save Button]
    V --> W[Validate Form]
    W --> X{Validation Pass?}
    X -->|No| Y[Show Validation Errors]
    Y --> W
    X -->|Yes| Z[Start Database Transaction]
    
    Z --> AA[Create PO Master Record]
    AA --> AB[Status = Approved Auto]
    AB --> AC[Set Created By = Current User]
    AC --> AD[Loop Through Items]
    AD --> AE[Create PO Item Record]
    AE --> AF{More Items?}
    AF -->|Yes| AD
    AF -->|No| AG[Commit Transaction]
    
    AG --> AH[Update Store Inventory]
    AH --> AI[Add Purchased Quantities to Store]
    AI --> AJ[Close Modal]
    AJ --> AK[Show Success Message]
    AK --> AL[Refresh DataTable]
    AL --> AM[PO Appears in List]
    AM --> End([PO Created Successfully])
    
    style Start fill:#e3f2fd
    style End fill:#c8e6c9
    style AB fill:#fff9c4
    style AG fill:#fff9c4
    style AI fill:#fff9c4
    style Y fill:#ffcdd2
```

---

## 4. Store Allocation Flow

```mermaid
flowchart TD
    Start([Start: Allocate Items to Sub-Store]) --> A[Navigate to Store Allocations]
    A --> B[Click Add Allocation Button]
    B --> C[Modal Opens]
    C --> D[Select Sub-Store from Dropdown]
    D --> E[Enter Allocation Date]
    E --> F{Sub-Store Selected?}
    F -->|No| E
    F -->|Yes| G[Get Items from Main Store]
    
    G --> H[Query: SUM purchase_order_items by item]
    H --> I[Display Available Quantities]
    I --> J[Add Item Row Section]
    
    J --> K[Click Add Item Row]
    K --> L[Select Item from Dropdown]
    L --> M[Display Available Quantity]
    M --> N[Enter Allocation Quantity]
    N --> O{Quantity <= Available?}
    O -->|No| P[Show Error: Insufficient Stock]
    P --> N
    O -->|Yes| Q[Enter Unit Price]
    Q --> R[Auto-calculate Total Price]
    R --> S{Add More Items?}
    S -->|Yes| K
    S -->|No| T[Review All Items]
    
    T --> U[Click Save Allocation]
    U --> V[Validate Form]
    V --> W{Validation Pass?}
    W -->|No| X[Show Errors]
    X --> V
    W -->|Yes| Y[Start Transaction]
    
    Y --> Z[Create Allocation Master]
    Z --> AA[Loop Through Items]
    AA --> AB[Create Allocation Item]
    AB --> AC{More Items?}
    AC -->|Yes| AA
    AC -->|No| AD[Commit Transaction]
    
    AD --> AE[Update Inventory]
    AE --> AF[Main Store: Subtract Quantities]
    AF --> AG[Sub-Store: Add Quantities]
    AG --> AH[Close Modal]
    AH --> AI[Show Success Message]
    AI --> AJ[Refresh List]
    AJ --> End([Allocation Completed])
    
    style Start fill:#e3f2fd
    style End fill:#c8e6c9
    style P fill:#ffcdd2
    style X fill:#ffcdd2
    style AD fill:#fff9c4
    style AF fill:#fff9c4
    style AG fill:#fff9c4
```

---

## 5. Material Management Flow

### 5.1 Main Selling Voucher Flow

```mermaid
flowchart TD
    Start([Start: Create Selling Voucher]) --> A[Navigate to Material Management]
    A --> B[Click Add Selling Voucher]
    B --> C[Modal Opens]
    C --> D[Select Store Main or Sub]
    D --> E{Store Type?}
    E -->|Main Store| E1[Get Items from Purchase Orders]
    E -->|Sub-Store| E2[Get Items from Allocations]
    E1 --> F
    E2 --> F
    
    F[Select Payment Type]
    F --> G{Payment Type?}
    G -->|Cash| H[Payment = 0]
    G -->|Credit| I[Payment = 1]
    G -->|Online| J[Payment = 2]
    H --> K
    I --> K
    J --> K
    
    K[Select Client Type]
    K --> L{Client Type?}
    L -->|Employee| M[Client Type = 1]
    L -->|OT| N[Client Type = 2]
    L -->|Course| O[Client Type = 3]
    L -->|Other| P[Client Type = 4]
    
    M --> M1[Load Employee List]
    M1 --> M2[Select Employee]
    M2 --> Q
    
    N --> N1[Load Course List]
    N1 --> N2[Select Course]
    N2 --> N3[AJAX: Load Students by Course]
    N3 --> N4[Select Student]
    N4 --> Q
    
    O --> O1[Enter Client Name Manually]
    O1 --> Q
    
    P --> P1[Load Client Type Master]
    P1 --> P2[Select Client]
    P2 --> Q
    
    Q[Enter Issue Date]
    Q --> R[Load Available Items for Store]
    R --> S[Display Items with Quantities]
    S --> T[Add Item Row]
    
    T --> U[Select Item]
    U --> V[Show Available Quantity]
    V --> W[Enter Issue Quantity]
    W --> X{Quantity <= Available?}
    X -->|No| Y[Error: Insufficient Stock]
    Y --> W
    X -->|Yes| Z[Enter Rate]
    Z --> AA[Auto-calculate Amount]
    AA --> AB{Add More Items?}
    AB -->|Yes| T
    AB -->|No| AC[Calculate Total Amount]
    
    AC --> AD[Enter Remarks Optional]
    AD --> AE[Click Create Button]
    AE --> AF[Validate All Fields]
    AF --> AG{Valid?}
    AG -->|No| AH[Show Errors]
    AH --> AF
    AG -->|Yes| AI[Start Transaction]
    
    AI --> AJ[Create Kitchen Issue Master]
    AJ --> AK[Status = Approved Default]
    AK --> AL[Kitchen Issue Type = 1]
    AL --> AM[Loop Through Items]
    AM --> AN[Create Kitchen Issue Item]
    AN --> AO[Store Item Details]
    AO --> AP{More Items?}
    AP -->|Yes| AM
    AP -->|No| AQ[Commit Transaction]
    
    AQ --> AR[Close Modal]
    AR --> AS[Success Message]
    AS --> AT[Refresh List]
    AT --> AU{Send for Approval?}
    AU -->|Yes| AV[Change Status to Processing]
    AV --> AW[Notify Approver]
    AW --> End1([Sent for Approval])
    AU -->|No| End2([Voucher Created])
    
    style Start fill:#e3f2fd
    style End1 fill:#fff3e0
    style End2 fill:#c8e6c9
    style Y fill:#ffcdd2
    style AH fill:#ffcdd2
    style AK fill:#fff9c4
    style AQ fill:#fff9c4
```

### 5.2 Client Type Selection Detail

```mermaid
flowchart LR
    A[Select Client Type] --> B{Type?}
    
    B -->|Employee| C[Employee Flow]
    C --> C1[Search Employee Master]
    C1 --> C2[Filter by Status Active]
    C2 --> C3[Display Full Name]
    C3 --> C4[Select Employee]
    C4 --> Z[Proceed to Items]
    
    B -->|OT Student| D[OT Flow]
    D --> D1[Load Active Courses]
    D1 --> D2[course.end_date >= today OR NULL]
    D2 --> D3[Select Course]
    D3 --> D4[AJAX Call: getStudentsByCourse]
    D4 --> D5[JOIN student_master_course_map]
    D5 --> D6[Get Students for Course]
    D6 --> D7[Display Name + OT Code]
    D7 --> D8[Select Student]
    D8 --> Z
    
    B -->|Course| E[Course Flow]
    E --> E1[Text Input]
    E1 --> E2[Enter Client Name]
    E2 --> E3[Optional: Select from Course Master]
    E3 --> Z
    
    B -->|Other| F[Other Flow]
    F --> F1[Load Client Type Master]
    F1 --> F2[Filter by client_type = other]
    F2 --> F3[Display Client Names]
    F3 --> F4[Select Client]
    F4 --> Z
    
    style A fill:#e3f2fd
    style Z fill:#c8e6c9
    style C4 fill:#fff9c4
    style D8 fill:#fff9c4
    style E2 fill:#fff9c4
    style F4 fill:#fff9c4
```

---

## 6. Approval Workflow

```mermaid
flowchart TD
    Start([Start: Material Request Created]) --> A{Auto-Approved?}
    A -->|Yes| End1([No Approval Needed])
    A -->|No| B[User Clicks Send for Approval]
    
    B --> C[Update Status to Processing]
    C --> D[Status = 1]
    D --> E[Set Modified By]
    E --> F[Save Changes]
    F --> G[Trigger Notification]
    G --> H[Notify Approver Role Users]
    H --> I[Approver Receives Notification]
    
    I --> J[Approver Logs In]
    J --> K[Navigate to Material Management Approvals]
    K --> L[View Pending Requests]
    L --> M[Filter: Status = Processing]
    M --> N[Display List of Requests]
    N --> O[Click on Request]
    O --> P[View Details Modal]
    
    P --> Q[Review Requested Items]
    Q --> R[Check Quantities]
    R --> S[Verify Client Information]
    S --> T[Check Available Stock]
    T --> U{Decision?}
    
    U -->|Approve| V[Click Approve Button]
    V --> W[Enter Approval Remarks Optional]
    W --> X[Confirm Approval]
    X --> Y[Update Status = Approved]
    Y --> Z[Status = 2]
    Z --> AA[Set Approved By]
    AA --> AB[Set Approved At]
    AB --> AC[Save Changes]
    AC --> AD[Trigger Approval Notification]
    AD --> AE[Notify Requester]
    AE --> AF[Update Inventory]
    AF --> AG[Deduct Items from Store]
    AG --> End2([Approved & Processed])
    
    U -->|Reject| AH[Click Reject Button]
    AH --> AI[Enter Rejection Reason Required]
    AI --> AJ{Reason Provided?}
    AJ -->|No| AI
    AJ -->|Yes| AK[Confirm Rejection]
    AK --> AL[Update Status = Rejected]
    AL --> AM[Status = 3]
    AM --> AN[Store Rejection Reason]
    AN --> AO[Save Changes]
    AO --> AP[Trigger Rejection Notification]
    AP --> AQ[Notify Requester]
    AQ --> AR[No Inventory Changes]
    AR --> End3([Rejected])
    
    style Start fill:#e3f2fd
    style End1 fill:#c8e6c9
    style End2 fill:#c8e6c9
    style End3 fill:#ffcdd2
    style Y fill:#fff9c4
    style AG fill:#fff9c4
    style AL fill:#ffcdd2
```

---

## 7. Billing & Payment Flow

```mermaid
flowchart TD
    Start([Start: Process Employee Bills]) --> A[Navigate to Process Mess Bills]
    A --> B[System Loads Default Date Range]
    B --> C[Current Month: First Day to Last Day]
    C --> D{Change Date Range?}
    D -->|Yes| E[Select Custom From Date]
    E --> F[Select Custom To Date]
    F --> G
    D -->|No| G[Click Filter/Search]
    
    G --> H[Backend Query Execution]
    H --> I[Query sv_date_range_reports]
    I --> J[Filter: client_type_slug = employee]
    J --> K[Filter: issue_date BETWEEN dates]
    K --> L[Join with client_types, stores]
    L --> M[Group by Employee]
    M --> N[Calculate Total Amount per Employee]
    N --> O[Return Results to Frontend]
    
    O --> P[Display Bill List]
    P --> Q[Show Columns: Name, Invoice, Amount, Payment Type, Status]
    Q --> R{Search by Name?}
    R -->|Yes| S[Enter Search Term]
    S --> T[Filter Results]
    T --> U
    R -->|No| U[View Bill List]
    
    U --> V[Select Employee Bill]
    V --> W{What Action?}
    
    W -->|View Details| X[Click View Icon]
    X --> Y[Modal: Show Bill Details]
    Y --> Z[Display Items, Quantities, Rates]
    Z --> AA[Show Total Amount]
    AA --> U
    
    W -->|Generate Invoice| AB[Click Generate Invoice]
    AB --> AC[AJAX Call to Backend]
    AC --> AD[Create Invoice Record]
    AD --> AE[Generate Invoice PDF Optional]
    AE --> AF[Send Email Notification]
    AF --> AG[Success Message]
    AG --> U
    
    W -->|Generate Payment| AH[Click Generate Payment]
    AH --> AI{Bill Already Paid?}
    AI -->|Yes| AJ[Error: Already Paid]
    AJ --> U
    AI -->|No| AK[AJAX Call to Backend]
    AK --> AL[Update Bill Status]
    AL --> AM[Status = 2 Paid]
    AM --> AN[Set Paid Date = Today]
    AN --> AO[Save Changes]
    AO --> AP[Create Payment Record]
    AP --> AQ[Send Payment Notification]
    AQ --> AR[Email: Payment Confirmation]
    AR --> AS[Success Message]
    AS --> AT[Refresh Bill List]
    AT --> AU[Bill Shows as Paid]
    AU --> U
    
    W -->|Print Receipt| AV[Click Print Receipt]
    AV --> AW[Generate Receipt View]
    AW --> AX[Format: Employee Details]
    AX --> AY[Format: Items List]
    AY --> AZ[Format: Total Amount]
    AZ --> BA[Format: Payment Details]
    BA --> BB[Open Print Dialog]
    BB --> BC[Print or Save as PDF]
    BC --> U
    
    W -->|Done| End([Billing Complete])
    
    style Start fill:#e3f2fd
    style End fill:#c8e6c9
    style AM fill:#fff9c4
    style AO fill:#fff9c4
    style AJ fill:#ffcdd2
```

---

## 8. Client Type Selection Flow

```mermaid
stateDiagram-v2
    [*] --> SelectClientType
    
    SelectClientType --> EmployeeFlow: Client Type = Employee
    SelectClientType --> OTFlow: Client Type = OT
    SelectClientType --> CourseFlow: Client Type = Course
    SelectClientType --> OtherFlow: Client Type = Other
    
    state EmployeeFlow {
        [*] --> LoadEmployees
        LoadEmployees --> FilterActive: WHERE status = 1
        FilterActive --> ShowList: Display full_name
        ShowList --> SelectEmployee
        SelectEmployee --> StoreClientId: client_id = employee.pk
        StoreClientId --> [*]
    }
    
    state OTFlow {
        [*] --> LoadCourses
        LoadCourses --> FilterActiveCourses: WHERE active_inactive = 1
        FilterActiveCourses --> ShowCourseList
        ShowCourseList --> SelectCourse
        SelectCourse --> AjaxLoadStudents: AJAX getStudentsByCourse
        AjaxLoadStudents --> JoinStudentCourse: JOIN student_master_course_map
        JoinStudentCourse --> FilterByCourse: WHERE course_master_pk = X
        FilterByCourse --> ShowStudentList: Display name + OT code
        ShowStudentList --> SelectStudent
        SelectStudent --> StoreStudentData: client_id = student.pk
        StoreStudentData --> StoreName: client_name = student.display_name
        StoreName --> [*]
    }
    
    state CourseFlow {
        [*] --> ShowTextInput
        ShowTextInput --> EnterName: Manual entry required
        EnterName --> ValidateName: Check not empty
        ValidateName --> StoreCourseData: client_name = entered value
        StoreCourseData --> [*]
    }
    
    state OtherFlow {
        [*] --> LoadClientTypes
        LoadClientTypes --> FilterOther: WHERE client_type = 'other'
        FilterOther --> ShowClientList
        ShowClientList --> SelectClient
        SelectClient --> StoreClientTypeData: client_type_pk = client_types.id
        StoreClientTypeData --> StoreOtherName: client_name = client_types.client_name
        StoreOtherName --> [*]
    }
    
    EmployeeFlow --> ProceedToItems
    OTFlow --> ProceedToItems
    CourseFlow --> ProceedToItems
    OtherFlow --> ProceedToItems
    
    ProceedToItems --> [*]
```

---

## 9. Data Flow Architecture

```mermaid
graph TB
    subgraph "Frontend Layer"
        UI[User Interface<br/>Blade Templates]
        JS[JavaScript<br/>jQuery/AJAX]
        DT[DataTables]
    end
    
    subgraph "Application Layer"
        Routes[Routes<br/>web.php]
        Controllers[Controllers<br/>Mess Namespace]
        Middleware[Middleware<br/>Auth, Permissions]
    end
    
    subgraph "Business Logic Layer"
        Models[Models<br/>Eloquent ORM]
        Validation[Validation Rules]
        Events[Events & Listeners]
    end
    
    subgraph "Data Layer"
        DB[(MySQL Database)]
        Cache[(Cache<br/>Redis/File)]
    end
    
    subgraph "External Systems"
        Email[Email Service]
        SMS[SMS Service]
        Notifications[Notification System]
    end
    
    UI --> JS
    JS --> Routes
    UI --> DT
    DT --> JS
    
    Routes --> Middleware
    Middleware --> Controllers
    Controllers --> Models
    Controllers --> Validation
    Models --> DB
    
    Controllers --> Events
    Events --> Notifications
    Events --> Email
    Events --> SMS
    
    Models --> Cache
    Cache --> Models
    
    DB --> Models
    
    style UI fill:#e3f2fd
    style DB fill:#fff9c4
    style Controllers fill:#c8e6c9
```

---

## 10. Complete Purchase to Sale Cycle

```mermaid
sequenceDiagram
    actor Admin
    participant PO as Purchase Order System
    participant Store as Main Store
    participant Alloc as Store Allocation
    participant SubStore as Sub-Store
    participant Voucher as Selling Voucher
    participant Bill as Billing System
    actor Customer
    
    Admin->>PO: Create Purchase Order
    PO->>PO: Select Vendor & Items
    PO->>PO: Enter Quantities & Prices
    PO->>PO: Calculate Total
    PO->>Store: Add Items to Inventory
    Store-->>Admin: PO Created (Status: Approved)
    
    Admin->>Alloc: Create Store Allocation
    Alloc->>Store: Check Available Quantities
    Store-->>Alloc: Return Available Stock
    Alloc->>Alloc: Select Items & Quantities
    Alloc->>Store: Deduct from Main Store
    Alloc->>SubStore: Add to Sub-Store
    SubStore-->>Admin: Allocation Complete
    
    Customer->>Voucher: Request Items
    Voucher->>SubStore: Check Available Items
    SubStore-->>Voucher: Return Available Stock
    Voucher->>Voucher: Select Items & Quantities
    Voucher->>Voucher: Calculate Amount
    Voucher->>SubStore: Deduct from Sub-Store
    Voucher-->>Customer: Issue Items (Voucher Created)
    
    Admin->>Bill: Generate Monthly Bills
    Bill->>Voucher: Query Transactions by Date
    Voucher-->>Bill: Return Transactions
    Bill->>Bill: Group by Customer
    Bill->>Bill: Calculate Total per Customer
    Bill-->>Admin: Bills Generated
    
    Admin->>Bill: Process Payment
    Bill->>Bill: Mark as Paid
    Bill->>Customer: Send Payment Notification
    Customer-->>Bill: Payment Confirmed
```

---

## 11. Inventory Management Flow

```mermaid
graph LR
    subgraph "Inbound Flow"
        A[Purchase Order] --> B[Vendor Delivers]
        B --> C[Goods Receipt]
        C --> D[Quality Check]
        D --> E{Quality OK?}
        E -->|Yes| F[Update Main Store +Qty]
        E -->|No| G[Return to Vendor]
        G --> A
    end
    
    subgraph "Internal Transfer"
        F --> H[Store Allocation Decision]
        H --> I[Select Sub-Store]
        I --> J[Allocate Quantities]
        J --> K[Update Main Store -Qty]
        K --> L[Update Sub-Store +Qty]
    end
    
    subgraph "Outbound Flow"
        L --> M[Customer Request]
        M --> N[Create Selling Voucher]
        N --> O[Check Sub-Store Stock]
        O --> P{Stock Available?}
        P -->|Yes| Q[Issue Items]
        Q --> R[Update Sub-Store -Qty]
        R --> S[Generate Bill]
        P -->|No| T[Stock Out Alert]
        T --> H
    end
    
    subgraph "Return Flow"
        S --> U{Items Returned?}
        U -->|Yes| V[Record Return]
        V --> W[Update Sub-Store +Qty]
        W --> X[Adjust Bill]
        U -->|No| Y[Transaction Complete]
    end
    
    F -.->|Low Stock Alert| Z[Reorder Point]
    Z -.-> A
    
    style F fill:#c8e6c9
    style L fill:#c8e6c9
    style R fill:#fff9c4
    style T fill:#ffcdd2
    style G fill:#ffcdd2
```

---

## 12. Permission Check Flow

```mermaid
flowchart TD
    Start([User Action]) --> A[Request Sent to Route]
    A --> B[Middleware: Check Authentication]
    B --> C{User Authenticated?}
    C -->|No| D[Redirect to Login]
    D --> End1([Access Denied])
    
    C -->|Yes| E[Load User Roles]
    E --> F[Load User Permissions]
    F --> G[Get Required Permission for Action]
    G --> H{Permission Check}
    
    H -->|Via Role| I[Check Role Permissions]
    I --> J{Role Has Permission?}
    J -->|Yes| K[Allow Access]
    J -->|No| L[Check User-Specific Permissions]
    
    H -->|Direct| L
    L --> M{User Has Permission?}
    M -->|Yes| K
    M -->|No| N[Access Denied]
    N --> O[Log Unauthorized Attempt]
    O --> P[Show Error: No Permission]
    P --> End2([Access Denied])
    
    K --> Q[Execute Controller Method]
    Q --> R[Return Response]
    R --> End3([Success])
    
    style Start fill:#e3f2fd
    style End1 fill:#ffcdd2
    style End2 fill:#ffcdd2
    style End3 fill:#c8e6c9
    style K fill:#c8e6c9
    style N fill:#ffcdd2
```

---

## 13. Report Generation Flow

```mermaid
flowchart TD
    Start([User Requests Report]) --> A[Navigate to Reports]
    A --> B{Select Report Type}
    
    B -->|Items List| C1[Items List Report]
    B -->|Mess Summary| C2[Mess Summary Report]
    B -->|Category Material| C3[Category Material Report]
    B -->|Pending Orders| C4[Pending Orders Report]
    B -->|Purchase Orders| C5[Purchase Orders Report]
    B -->|Stock Issues| C6[Stock Issue Report]
    B -->|Financial| C7[Financial Reports]
    
    C1 --> D1[Query All Items]
    D1 --> D2[Join Categories]
    D2 --> D3[Join Stock Data]
    D3 --> E
    
    C2 --> D4[Query Transactions]
    D4 --> D5[Aggregate by Period]
    D5 --> D6[Calculate Summaries]
    D6 --> E
    
    C3 --> D7[Query by Category]
    D7 --> D8[Sum Quantities]
    D8 --> D9[Calculate Costs]
    D9 --> E
    
    C4 --> D10[Query POs]
    D10 --> D11[Filter Status != Approved]
    D11 --> D12[Join Vendors]
    D12 --> E
    
    C5 --> D13[Query POs]
    D13 --> D14[Join Items, Vendors]
    D14 --> D15[Filter by Date Range]
    D15 --> E
    
    C6 --> D16[Query Kitchen Issues]
    D16 --> D17[Join Items, Clients]
    D17 --> D18[Filter by Date Range]
    D18 --> E
    
    C7 --> D19[Query Bills & Payments]
    D19 --> D20[Calculate Totals]
    D20 --> D21[Calculate Outstanding]
    D21 --> E
    
    E[Format Data for Display]
    E --> F{Export Format?}
    F -->|Screen| G1[Render HTML Table]
    F -->|Excel| G2[Generate XLSX]
    F -->|PDF| G3[Generate PDF]
    F -->|CSV| G4[Generate CSV]
    
    G1 --> H[Display Report]
    G2 --> I[Download File]
    G3 --> I
    G4 --> I
    
    H --> J{Apply Filters?}
    J -->|Yes| K[User Selects Filters]
    K --> L[Re-run Query]
    L --> E
    J -->|No| End([Report Complete])
    
    I --> End
    
    style Start fill:#e3f2fd
    style End fill:#c8e6c9
    style H fill:#fff9c4
```

---

## 14. Error Handling Flow

```mermaid
flowchart TD
    Start([User Action]) --> A[Request Sent]
    A --> B[Try Block Begins]
    B --> C[Execute Business Logic]
    C --> D{Error Occurred?}
    
    D -->|No| E[Success Response]
    E --> F[Return JSON/Redirect]
    F --> G[Show Success Message]
    G --> End1([Success])
    
    D -->|Yes| H{Error Type?}
    
    H -->|Validation Error| I[Catch ValidationException]
    I --> J[Get Validation Messages]
    J --> K[Return with Errors]
    K --> L[Display Field-Level Errors]
    L --> M[User Corrects Input]
    M --> A
    
    H -->|Database Error| N[Catch QueryException]
    N --> O[Rollback Transaction]
    O --> P[Log Error Details]
    P --> Q[Show Generic Error Message]
    Q --> R[Don't Expose DB Details]
    R --> End2([Error Handled])
    
    H -->|Authorization Error| S[Catch AuthorizationException]
    S --> T[Log Unauthorized Access]
    T --> U[Show 403 Error Page]
    U --> End3([Access Denied])
    
    H -->|Not Found| V[Catch ModelNotFoundException]
    V --> W[Log Missing Resource]
    W --> X[Show 404 Error Page]
    X --> End4([Not Found])
    
    H -->|General Exception| Y[Catch Exception]
    Y --> Z[Log Full Stack Trace]
    Z --> AA[Rollback if in Transaction]
    AA --> AB[Show User-Friendly Message]
    AB --> AC[Offer Retry Option]
    AC --> AD{Retry?}
    AD -->|Yes| A
    AD -->|No| End5([Error Handled])
    
    style Start fill:#e3f2fd
    style End1 fill:#c8e6c9
    style End2 fill:#ffcdd2
    style End3 fill:#ffcdd2
    style End4 fill:#ffcdd2
    style End5 fill:#ffcdd2
    style O fill:#ff9800
    style AA fill:#ff9800
```

---

## 15. System Integration Diagram

```mermaid
graph TB
    subgraph "Mess Module Core"
        MM[Mess Module]
    end
    
    subgraph "Internal Integrations"
        EM[Employee Master]
        SM[Student Master]
        CM[Course Master]
        FM[Faculty Master]
        DM[Department Master]
        UM[User Management]
        RM[Role Management]
    end
    
    subgraph "External Services"
        ES[Email Service<br/>SMTP]
        SS[SMS Gateway]
        PS[Payment Gateway]
        AS[Accounting System]
    end
    
    subgraph "Reporting & Analytics"
        RS[Report Server]
        DS[Dashboard Service]
        EX[Export Service]
    end
    
    MM --> EM
    MM --> SM
    MM --> CM
    MM --> FM
    MM --> DM
    MM --> UM
    MM --> RM
    
    EM -.->|Employee Data| MM
    SM -.->|Student Data| MM
    CM -.->|Course Data| MM
    
    MM --> ES
    MM --> SS
    MM --> PS
    MM -.->|Financial Data| AS
    
    MM --> RS
    MM --> DS
    MM --> EX
    
    RS -.->|Report Data| MM
    DS -.->|Analytics Data| MM
    
    style MM fill:#4caf50,color:#fff
    style EM fill:#2196f3,color:#fff
    style SM fill:#2196f3,color:#fff
    style CM fill:#2196f3,color:#fff
    style ES fill:#ff9800,color:#fff
    style SS fill:#ff9800,color:#fff
    style PS fill:#ff9800,color:#fff
    style AS fill:#ff9800,color:#fff
    style RS fill:#9c27b0,color:#fff
    style DS fill:#9c27b0,color:#fff
```

---

## Conclusion

These flowcharts provide a visual representation of all major workflows in the Mess Module. They can be rendered using Mermaid-compatible tools for presentations, documentation, or training purposes.

For the complete textual documentation, refer to `MESS_MODULE_WORKFLOW.md`.

---

**Document End**

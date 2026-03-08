# LOWER DIVISION CLERK ko kis eligibility (Unit Sub Type) wala house allot ho sakta hai – DB check

## Revert

Allot modal wapas eligibility filter ke saath hai: **employee_pk** pass hota hai, isliye sirf **us employee ki eligibility** (salary grade → unit sub type) wale houses hi dropdown mein aate hain.

---

## Concept (short)

1. **Employee** → **payroll_salary_master** (salary grade) → **salary_grade_master**
2. **estate_eligibility_mapping** = **salary_grade_master_pk** → **estate_unit_sub_type_master_pk** (aur unit type)
3. **Define House** mein house banate waqt jo **Unit Sub Type** select karte ho, wahi **eligibility** hai. Agar employee ki salary grade se map hone wala **unit sub type** yahi hai, toh allot ke time woh house dropdown mein dikhega.

Toh **LOWER DIVISION CLERK** (designation) → koi bhi employee jiska designation LDC hai → uski **salary grade** → **estate_eligibility_mapping** se **estate_unit_sub_type_master_pk** → wahi **Unit Sub Type** wala house Define House mein banao, toh allot pe show hoga.

---

## DB mein check karne ke liye SQL

### Option A: Designation se (LOWER DIVISION CLERK)

Designation = **LOWER DIVISION CLERK** wale employees ki salary grade se linked **Unit Sub Type** nikalna:

```sql
-- Use salary_grade_pk if payroll_salary_master has that column; else use salary_grade_master_pk
SELECT DISTINCT
    ust.pk AS unit_sub_type_pk,
    ust.unit_sub_type AS unit_sub_type_name,
    sg.pk AS salary_grade_pk,
    sg.salary_grade AS salary_grade_name
FROM designation_master d
JOIN employee_master em ON em.designation_master_pk = d.pk
JOIN payroll_salary_master ps ON ps.employee_master_pk = em.pk
JOIN salary_grade_master sg ON sg.pk = ps.salary_grade_pk
JOIN estate_eligibility_mapping eem ON eem.salary_grade_master_pk = sg.pk
JOIN estate_unit_sub_type_master ust ON ust.pk = eem.estate_unit_sub_type_master_pk
WHERE d.designation_name = 'LOWER DIVISION CLERK';
```

Agar `payroll_salary_master` mein column **salary_grade_master_pk** hai (aur **salary_grade_pk** nahi), toh last two lines replace karo:

```sql
JOIN salary_grade_master sg ON sg.pk = ps.salary_grade_master_pk
```

Agar aapke system mein **employee_master** se link **pk_old** se hai (payroll_salary_master.employee_master_pk = employee_master.pk_old), toh join ye use karo:

```sql
JOIN employee_master em ON em.pk_old = d.pk  -- wrong, see below
```

Sahi join: designation se employee, phir employee ki **woh column** jo payroll_salary_master use karti hai:

```sql
-- If payroll uses employee_master.pk:
JOIN payroll_salary_master ps ON ps.employee_master_pk = em.pk

-- If payroll uses employee_master.pk_old:
JOIN payroll_salary_master ps ON ps.employee_master_pk = em.pk_old
```

Pehle ye check karo:

```sql
-- Check employee_master columns
SHOW COLUMNS FROM employee_master LIKE 'pk%';

-- Check payroll_salary_master salary column name
SHOW COLUMNS FROM payroll_salary_master LIKE '%salary_grade%';
```

---

### Option B: Direct Eligibility Criteria list (Estate module)

Estate **Eligibility – Criteria** screen jaisa data (Salary Grade ↔ Unit Sub Type):

```sql
SELECT
    sg.pk AS salary_grade_pk,
    sg.salary_grade,
    ust.pk AS estate_unit_sub_type_master_pk,
    ust.unit_sub_type
FROM estate_eligibility_mapping eem
JOIN salary_grade_master sg ON sg.pk = eem.salary_grade_master_pk
JOIN estate_unit_sub_type_master ust ON ust.pk = eem.estate_unit_sub_type_master_pk
ORDER BY sg.salary_grade, ust.unit_sub_type;
```

Isse dikh jayega: **kaun sa salary grade** → **kaun sa unit sub type**. LDC jis salary grade pe hai, us row mein jo **estate_unit_sub_type_master_pk** / **unit_sub_type** hai, **Define House** mein wahi Unit Sub Type choose karna hai.

---

### Option C: Specific employee (e.g. UMESH KUMAR CHAUDHARY) ke liye

Agar aapko pata hai employee name ya employee id:

```sql
SELECT DISTINCT
    ust.pk AS unit_sub_type_pk,
    ust.unit_sub_type
FROM employee_master em
JOIN payroll_salary_master ps ON ps.employee_master_pk = em.pk   -- or em.pk_old
JOIN estate_eligibility_mapping eem ON eem.salary_grade_master_pk = ps.salary_grade_pk  -- or ps.salary_grade_master_pk
JOIN estate_unit_sub_type_master ust ON ust.pk = eem.estate_unit_sub_type_master_pk
WHERE CONCAT(em.first_name, ' ', em.last_name) LIKE '%UMESH%CHAUDHARY%'
   OR em.emp_id = 'YOUR_EMPLOYEE_ID';
```

Result: **unit_sub_type_pk** aur **unit_sub_type** — yahi Define House mein use karo.

---

## Define House mein kya karna hai

1. **Estate / Campus** select karo (e.g. Behind Karamsh).
2. **Unit Type** select karo (e.g. Residential).
3. **Building** select karo (e.g. Alakhnanda Awa).
4. **Unit Sub Type** **woh select karo jo upar wale SQL se aaya** (e.g. jo LDC ki salary grade se map hai — jaise LAUNDROMATI agar wahi eligibility hai).
5. House No. daalo, Status **Vacant** rakho, Save karo.

Iske baad **HAC Approved → Allot House** mein same Estate, Unit Type, Building, Unit Sub Type select karoge toh ye naya house **House No.** dropdown mein dikhega (eligibility filter ab bhi on hai, isliye sirf eligible unit sub type wala hi aayega).

---

## Agar SQL se kuch na aaye

- **designation_master** mein `LOWER DIVISION CLERK` ka row hai?
- **employee_master** mein koi employee `designation_master_pk` se LDC link hai?
- **payroll_salary_master** mein us employee ka row hai aur **salary_grade_pk** (ya **salary_grade_master_pk**) set hai?
- **estate_eligibility_mapping** mein us **salary_grade_master_pk** ke liye **estate_unit_sub_type_master_pk** set hai?

Agar kahi link missing hai (payroll ya eligibility mapping), toh **Estate → Eligibility – Criteria** mein us salary grade ke liye woh Unit Sub Type add karo; phir Define House mein wahi Unit Sub Type use karke house banao.

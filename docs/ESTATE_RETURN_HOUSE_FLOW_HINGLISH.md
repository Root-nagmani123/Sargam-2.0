# Return House Flow (Hinglish) — GANESH SHANKAR MISHRA jaisa case

Jab aapne **possession bana diya** (Possession Details create) aur ab **Return House** karna hai (ghar vacate record karna), flow yeh hai.

---

## 1. LBSNAA employee (Possession Details wale) — Return House

**GANESH SHANKAR MISHRA** ka possession **estate_possession_details** mein hai (LBSNAA flow: Request For Estate → HAC Approved → Allot → Possession Details create).

### Step-by-step flow

1. **Admin / Estate** login karke **Estate** section mein jao.
2. **Return House** page kholo:  
   **Menu** → Estate → **Return House**  
   (URL: `admin/estate/return-house`)
3. Page pe **upar wala card** dikhega: **"LBSNAA employees — Pending Return"**.
4. Is table mein **sab LBSNAA employees** dikhenge jinke paas possession hai aur **abhi tak return nahi kiya** (return_home_status = 0).
5. **GANESH SHANKAR MISHRA** yahan list mein hoga (Name, Request ID, House No., Allotment Date, Possession Date).
6. Usi row mein **"Mark Return"** button dabao.
7. Confirm modal aayega: **"Are you sure you want to mark this house as returned?"** → **Return House** dabao.
8. Backend yeh karega:
   - **estate_possession_details**: us row ka `return_home_status = 1` (house return ho chuka).
   - **estate_house_master**: us house ka `used_home_status = 0` (house ab vacant).
   - **estate_home_request_details**: us employee ka `current_alot = NULL` (ab koi house allotted nahi).
9. Success message ke baad **LBSNAA pending return** table refresh ho jayegi — GANESH ka row list se hat jayega (kyunki ab return ho chuka).

**Summary:** Return House page → LBSNAA section → GANESH select → **Mark Return** → Confirm → done. Change Request detail alag flow hai (neeche).

---

## 2. Change Request Detail wala flow — kab use hota hai?

**Change Request** = employee **already allotted** hai, ab **dusra ghar chahta hai** (shift).  
**Return House** = employee ne **ghar chhod diya** (vacate), sirf return record karna.

| Case | Kya karna hai | Kahan jana hai |
|------|----------------|-----------------|
| Employee ne ghar **chhod diya** (transfer / resign / end of tenure) | Return record karna | **Return House** → LBSNAA section → **Mark Return** |
| Employee **dusra ghar chahta hai** (shift) | Change Request raise karna, phir approve | **Change Request Details** / **HAC Approved** (change request approve) |

- **Return House** = sirf “ghar return ho gaya” mark karna (current flow jo ab LBSNAA ke liye add kiya hai).
- **Change Request Details** = naya ghar maangna; approve hone par purana ghar **automatic** return mark ho jata hai (alag se Return House dabane ki zarurat nahi).

Agar GANESH **dusra house chahta hai** (change request):
1. **Change Request Details** form se naya house select karke request save karo (jab `current_alot` set ho).
2. **HAC Approved** page pe us change request ko **Approve** karo.
3. Approve par system khud purane house ko return + vacant mark kar deta hai; alag se Return House **Mark Return** nahi dabana padta.

Agar GANESH ne **sirf ghar chhod diya** (shift / resign):
1. **Return House** page → **LBSNAA employees — Pending Return**.
2. GANESH SHANKAR MISHRA pe **Mark Return** → Confirm.
3. Ho gaya — house vacant, `current_alot` clear.

---

## 3. Ek line mein

| Tumhara case | Flow |
|--------------|------|
| Possession bana di (e.g. GANESH), ab **return record** karna hai (ghar chhod diya) | **Return House** → LBSNAA section → **Mark Return** |
| Employee **dusra ghar chahta hai** | **Change Request Details** + **HAC Approved** pe Approve (return automatic) |

---

*Document: Sargam 2.0 Estate — Return House & Change Request flow.*

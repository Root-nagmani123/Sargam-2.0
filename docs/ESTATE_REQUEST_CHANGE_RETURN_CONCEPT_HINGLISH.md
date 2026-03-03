# Estate Module — Request for House, Change Request & Return House (Concept — Hinglish)

Yeh document samjhata hai ki **Request for House**, **Change Request Details** aur **Return House** kaise chalta hai aur user data kaise dalta hai.

---

## 1. REQUEST FOR HOUSE — Kaise chalta hai?

**Request for House** matlab: **Employee pehli baar quarter/ghar maangta hai** (abhi usko koi house allotted nahi hai).

### Flow (short):
1. **Employee** login karke **Request For Estate** page pe jata hai.
2. **Add Estate Request** dabata hai → form khulta hai.
3. Form mein **Employee select** karta hai (ya naya enter), **Request Date**, **Eligibility Type**, **Remarks** etc. bharta hai.
4. **Save** dabane par system **`estate_home_request_details`** table mein **ek naya row insert** karta hai.
5. Us row mein:
   - `req_id` = auto (e.g. REQ-2024-001)
   - `current_alot` = **NULL** (abhi koi ghar allot nahi hua)
   - `status` = 0 (Pending), `hac_status` = 0, `change_status` = 0

**Summary:** Request for House = **naya ghar maangna**. Ye record **master** hai — isi ke upar baad mein allotment, possession, change request sab link hota hai.

---

## 2. CHANGE REQUEST DETAILS — Kaise chalta hai?

**Change Request** matlab: **Employee ko pehle se ek house allotted hai**, ab wo **dusra house chahta hai** (shift karna chahta hai).

### Rule (bahut important):
- Change Request **sirf tab** allow hota hai jab **`current_alot` empty nahi hai** (employee ke paas already koi house number set hai, e.g. H-101).
- Agar `current_alot` **NULL** hai to Change Request option **band / disabled** hona chahiye.

### Flow (short):
1. Employee **already allotted** hai (e.g. H-101) — isliye **Request for House** wale record mein `current_alot = 'H-101'` set hai.
2. Employee **Change Request** open karta hai (form load hone ke liye **current_alot** parameter zaroori hai).
3. Form pe:
   - **Current House** = H-101 (read-only)
   - **New Campus, Block, House** = dropdown se select (sirf **vacant** houses)
   - **Remarks** = reason likhta hai
4. **Save** par:
   - **`estate_change_home_req_details`** mein **naya row** (change_house_no = naya ghar, remarks, change_req_date, f_status=1, change_ap_dis_status=0).
   - **`estate_home_request_details`** mein **`change_status = 1`** update (matlab “change request raise ho chuka”).

5. **Approval:**
   - Approver **list** mein ye change request dikhta hai (forward chain ya HAC se).
   - **Approve** kiya to:
     - `estate_home_request_details.current_alot` = **naya house** (e.g. H-205)
     - Purana house (H-101) **vacant** mark — `estate_possession_details.return_home_status = 1`, `estate_house_master.used_home_status = 0`
   - **Disapprove** kiya to `current_alot` **same** rehta hai, employee purane house mein hi rehta hai.

**Summary:** Change Request = **allotted employee dusra ghar chahta hai**. System **current_alot** check karta hai; approve par **current_alot** naye house se update + purana ghar return/vacant.

---

## 3. RETURN HOUSE — User kaise dalta hai?

**Return House** = Employee ne ghar **chhod diya** (vacate / transfer / resignation etc.). System ko ye record karna hota hai: **house ab vacant hai**, **current_alot** clear.

### Do tarike se Return House record hota hai:

#### Option A — Automatic (Change Request Approve)
- Jab **Change Request APPROVE** hota hai, system **automatically**:
  - Purane house ke liye **`estate_possession_details.return_home_status = 1`** set karta hai (ghar return ho gaya).
  - **`estate_house_master.used_home_status = 0`** (house vacant).
- Is case mein **user alag se Return House form nahi bharta** — approval ke time hi return process ho jata hai.

#### Option B — Manual (Estate Officer / Staff)
- **Return House** page pe staff jata hai.
- Wahan **list** hoti hai jisme **un employees ka data** hota hai jo **possession** mein hain aur **return_home_status = 0** (abhi return nahi kiya).
- Staff **employee select** karta hai, **return date**, **remarks**, optional **NOC document** upload karta hai.
- **Submit** par:
  - **`estate_possession_other`** (ya possession table) mein **`return_home_status = 1`** update hota hai.
  - House **vacant** mark hota hai (**used_home_status = 0**).
  - Agar **estate_home_request_details** use ho raha ho to **`current_alot = NULL`** bhi set ho sakta hai (flow ke hisaab se).

Sargam 2.0 mein **Return House** flow:
- **Return House** page pe **“Request House”** type modal bhi hai — jahan user **return + naya request** ek saath kar sakta hai (redirect_to = return-house).
- Form se **employee**, **estate**, **building**, **house**, **returning date**, **remarks**, **NOC document** bhar kar submit karte hain.
- Backend **return_home_status = 1** set karta hai aur house ko vacant consider karta hai.

**Summary:** Return House **dalne** ke do tareeke — (1) **Change Request Approve** = automatic return, (2) **Return House page** se staff manually employee select karke return date + remarks/document bhar ke submit karta hai.

---

## 4. Ek line mein relation

| Concept            | Kab use hota hai                          | Main table / column                    |
|--------------------|-------------------------------------------|----------------------------------------|
| Request for House  | Naya ghar maangna (current_alot = NULL)   | `estate_home_request_details` (INSERT) |
| Change Request     | Allotted employee dusra ghar chahta hai   | `estate_change_home_req_details` + `estate_home_request_details.change_status` |
| Return House       | Ghar chhod diya (vacant mark karna)       | `estate_possession_*`.return_home_status = 1, house master vacant |

---

*Yeh document ESTATE_CHANGE_REQUEST_DETAIL.md aur ESTATE_MODULE_FLOW_DIAGRAM.md ke flow ke hisaab se likha gaya hai.*

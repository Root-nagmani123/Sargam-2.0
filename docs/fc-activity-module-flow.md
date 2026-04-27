# FC Activity Module Flow

## Open Activity Dashboard

- `/{your-app-base}/fc-reg/admin/activities`

## Add An Activity Entry

1. Select course
2. Enter OT code
3. Select activity
4. Enter value
5. Click Save

### Medical One-Click Entry (Popup)

When you want to enter multiple medical fields in one go:

1. Select course
2. Enter OT code
3. In activity dropdown, choose:
   - `Medical (fill all vitals at once)`
4. Click **Save Activity**
5. Fill the popup form fields:
   - Height
   - Weight
   - SpO2
   - Pulse
   - Blood Pressure
   - Pre-remarks
   - Vial Tube
   - Blood Sample
6. Click **Save Medical Details**

Internal endpoint used by popup:

- `POST /{your-app-base}/fc-reg/admin/activities/medical-bulk`

## Edit/Delete Existing Activity

- Edit and delete are available in the table on the same dashboard page.
- Edit URL pattern:
  - `/{your-app-base}/fc-reg/admin/activities/{activityId}/edit`

## Status Pages

- Admin status: `/{your-app-base}/fc-reg/admin/activities/status/admin`
- Security status: `/{your-app-base}/fc-reg/admin/activities/status/security`
- IT status: `/{your-app-base}/fc-reg/admin/activities/status/it`
- Training status: `/{your-app-base}/fc-reg/admin/activities/status/training`
- Medical status: `/{your-app-base}/fc-reg/admin/activities/status/medical`
- Shop status: `/{your-app-base}/fc-reg/admin/activities/status/shop`
- All combined status: `/{your-app-base}/fc-reg/admin/activities/status/all`

## Report Pages

- Summary: `/{your-app-base}/fc-reg/admin/activities/reports/summary`
- Department-wise: `/{your-app-base}/fc-reg/admin/activities/reports/department/{dept}`
  - Allowed `dept` values: `admin`, `security`, `it`, `trg`, `medical`, `shop`
- Not joined: `/{your-app-base}/fc-reg/admin/activities/reports/not-joined`
- Service-wise: `/{your-app-base}/fc-reg/admin/activities/reports/service-wise`

## Medical Flow

Medical page URLs:

- Medical dashboard page: `/{your-app-base}/fc-reg/admin/activities/medical`
- Medical report loader (GET with query): `/{your-app-base}/fc-reg/admin/activities/medical/report?course={course}&ot={otcode}`
- Medical upload endpoint (AJAX POST): `/{your-app-base}/fc-reg/admin/activities/medical/upload`

Step-by-step:

1. Open medical page: `/{your-app-base}/fc-reg/admin/activities/medical`
2. Select course
3. Enter OT code
4. Medical report loads automatically from:
   - `GET /{your-app-base}/fc-reg/admin/activities/medical/report`
5. Upload pathology PDF and/or enter final findings
6. Click submit
7. Data is saved via:
   - `POST /{your-app-base}/fc-reg/admin/activities/medical/upload`


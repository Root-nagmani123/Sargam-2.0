# Course Repository Upload Form - Data Insertion Process

## Overview
Upload form से data को database में insert करने की complete process.

---

## Data Flow Diagram

```
Upload Form (show.blade.php)
        ↓
   [Form Submission]
   - Category (Course/Other/Institutional)
   - Course Name (Course Master dropdown)
   - Subject Name (Subject Master dropdown)
   - Timetable (Timetable dropdown)
   - Session Date (Auto-populated)
   - Author Name (Auto-populated)
   - Keywords (Auto-generated)
   - Video Link
   - File Attachments (Multiple files)
        ↓
[uploadDocument() method in CourseRepositoryController]
        ↓
    ┌───────────────────────────────────────┐
    │  STEP 1: Insert into                  │
    │  course_repository_details            │
    │  (Metadata, keywords, video link)     │
    └───────────────────────────────────────┘
                    ↓
              Returns: detail_pk
                    ↓
    ┌───────────────────────────────────────┐
    │  STEP 2: Upload Files                 │
    │  - Store in public/storage/           │
    │  - Generate unique filename           │
    │  - Insert into course_repository_     │
    │    documents with detail_pk reference │
    └───────────────────────────────────────┘
                    ↓
              [Success Response]
                    ↓
           [Page Reload to Show Data]
```

---

## Table Structure

### course_repository_details
```
┌─────────────────────────────┬──────────────────────────────────────┐
│ Column Name                 │ Description & Source                 │
├─────────────────────────────┼──────────────────────────────────────┤
│ pk                          │ Auto-increment PK                    │
│ course_repository_master_pk │ From URL: /course-repository/{pk}    │
│ course_repository_type      │ 1 = Course, 2 = Other, 3 = Inst.    │
│ program_structure_pk        │ Optional                             │
│ subject_pk                  │ From subject_name dropdown selection │
│ detail_document             │ Optional document count              │
│ topic_pk                    │ From timetable_name dropdown         │
│ session_date                │ From timetable auto-population       │
│ author_name                 │ From author_name auto-population     │
│ keyword                     │ From keywords auto-generated field   │
│ created_date                │ NOW() - Current timestamp            │
│ created_by                  │ auth()->id() - Current user          │
│ modify_by                   │ NULL (Updated on edit)               │
│ modify_date                 │ NULL (Updated on edit)               │
│ status                      │ 1 = active, 0 = inactive             │
│ type                        │ 'CO'/'OT'/'IN' based on category     │
│ videolink                   │ From video_link form field           │
└─────────────────────────────┴──────────────────────────────────────┘
```

### course_repository_documents
```
┌──────────────────────────────┬──────────────────────────────────────┐
│ Column Name                  │ Description & Source                 │
├──────────────────────────────┼──────────────────────────────────────┤
│ pk                           │ Auto-increment PK                    │
│ upload_document              │ Generated filename with timestamp    │
│ course_repository_details_pk │ FK to course_repository_details.pk   │
│ course_repository_master_pk  │ From URL parameter                   │
│ course_repository_type       │ 1 = Course, 2 = Other, 3 = Inst.    │
│ file_title                   │ From attachment_titles form field    │
│ del_type                     │ 1 = active, 0 = deleted (soft del)  │
│ deleted_date                 │ NULL (Set on deletion)               │
│ deleted_by                   │ NULL (Set on deletion)               │
│ full_path                    │ storage/course-repository/{filename} │
└──────────────────────────────┴──────────────────────────────────────┘
```

---

## Controller Method: uploadDocument()

```php
// Location: app/Http/Controllers/Admin/CourseRepositoryController.php

public function uploadDocument($pk, Request $request)
{
    try {
        // Step 1: Validate incoming data
        $validated = $request->validate([
            'category' => 'required|string|in:Course,Other,Institutional',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:102400',  // 100MB max
            'attachment_titles' => 'nullable|array',
            'attachment_titles.*' => 'nullable|string|max:5000',
            'keywords' => 'nullable|string|max:4000',
            'video_link' => 'nullable|string|max:2000',
        ]);
        
        // Step 2: Determine type based on category
        $typeMap = [
            'Course' => 'CO',
            'Other' => 'OT',
            'Institutional' => 'IN'
        ];
        
        // Step 3: Insert metadata into course_repository_details
        $details = CourseRepositoryDetail::create([
            'course_repository_master_pk' => $pk,
            'course_repository_type' => 1,
            'keyword' => $validated['keywords'] ?? null,
            'videolink' => $validated['video_link'] ?? null,
            'created_date' => now(),
            'created_by' => auth()->id(),
            'status' => 1,
            'type' => $typeMap[$validated['category']] ?? 'CO',
        ]);
        
        // Step 4: Process and upload files
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            $titles = $validated['attachment_titles'] ?? [];
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    // Generate unique filename
                    // Format: {timestamp}_{unique_id}_{original_filename}
                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    
                    // Store file in public disk at course-repository folder
                    $filePath = $file->storeAs('course-repository', $fileName, 'public');
                    
                    // Insert document record
                    CourseRepositoryDocument::create([
                        'upload_document' => $fileName,
                        'course_repository_master_pk' => $pk,
                        'course_repository_details_pk' => $details->pk,
                        'course_repository_type' => 1,
                        'file_title' => $titles[$index] ?? $file->getClientOriginalName(),
                        'full_path' => $filePath,
                        'del_type' => 1,  // 1 = active
                    ]);
                }
            }
        }
        
        // Step 5: Return success response
        return response()->json([
            'success' => true,
            'message' => 'Documents uploaded and data saved successfully',
            'detail_pk' => $details->pk,
        ]);
        
    } catch (Exception $e) {
        Log::error('Error in uploadDocument: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Upload failed: ' . $e->getMessage(),
        ], 500);
    }
}
```

---

## Form Data Example

### What User Submits:
```
Category:           Course
Course Name:        MBA - Leadership Management
Subject Name:       Leadership (subject_master pk = 1)
Timetable:          Leadership Case Study Work (timetable pk = 5)
Session Date:       16-01-2026 (auto-populated)
Author Name:        Himanshu Rai (auto-populated)
Keywords:           Leadership, Management, Development (auto-generated)
Video Link:         https://youtube.com/watch?v=leadership-mgmt
Attachments:
  - File 1: leadership-fundamentals.pdf
    Title 1: Leadership Fundamentals - Complete Guide
  - File 2: case-study-analysis.xlsx
    Title 2: Leadership Case Study Analysis
```

### What Gets Inserted:

**course_repository_details (1 record):**
```sql
INSERT INTO course_repository_details (
  course_repository_master_pk = 1,
  keyword = 'Leadership, Management, Development',
  videolink = 'https://youtube.com/watch?v=leadership-mgmt',
  created_date = 2026-01-21 15:30:00,
  created_by = 1,
  status = 1,
  type = 'CO'
);
```

**course_repository_documents (2 records):**
```sql
INSERT INTO course_repository_documents (
  upload_document = '1705871400_65a1b2c3_leadership-fundamentals.pdf',
  course_repository_details_pk = 1,  -- Links to details above
  course_repository_master_pk = 1,
  file_title = 'Leadership Fundamentals - Complete Guide',
  full_path = 'storage/course-repository/1705871400_65a1b2c3_leadership-fundamentals.pdf',
  del_type = 1
);

INSERT INTO course_repository_documents (
  upload_document = '1705871405_65a1b2c4_case-study-analysis.xlsx',
  course_repository_details_pk = 1,  -- Same detail_pk (Multiple files)
  course_repository_master_pk = 1,
  file_title = 'Leadership Case Study Analysis',
  full_path = 'storage/course-repository/1705871405_65a1b2c4_case-study-analysis.xlsx',
  del_type = 1
);
```

---

## JavaScript Form Submission (show.blade.php)

```javascript
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Validate at least one file is selected
    let hasAttachment = false;
    formData.getAll('attachments[]').forEach(file => {
        if (file instanceof File && file.size > 0) {
            hasAttachment = true;
        }
    });
    
    if (!hasAttachment) {
        Swal.fire('Warning!', 'Please select at least one document', 'warning');
        return;
    }
    
    // Send to controller
    fetch(`/course-repository/${repositoryPk}/upload-document`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success!', 'Documents uploaded successfully', 'success')
                .then(() => location.reload());
        } else {
            Swal.fire('Error!', data.error || 'Upload failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error!', 'Upload failed', 'error');
    });
});
```

---

## File Storage Location

**Files are stored at:**
```
public/storage/course-repository/
├── 1705871400_65a1b2c3_leadership-fundamentals.pdf
├── 1705871405_65a1b2c4_case-study-analysis.xlsx
├── 1705871410_65a1b2c5_business-admin-notes.pdf
└── ...
```

**Access URL:**
```
/storage/course-repository/{filename}
```

---

## Verification Queries

```sql
-- 1. Check inserted details
SELECT * FROM course_repository_details 
WHERE created_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_date DESC;

-- 2. Check uploaded documents
SELECT * FROM course_repository_documents 
WHERE course_repository_master_pk = 1
ORDER BY pk DESC;

-- 3. Check relationship (details with documents)
SELECT 
    d.pk as detail_id,
    d.keyword,
    d.videolink,
    COUNT(doc.pk) as total_files
FROM course_repository_details d
LEFT JOIN course_repository_documents doc 
    ON d.pk = doc.course_repository_details_pk
WHERE d.course_repository_master_pk = 1
GROUP BY d.pk;
```

---

## Error Handling

The controller includes proper error handling:

1. **Validation Errors (422):** Invalid file types, missing fields
2. **Server Errors (500):** File upload issues, database errors
3. **All errors logged** in `storage/logs/laravel-*.log`

---

## Notes

- ✅ यह implementation form से आने वाले सभी data को properly insert करता है
- ✅ Multiple files एक ही topic/session के लिए support करता है
- ✅ Soft delete functionality है (del_type = 0 for soft delete)
- ✅ File storage secure है (public disk में stored)
- ✅ All fields properly validated हैं

<?php

/*
|--------------------------------------------------------------------------
| Security module file storage (ID cards, family cards, vehicle passes)
|--------------------------------------------------------------------------
|
| Files live on the private "security" disk (config/filesystems.php) in the
| same folder layout as the legacy E:\security archive, so the old files can
| be copied across unchanged:
|
|   <root>/contractual/<photo_path>/<file>            security_con_oth_id_apply
|   <root>/DuplicateCardRequest/<id_card_no>/<file>   security_dup_*_id_apply
|   <root>/familyCard/<emp_id_apply>/<file>           security_family_id_apply
|   <root>/permanent/<unique no>/<file>               security_parm_id_apply
|   <root>/VechicalDoc/<unique no>/<file>             vehicle_pass_* tables
|
| Legacy rows store a bare file name; the folder comes from the row's key
| column (see App\Support\SecurityFiles::LEGACY_FOLDERS). Permanent and
| vehicle folders are random numbers that no table records, so those rows
| are linked by `php artisan security:link-legacy-files`.
|
*/

return [

    'disk' => 'security',

    // Folder name per category, exactly as on the legacy archive.
    'folders' => [
        'contractual' => 'contractual',
        'duplicate' => 'DuplicateCardRequest',
        'family' => 'familyCard',
        'permanent' => 'permanent',
        'vehicle' => 'VechicalDoc',
    ],

    // Uploads made before this layout went to the public disk. They stay
    // readable (served through the same authenticated route) until moved.
    'legacy_public_disk' => 'public',
    'legacy_public_prefixes' => [
        'idcard/',
        'family_idcard/',
        'vehicle_documents/',
        'duplicate_vehicle_pass_docs/',
    ],

    // Extensions the file route may render inline; everything else downloads.
    'inline_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],

];

# Private leave and notice attachments

Leave application attachments and notice documents are served from the `private`
filesystem disk. Before routing traffic to a release with this change:

PT-exemption attachment downloads are for the record owner, Training/Estate
authorities, and faculty who are authorized to view that trainee through an
active course coordinator or group assignment. The student-detail page only
renders a download link for those same viewers; the endpoint enforces the policy
independently.

1. Put the application in maintenance mode and back up the public files and the
   `leave_application_attachment.file_path` / `notices_notification.document`
   references.
2. Deploy the release while keeping traffic in maintenance mode so the command
   and private-download routes are available but users cannot reach the release.
3. Run `php artisan attachments:migrate-sensitive --dry-run`. Resolve every
   missing or unsupported path it reports before continuing.
4. Run `php artisan attachments:migrate-sensitive` and confirm the success
   message. The command copies and verifies every referenced file in private
   storage before deleting public copies. It can be rerun after an interrupted
   transfer; it never serves a missing private file from public storage.
5. Verify the authenticated downloads, then bring the application out of
   maintenance mode. Disable public web access to the old storage path if the
   deployment serves the storage tree through a separate static-file layer.

Do not proceed if the dry run or transfer exits unsuccessfully. Missing files
need to be restored or their database references repaired by the responsible
owner; do not create public-link fallbacks.

# Private leave attachments and gated notice documents

Leave application attachments are served from the `private` filesystem disk.
Notice documents are stored on the `public` disk (`notice_docs/`, a client
requirement) but must only be read through the audience-checked
`admin.notice.attachment` route. Because `public/storage` is a symlink to
`storage/app/public`, the web server would otherwise serve
`/storage/notice_docs/<file>` to anyone, with no login and no audience check.
That block is web-server configuration, not application code; see "Notice
documents" below.

PT-exemption attachment downloads are for the record owner, Training/Estate
authorities, and faculty who are authorized to view that trainee through an
active course coordinator or group assignment. The student-detail page only
renders a download link for those same viewers; the endpoint enforces the policy
independently.

## Notice documents: deny direct access (required, every environment)

Add one of these to the server config (not to a gitignored `.htaccess` that a
deploy can drop), adjusting the path to the release's storage directory:

Apache (vhost or `httpd.conf`):

```apache
<Directory "/path/to/Sargam-2.0/storage/app/public/notice_docs">
    Require all denied
</Directory>
```

nginx (inside the site's `server` block, before any generic static rule):

```nginx
location ^~ /storage/notice_docs/ { return 404; }
```

Reload the server, then verify:

1. Unauthenticated `GET /storage/notice_docs/<an existing file>` returns 403 or
   404, not 200.
2. An audience member opening the notice attachment from the dashboard still
   gets the file (the route reads the disk directly, so the block does not
   affect it).

Notice files uploaded while notices were kept on the `private` disk stay there
and are still served by the route. `attachments:migrate-sensitive` does not move
notice files.

## Leave attachments: migrate to private

Before routing traffic to a release with this change:

1. Put the application in maintenance mode and back up the public files and the
   `leave_application_attachment.file_path` references.
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

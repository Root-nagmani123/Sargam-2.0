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

Notice files stay on the public disk; this rule is what keeps them off the open
web. It is a deployment action on the production web server, not part of the
release.

First confirm which web server actually serves production (Apache or nginx, and
whether a proxy or CDN in front serves `/storage/` itself). The rules below are
deployment-specific: apply only the one for that server, adapted to its real
paths, in the server config (not in a gitignored `.htaccess` that a deploy can
drop). If something other than the application's web server serves `/storage/`,
the block has to be added there instead.

Apache (vhost or `httpd.conf`). The path is the site's `DocumentRoot` (the
release's `public/` directory, written exactly as `DocumentRoot` writes it)
followed by `/storage/notice_docs`:

```apache
<Directory "/path/to/Sargam-2.0/public/storage/notice_docs">
    Require all denied
</Directory>
```

Do not point it at `storage/app/public/notice_docs`. Apache matches
`<Directory>` against the path it serves (`public/storage/...`) and does not
resolve the `public/storage` link, so a rule on the link target never applies.
Executed on Apache 2.4.58 (Windows, `public/storage` a junction): the
link-target rule left every direct request at 200; the `public/storage/...`
rule returned 403 for the plain path and for case, `//`, `./`, `../`, `%6e`,
trailing-dot and trailing-space variants, while other public files and the
Laravel route were unaffected.

nginx (inside the site's `server` block). `^~` stops regex locations, such as a
static-file rule, from taking the request first:

```nginx
location ^~ /storage/notice_docs/ { return 404; }
```

The nginx rule has not been executed against a server; verify it with the
checks below before relying on it.

Reload the server, then verify on production and record the results:

1. Direct access is blocked: an unauthenticated
   `GET /storage/notice_docs/<an existing file>` returns 403 or 404, not 200.
   Repeat with `/storage/NOTICE_DOCS/<file>` and `//storage//notice_docs/<file>`.
2. Other public files are still served: a `GET` for a file elsewhere under
   `/storage/` still returns 200.
3. The authorized route still works: an audience member opening the notice
   attachment from the dashboard (`admin.notice.attachment`) gets the file, and
   a logged-out request to that route redirects to login. The route reads the
   disk directly, so the block does not affect it.

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

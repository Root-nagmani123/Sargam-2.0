# Private leave attachments and gated notice documents

Leave application attachments are served from the `private` filesystem disk.
Notice documents are stored on the `public` disk (`notice_docs/`, a client
requirement; its source is recorded with the Engineering lead's risk acceptance,
not in this repository) but must only be read through the audience-checked
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

Do not apply either rule yet: steps 1-3 of "Verify" below must pass first, on
the same server, while direct access is still open.

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

### Verify

Record every step's printed output with the date, environment and who ran it.
Laravel's test suite cannot check this: its test kernel never serves
`/storage/` files, so only requests to the deployed server prove the block.

A 403 or 404 proves nothing by itself: a missing file, a wrong host or a URL
variant the server rejects anyway also returns 404. So the check first shows
that the same file is publicly served without the rule, and fails if it is not.

Save this as `notice-direct-access-check.sh` on a host that can read the
release's `storage/` directory and reach the public URL users use (through the
proxy or CDN, if there is one):

```sh
#!/bin/sh
# Usage: sh notice-direct-access-check.sh before|after
set -u
set -f
BASE=${BASE:-'https://<production host>'}       # the URL users reach, no trailing slash
APP=${APP:-'/path/to/Sargam-2.0'}               # release root on this server
DOC=${DOC:-'notice_docs/<file from the query>'}
NAME=${DOC#notice_docs/}
FILE="$APP/storage/app/public/$DOC"

fail() { echo "FAIL: $*"; exit 1; }

# The selected file must be on the public disk, or no response proves anything.
[ -f "$FILE" ] && [ -s "$FILE" ] || fail "$DOC is not on the public disk ($FILE); pick another file"
SIZE=$(wc -c < "$FILE" | tr -d ' ')

# --path-as-is: without it curl rewrites /./ and /../ before sending.
get() { curl -s --path-as-is -o /dev/null -w '%{http_code} %{size_download}' "$BASE$1"; }

variants() {
  printf '%s\n' "/storage/$DOC" "//storage//$DOC" "/storage/./$DOC" \
    "/storage/notice_docs/../$DOC" "/storage/NOTICE_DOCS/$NAME" "/storage/notice_docs%2F$NAME"
}

case "${1:-}" in
before)
  set -- $(get "/storage/$DOC")
  if [ "$1" != 200 ] || [ "$2" != "$SIZE" ]; then
    fail "positive control: /storage/$DOC returned $1 ($2 bytes; file is $SIZE bytes)"
  fi
  echo "PASS positive control: 200 $2 bytes  /storage/$DOC"
  for p in $(variants); do
    set -- $(get "$p")
    if [ "$1" = 200 ] && [ "$2" = "$SIZE" ]; then
      echo "variant tested       ($1 without the rule)  $p"
    else
      echo "variant NOT A TEST here ($1 without the rule)  $p"
    fi
  done
  ;;
after)
  rc=0
  for p in $(variants); do
    set -- $(get "$p")
    case "$1" in
      403|404) echo "PASS $1  $p" ;;
      *)       echo "FAIL $1 ($2 bytes)  $p"; rc=1 ;;
    esac
  done
  [ "$rc" = 0 ] && echo "PASS: every direct request refused" || echo "FAIL: direct access is open"
  exit "$rc"
  ;;
*)
  fail "usage: sh $0 before|after"
  ;;
esac
```

1. Select a notice file that is on the public disk (read-only). Take the newest
   row whose file exists under `storage/app/public/`; older notices may be on
   the `private` disk or have no file at all. Start from:

   ```sql
   SELECT pk, document FROM notices_notification
   WHERE document LIKE 'notice_docs/%' ORDER BY pk DESC LIMIT 20;
   ```

   Set `BASE`, `APP` and `DOC` (the `document` value) in the script. The script
   stops with `FAIL: … is not on the public disk` if the file is not there; move
   to the next row.
2. Positive control, **before** applying the rule: `sh
   notice-direct-access-check.sh before`. It must print `PASS positive control:
   200 <bytes>`, where the byte count equals the file's size. Any other result
   exits 1, and the verification cannot proceed: fix `BASE`/`APP`/`DOC` or pick
   another file. Do not apply the rule until this passes.
3. Record the `before` output. Its variant lines say which variants this server
   actually serves: a variant marked `NOT A TEST here` is rejected even without
   the rule, so its later 403/404 is not evidence. Expect `%2F` to be `NOT A
   TEST` on Apache, which refuses encoded slashes by default (`AllowEncodedSlashes
   Off`), and `/storage/NOTICE_DOCS/…` to be `NOT A TEST` on a case-sensitive
   (Linux) filesystem.
4. Apply the rule for the server (above) and reload it.
5. Re-run the **same file**: `sh notice-direct-access-check.sh after`. Every
   line must print 403 or 404 and the script must exit 0 with `PASS: every
   direct request refused`. A 200, or any other code, exits 1: direct access is
   still open. Remove nothing from the variant list to make it pass.

Then check that the rule did not over-block, and that the route still works:

- Check 2 — other public files are still served: `curl -s -o /dev/null -w
  "%{http_code}\n" "$BASE/storage/<a non-notice file under storage/app/public>"`
  prints 200. Without this control a blanket `/storage/` outage would look like
  a pass.
- Check 3 — the authorized route still works (signed-in browser): an audience
  member opening the notice attachment from the dashboard
  (`/admin/notice/<pk>/attachment`) gets the file; a login outside the notice's
  audience gets 404; a logged-out request to that route redirects to login. The
  route reads the disk directly, so the block does not affect it.

Repeat steps 1-5 on every new environment, before it serves users. After a
web-server, proxy or CDN change on an environment that already has the rule,
`before` cannot pass, because the rule is in place. Run `after` with the file
from that environment's recorded positive control (the script still fails if
the file has left the public disk), and record it with a reference to that
positive control. An environment with no recorded positive control has not been
verified.

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

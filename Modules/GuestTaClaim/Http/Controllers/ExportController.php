<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\GuestTaClaim\Contracts\TimetableProvider;
use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Enums\Permission as P;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Models\UserPermission;
use Modules\GuestTaClaim\Services\CsvExporter;
use Modules\GuestTaClaim\Support\Format;

class ExportController extends Controller
{
    public function __invoke(Request $request, string $type, CsvExporter $csv, TimetableProvider $timetable)
    {
        $stamp = now()->format('Ymd-Hi');

        switch ($type) {
            case 'claims':
                abort_unless($this->can(P::CreateClaim, P::Forward, P::GenerateReport), 403);
                $tab = $request->query('tab', 'all');
                $claims = Claim::visibleTo($request->user())
                    ->when($tab !== 'all' && ClaimStatus::tryFrom($tab), fn ($q) => $q->where('status', $tab))->latest('id')->get();

                return $csv->download("guest-ta-claims-$stamp.csv", $csv->claimRows($claims));

            case 'approvals':
                abort_unless($this->can(P::Approve), 403);

                return $csv->download("cc-approvals-$stamp.csv", $csv->claimRows(ApprovalController::query($request->query('tab') === 'done' ? 'done' : 'pending')->get()));

            case 'payments':
                abort_unless($this->can(P::ViewPayments, P::MarkPaid), 403);

                return $csv->download("accounts-payments-$stamp.csv", $csv->claimRows(PaymentController::query($request->query('tab') === 'paid' ? 'paid' : 'pending')->get()));

            case 'reports':
                abort_unless($this->can(P::GenerateReport, P::ViewPayments, P::MarkPaid), 403);

                return $csv->download("guest-ta-claim-register-$stamp.csv", $csv->claimRows(ReportController::query($request)->get()));

            case 'claim':
                $claim = Claim::with('sessions')->findOrFail($request->query('id'));
                $this->authorizeView($claim);
                $rows = $csv->claimRows(collect([$claim]));
                $rows[] = [];
                $rows[] = ['Date', 'Start', 'End', 'Topic', 'Venue', 'Status'];
                foreach ($claim->sessions as $s) {
                    $rows[] = [Format::date($s->session_date), substr($s->start_time, 0, 5), substr($s->end_time, 0, 5), $s->topic, $s->venue, ucfirst($s->status)];
                }

                return $csv->download(str_replace('/', '-', $claim->claim_no).".csv", $rows);

            case 'timetable':
                abort_unless($this->can(P::CreateClaim), 403);
                $data = $request->validate(['course_id' => 'required|string', 'from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
                $sessions = $timetable->guestSessions($data['course_id'], Carbon::parse($data['from']), Carbon::parse($data['to']));
                $faculty = $timetable->facultyMany($sessions->pluck('faculty_id')->unique()->all());
                $rows = [['Guest faculty', 'Date', 'Start', 'End', 'Topic', 'Venue']];
                foreach ($sessions as $s) {
                    $rows[] = [$faculty[$s['faculty_id']]['name'] ?? $s['faculty_id'], Format::date($s['date']), $s['start'], $s['end'], $s['topic'], $s['venue']];
                }

                return $csv->download("faculty-timetable-$stamp.csv", $rows);

            case 'permissions':
                abort_unless($this->can(P::ManagePermissions), 403);
                $model = config('guest-ta-claim.user_model');
                $nameCol = config('guest-ta-claim.user_name_column', 'name');
                $map = UserPermission::all()->groupBy('user_id');
                $users = $model::whereIn((new $model)->getKeyName(), $map->keys())->get();
                $rows = [array_merge(['User ID', 'Name'], array_map(fn ($p) => $p->label(), P::cases()))];
                foreach ($users as $u) {
                    $held = $map[$u->getKey()]->pluck('permission')->all();
                    $rows[] = array_merge([$u->getKey(), $u->{$nameCol}], array_map(fn ($p) => in_array($p->value, $held, true) ? 'Yes' : 'No', P::cases()));
                }

                return $csv->download("guest-ta-claim-permissions-$stamp.csv", $rows);
        }

        abort(404);
    }
}

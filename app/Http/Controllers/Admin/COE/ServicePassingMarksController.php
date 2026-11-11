<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ServicePassingMarksDataTable;
use App\DataTables\SubjectMaxMarksDataTable;
use App\Http\Controllers\Admin\COE\Concerns\ExportsCoeMaster;
use App\Http\Controllers\Controller;
use App\Models\ServiceMaster;
use App\Models\ServicePassingMarksMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Service Passing Marks.
 *
 * One row per service_master row: `service_master_pk` required|unique,
 * `passing_marks` 0..9999.99, `active_inactive` 0/1. Same page and endpoints
 * shape as ComponentMasterController.
 */
class ServicePassingMarksController extends Controller
{
    use ExportsCoeMaster;

    public function index(ServicePassingMarksDataTable $dataTable)
    {
        $master = [
            'title' => 'Service Passing Marks',
            'entity' => 'Service Passing Marks',
            'prefix' => 'spm',
            'colvisKey' => 'coeServicePassingMarks:hiddenColumns:v1',
            'labels' => [
                'createButton' => 'Add Service Passing Marks',
                'createTitle' => 'Add Service Passing Marks',
                'editTitle' => 'Edit Service Passing Marks',
                'createSubmit' => 'Save',
                'editSubmit' => 'Update',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            // Active / Inactive filter (exports follow it) and the Action column pinned to the right.
            'statusFilter' => true,
            'pinActions' => true,
            'fields' => [
                [
                    'key' => 'service', 'name' => 'service_master_pk', 'label' => 'Service', 'column' => 'Service',
                    'type' => 'select', 'placeholder' => 'Select Service', 'required' => true,
                    'options' => $this->serviceOptions(),
                ],
                [
                    'key' => 'marks', 'name' => 'passing_marks', 'label' => 'Passing Marks', 'column' => 'Passing Marks',
                    'type' => 'number', 'placeholder' => '100', 'required' => true, 'maxValue' => 9999.99,
                ],
            ],
            // Shown in the grid and exports only, not in the Add / Edit form.
            'extraColumns' => [
                ['key' => 'service_name', 'name' => 'service_name', 'column' => 'Service Name', 'after' => 'service'],
            ],
            'endpoints' => [
                'list' => route('admin.coe.service-passing-marks.index'),
                'export' => route('admin.coe.service-passing-marks.export', ['format' => '__FORMAT__']),
                'store' => route('admin.coe.service-passing-marks.store'),
                'destroy' => route('admin.coe.service-passing-marks.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.service-passing-marks.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = array_column($master['fields'], 'key');

        return $dataTable->render('admin.coe.examination_term.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;

        $validated = $request->validate([
            'service_master_pk' => [
                'required', 'integer', Rule::exists('service_master', 'pk'),
                Rule::unique('service_passing_marks_master', 'service_master_pk')->ignore($id, 'pk'),
            ],
            'passing_marks' => ['required', 'numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
            'active_inactive' => ['required', 'in:0,1'],
        ], [
            'service_master_pk.unique' => 'Passing marks for this service already exist.',
            'passing_marks.decimal' => 'Enter a number with at most 2 decimals.',
        ], [
            'service_master_pk' => 'service',
            'passing_marks' => 'passing marks',
        ]);

        $model = $id ? ServicePassingMarksMaster::findOrFail($id) : new ServicePassingMarksMaster();
        $model->service_master_pk = $validated['service_master_pk'];
        $model->passing_marks = $validated['passing_marks'];
        $model->active_inactive = $validated['active_inactive'];
        if (! $id) {
            $model->created_date = now();
        }
        $model->modified_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Service passing marks ' . ($id ? 'updated' : 'created') . ' successfully.',
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = ServicePassingMarksMaster::findOrFail(decrypt($id));
        $model->active_inactive = $request->boolean('active') ? 1 : 0;
        $model->modified_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    /** Only an inactive entry can be deleted: faculty setup and drives read the active one as the service's passing marks. */
    public function destroy(string $id): JsonResponse
    {
        $model = ServicePassingMarksMaster::findOrFail(decrypt($id));

        if ((int) $model->active_inactive === 1) {
            return response()->json(['status' => 'error', 'message' => 'Active service passing marks cannot be deleted. Deactivate them first.'], 422);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => 'Service passing marks deleted successfully.']);
    }

    /**
     * Active services, plus any inactive one a row already uses so its Edit
     * dialog can still show it. [pk => name].
     */
    private function serviceOptions(): array
    {
        return ServiceMaster::query()
            ->where('active_inactive', 1)
            ->orWhereIn('pk', ServicePassingMarksMaster::select('service_master_pk'))
            ->orderBy('service_name')
            ->pluck('service_name', 'pk')
            ->map(fn ($name) => trim($name))
            ->all();
    }

    /** CSV / Excel / PDF / Print of the grid as shown on screen (ExportsCoeMaster). */
    public function export(Request $request, string $format)
    {
        return $this->exportCoeMaster(
            $request,
            $format,
            ServicePassingMarksMaster::query()->with('service')->orderByDesc('pk'),
            [
                'sno' => $this->coeSnoColumn(),
                'service' => ['heading' => 'Service', 'width' => '16%', 'align' => 'left',
                    'value' => fn ($row) => $row->service->service_short_name ?? ''],
                'service_name' => ['heading' => 'Service Name', 'width' => '40%', 'align' => 'left',
                    'value' => fn ($row) => $row->service->service_name ?? ''],
                'marks' => ['heading' => 'Passing Marks', 'width' => '16%', 'align' => 'center',
                    'value' => fn ($row) => SubjectMaxMarksDataTable::marks($row->passing_marks)],
                'status' => $this->coeStatusColumn(),
            ],
            // Same match as the grid's search: full or short service name.
            fn ($query, string $term) => $query->whereHas('service', fn ($q) => $q
                ->where('service_name', 'like', '%' . $term . '%')
                ->orWhere('service_short_name', 'like', '%' . $term . '%')),
            'Service Passing Marks',
            'ServicePassingMarks'
        );
    }
}

<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ServicePassingMarksDataTable;
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
            'endpoints' => [
                'list' => route('admin.coe.service-passing-marks.index'),
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

    public function destroy(string $id): JsonResponse
    {
        ServicePassingMarksMaster::where('pk', decrypt($id))->delete();

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
}

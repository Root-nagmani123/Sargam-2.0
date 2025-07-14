<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ColumnController extends Controller
{
    public function showForm()
    {
        return view('admin.registration.add_column_form');
    }

    public function addColumn(Request $request)
    {
        // dd($request);
        $validated = $request->validate([
            'tablename' => 'required|alpha_dash',
            'columnname' => 'required|alpha_dash',
            'datatype' => 'required|in:INT,VARCHAR,TEXT,DATE,BOOLEAN',
            'length' => 'nullable|integer',
            'nullable' => 'nullable',
            'defaultvalue' => 'nullable|string',
        ]);


        try {
            $table = $validated['tablename'];
            $column = $validated['columnname'];
            $type = strtoupper($validated['datatype']);
            $length = $validated['length'];
            $nullable = $request->has('nullable');
            $default = $validated['defaultvalue'];

            Schema::table($table, function ($tableBlueprint) use ($type, $column, $length, $nullable, $default) {
                $col = match ($type) {
                    // 'VARCHAR' => $tableBlueprint->string($column, $length ?? 255),
                    'VARCHAR' => ($length && $length <= 191)
                        ? $tableBlueprint->string($column, $length)
                        : $tableBlueprint->text($column), // fallback to TEXT
                    'INT' => $tableBlueprint->integer($column),
                    'TEXT' => $tableBlueprint->text($column),
                    'DATE' => $tableBlueprint->date($column),
                    'BOOLEAN' => $tableBlueprint->boolean($column),
                };

                if ($nullable) {
                    $col->nullable();
                }

                if ($default !== null && $default !== '') {
                    $col->default($default);
                }
            });

            return redirect()->back()->with('success', 'Column added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error adding column: ' . $e->getMessage());
        }
    }
}

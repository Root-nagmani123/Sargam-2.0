<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\FC\FcJoiningDocumentForm;
use App\Support\FC\DocumentFormTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Fillable joining-document forms.
 *
 * For a document field whose `form_template` is set (e.g. family_details),
 * the candidate fills a structured form online instead of uploading a file.
 * On submit the data is saved (re-editable) and a PDF is generated and stored
 * into the field's target_column — so the existing checklist View/Status logic
 * works unchanged. The candidate upload/save logic is NOT touched.
 */
class FcJoiningDocumentFormController extends Controller
{
    /** Show the fillable form (blank or pre-filled with previously saved data). */
    public function show(FcForm $form, FcFormStep $step, string $field): View|RedirectResponse
    {
        [$formField, $template] = $this->resolve($form, $step, $field);

        $userId = Auth::id();
        $saved  = FcJoiningDocumentForm::where('user_id', $userId)
            ->where('field_name', $formField->field_name)
            ->first();

        return view(DocumentFormTemplates::formView($template), [
            'form'      => $form,
            'step'      => $step,
            'field'     => $formField,
            'template'  => $template,
            'data'      => $saved?->form_data ?? [],
        ]);
    }

    /** Validate, save the entered data, generate the PDF, and store it like an upload. */
    public function save(Request $request, FcForm $form, FcFormStep $step, string $field): RedirectResponse
    {
        [$formField, $template] = $this->resolve($form, $step, $field);

        $userId = Auth::id();

        $rules = DocumentFormTemplates::rules($template['key']);
        $rules['signature']   = 'nullable|array';
        $rules['signature.*'] = 'nullable|image|max:2048';
        $validated = $request->validate($rules);
        $data      = DocumentFormTemplates::normalize($template['key'], $validated);

        // Signature images: store newly-uploaded ones, keep previously-saved ones.
        $prior = FcJoiningDocumentForm::where('user_id', $userId)
            ->where('field_name', $formField->field_name)->first();
        $data['_signatures'] = $this->handleSignatures(
            $request, $template, $formField, $userId,
            $prior?->form_data['_signatures'] ?? []
        );

        $targetTable = $formField->target_table ?: $step->target_table;
        $targetCol   = $formField->target_column ?: $formField->field_name;

        // 1) Render + store the generated PDF on the public disk (same dir as uploads).
        $pdfPath = $this->generateAndStorePdf($template, $formField, $userId, $data);

        // 2) Write the PDF path into the document's target column — identical to an upload.
        $uCol = fc_user_col($targetTable);
        $uVal = fc_user_val($targetTable, $userId);
        DB::table($targetTable)->updateOrInsert(
            [$uCol => $uVal],
            [$targetCol => $pdfPath, 'updated_at' => now()]
        );
        $existing = DB::table($targetTable)->where($uCol, $uVal)->first();
        if ($existing && empty($existing->created_at)) {
            DB::table($targetTable)->where($uCol, $uVal)->update(['created_at' => now()]);
        }

        // 3) Persist the editable structured data.
        FcJoiningDocumentForm::updateOrCreate(
            ['user_id' => $userId, 'field_name' => $formField->field_name],
            [
                'form_id'      => $form->id,
                'step_id'      => $step->id,
                'template_key' => $template['key'],
                'form_data'    => $data,
                'pdf_path'     => $pdfPath,
            ]
        );

        return redirect()->route('fc-reg.forms.step', [$form, $step])
            ->with('success', $formField->label.' saved. A PDF copy has been generated.');
    }

    /**
     * Resolve the step's document field and its template, or 404.
     *
     * @return array{0:\App\Models\FC\FcFormField,1:array}
     */
    private function resolve(FcForm $form, FcFormStep $step, string $field): array
    {
        if ($step->form_id !== $form->id) {
            abort(404);
        }

        $formField = $step->activeFields->firstWhere('field_name', $field);
        if (! $formField || $formField->field_type !== 'file') {
            abort(404);
        }

        $template = DocumentFormTemplates::get($formField->form_template);
        if (! $template) {
            abort(404, 'This document is not a fillable form.');
        }

        return [$formField, $template];
    }

    /**
     * Store uploaded signature images and return their relative paths keyed by
     * signature index. Newly-uploaded files replace; otherwise the prior path
     * (from a previous save) is preserved.
     *
     * @param  array<int,string>  $existing
     * @return array<int,string>
     */
    private function handleSignatures(Request $request, array $template, $formField, int $userId, array $existing): array
    {
        $out = [];
        foreach (array_keys($template['signatures'] ?? []) as $i) {
            $file = $request->file("signature.$i");
            if ($file) {
                $out[$i] = $file->storeAs(
                    'uploads/'.fc_upload_path_segment($userId).'/documents/signatures',
                    $formField->field_name.'_sig'.$i.'_'.time().'.'.$file->extension(),
                    'public'
                );
            } elseif (! empty($existing[$i])) {
                $out[$i] = $existing[$i];
            }
        }

        return $out;
    }

    /** Render the template's PDF view to a file on the public disk; return its relative path. */
    private function generateAndStorePdf(array $template, $formField, int $userId, array $data): string
    {
        // Embed signature images as base64 data URIs so mpdf renders them without local-file access.
        $data['_signature_src'] = [];
        foreach ($data['_signatures'] ?? [] as $i => $rel) {
            $abs = storage_path('app/public/'.$rel);
            if (is_file($abs)) {
                $mime = mime_content_type($abs) ?: 'image/png';
                $data['_signature_src'][$i] = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($abs));
            }
        }

        $html = view(DocumentFormTemplates::pdfView($template), [
            'template' => $template,
            'data'     => $data,
            'field'    => $formField,
        ])->render();

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // mpdf renders Devanagari (Hindi) reliably with auto script/font detection.
        $mpdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'tempDir'          => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'margin_top'       => 12,
            'margin_bottom'    => 12,
            'margin_left'      => 12,
            'margin_right'     => 12,
        ]);
        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

        $relativePath = 'uploads/'.fc_upload_path_segment($userId).'/documents/'
            .$formField->field_name.'_'.time().'.pdf';

        Storage::disk('public')->put($relativePath, $pdfContent);

        return $relativePath;
    }
}

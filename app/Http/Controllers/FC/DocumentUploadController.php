<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Services\FC\FcCourseEnrollmentService;
use App\Services\FC\FcRegistrationFlowService;
use App\Models\FC\{
    FcJoiningRelatedDocumentsMaster,
    FcJoiningRelatedDocumentsDetailsMaster,
    StudentMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Matches: FCJoiningRelatedDocuments flow in the original system.
 * Handles student document uploads against a checklist defined in admin.
 */
class DocumentUploadController extends Controller
{
    public function show(FcRegistrationFlowService $registrationFlow)
    {
        $userId = Auth::id();

        $form = $registrationFlow->activeFormFromSession();
        if ($form && ! $registrationFlow->usesLegacyDocumentChecklist($form)) {
            if ($registrationFlow->isDynamicDocumentsComplete($form, $userId)) {
                return redirect()->route('fc-reg.forms.dashboard', $form)
                    ->with('info', 'Joining documents are already completed on your registration form.');
            }

            $docsStep = $registrationFlow->documentsStep($form);
            if ($docsStep) {
                return redirect()->route('fc-reg.forms.step', [$form, $docsStep])
                    ->with('info', 'Upload joining documents using your registration form (not the legacy checklist).');
            }
        }

        $bankDone = StudentMaster::forUser($userId)->value('bank_done');
        if (! $bankDone) {
            return redirect()->route('fc-reg.registration.bank')
                ->with('error', 'Please complete bank details first.');
        }

        $travelDone = StudentMaster::forUser($userId)->value('travel_done');
        if (! $travelDone) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('error', 'Please submit your travel plan before uploading documents.');
        }

        // Get all mandatory/active documents from the master checklist
        $docMasters = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->orderBy('display_order')->get();

        // Already-uploaded docs by this user
        $uploadedDocs = FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)
            ->get()->keyBy('document_master_id');

        return view('fc.registration.documents', compact('docMasters', 'uploadedDocs'));
    }

    public function upload(Request $request, int $documentMasterId)
    {
        $userId = Auth::id();

        if (! StudentMaster::forUser($userId)->value('travel_done')) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('error', 'Please submit your travel plan before uploading documents.');
        }

        $docMaster = FcJoiningRelatedDocumentsMaster::findOrFail($documentMasterId);

        $request->validate([
            'document_file' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120', // 5 MB max
        ]);

        $file     = $request->file('document_file');
        $filePath = $file->storeAs(
            "uploads/{$userId}/documents",
            $documentMasterId . '_' . time() . '.' . $file->extension(),
            'public'
        );

        FcJoiningRelatedDocumentsDetailsMaster::updateOrCreate(
            [fc_user_col('fc_joining_related_documents_details_masters') => fc_user_val('fc_joining_related_documents_details_masters', $userId), 'document_master_id' => $documentMasterId],
            [
                'document_name'     => $docMaster->document_name,
                'file_path'         => $filePath,
                'file_original_name'=> $file->getClientOriginalName(),
                'is_uploaded'       => 1,
                'is_verified'       => 0,
            ]
        );

        // Check if all mandatory docs are uploaded, mark docs_done
        $this->checkAndMarkDocsDone($userId);

        return back()->with('success', "\"{$docMaster->document_name}\" uploaded successfully.");
    }

    public function delete(Request $request, int $documentMasterId)
    {
        $userId = Auth::id();

        if (! StudentMaster::forUser($userId)->value('travel_done')) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('error', 'Please submit your travel plan before managing documents.');
        }

        $doc = FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)
            ->where('document_master_id', $documentMasterId)->first();

        if ($doc) {
            // Remove file from disk
            if ($doc->file_path) {
                \Storage::disk('public')->delete($doc->file_path);
            }
            $doc->delete();
        }

        StudentMaster::forUser($userId)->update(['docs_done' => 0]);

        return back()->with('success', 'Document removed.');
    }

    public function finalSubmit(Request $request, FcCourseEnrollmentService $enrollment)
    {
        $userId = Auth::id();

        if (! StudentMaster::forUser($userId)->value('travel_done')) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('error', 'Please submit your travel plan before final document submission.');
        }

        // Validate all mandatory docs are uploaded
        $mandatoryIds = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->where('is_mandatory', 1)->pluck('id');

        $uploadedIds = FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)
            ->where('is_uploaded', 1)->pluck('document_master_id');

        $missing = $mandatoryIds->diff($uploadedIds);
        if ($missing->count() > 0) {
            return back()->with('error', 'Please upload all mandatory documents before final submission.');
        }

        // Mark overall submission complete
        StudentMaster::forUser($userId)->update([
            'docs_done' => 1,
            'status'    => 'SUBMITTED',
        ]);

        $enrollResult = $enrollment->enrollTrainee($userId);
        $flash = 'Registration submitted successfully! Please note your roll number.';
        if ($enrollResult['enrolled']) {
            $flash .= ' You are enrolled in the linked programme.';
        }

        return redirect()->route('fc-reg.registration.status')
            ->with('success', $flash);
    }

    private function checkAndMarkDocsDone(int $userId): void
    {
        $mandatoryIds = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->where('is_mandatory', 1)->pluck('id');
        $uploadedIds = FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)
            ->where('is_uploaded', 1)->pluck('document_master_id');

        if ($mandatoryIds->diff($uploadedIds)->isEmpty()) {
            StudentMaster::forUser($userId)->update(['docs_done' => 1]);
        }
    }
}

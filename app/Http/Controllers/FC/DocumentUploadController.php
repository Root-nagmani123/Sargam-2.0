<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
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
    public function show()
    {
        $username = Auth::user()->username;

        $bankDone = StudentMaster::where('username', $username)->value('bank_done');
        if (! $bankDone) {
            return redirect()->route('fc-reg.registration.bank')
                ->with('error', 'Please complete bank details first.');
        }

        $travelDone = StudentMaster::where('username', $username)->value('travel_done');
        if (! $travelDone) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('error', 'Please submit your travel plan before uploading documents.');
        }

        // Get all mandatory/active documents from the master checklist
        $docMasters = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->orderBy('display_order')->get();

        // Already-uploaded docs by this user
        $uploadedDocs = FcJoiningRelatedDocumentsDetailsMaster::where('username', $username)
            ->get()->keyBy('document_master_id');

        return view('fc.registration.documents', compact('docMasters', 'uploadedDocs'));
    }

    public function upload(Request $request, int $documentMasterId)
    {
        $username = Auth::user()->username;

        if (! StudentMaster::where('username', $username)->value('travel_done')) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('error', 'Please submit your travel plan before uploading documents.');
        }

        $docMaster = FcJoiningRelatedDocumentsMaster::findOrFail($documentMasterId);

        $request->validate([
            'document_file' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120', // 5 MB max
        ]);

        $file     = $request->file('document_file');
        $filePath = $file->storeAs(
            "uploads/{$username}/documents",
            $documentMasterId . '_' . time() . '.' . $file->extension(),
            'public'
        );

        FcJoiningRelatedDocumentsDetailsMaster::updateOrCreate(
            ['username' => $username, 'document_master_id' => $documentMasterId],
            [
                'document_name'     => $docMaster->document_name,
                'file_path'         => $filePath,
                'file_original_name'=> $file->getClientOriginalName(),
                'is_uploaded'       => 1,
                'is_verified'       => 0,
            ]
        );

        // Check if all mandatory docs are uploaded, mark docs_done
        $this->checkAndMarkDocsDone($username);

        return back()->with('success', "\"{$docMaster->document_name}\" uploaded successfully.");
    }

    public function delete(Request $request, int $documentMasterId)
    {
        $username = Auth::user()->username;

        if (! StudentMaster::where('username', $username)->value('travel_done')) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('error', 'Please submit your travel plan before managing documents.');
        }

        $doc = FcJoiningRelatedDocumentsDetailsMaster::where('username', $username)
            ->where('document_master_id', $documentMasterId)->first();

        if ($doc) {
            // Remove file from disk
            if ($doc->file_path) {
                \Storage::disk('public')->delete($doc->file_path);
            }
            $doc->delete();
        }

        StudentMaster::where('username', $username)->update(['docs_done' => 0]);

        return back()->with('success', 'Document removed.');
    }

    public function finalSubmit(Request $request)
    {
        $username = Auth::user()->username;

        if (! StudentMaster::where('username', $username)->value('travel_done')) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('error', 'Please submit your travel plan before final document submission.');
        }

        // Validate all mandatory docs are uploaded
        $mandatoryIds = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->where('is_mandatory', 1)->pluck('id');

        $uploadedIds = FcJoiningRelatedDocumentsDetailsMaster::where('username', $username)
            ->where('is_uploaded', 1)->pluck('document_master_id');

        $missing = $mandatoryIds->diff($uploadedIds);
        if ($missing->count() > 0) {
            return back()->with('error', 'Please upload all mandatory documents before final submission.');
        }

        // Mark overall submission complete
        StudentMaster::where('username', $username)->update([
            'docs_done' => 1,
            'status'    => 'SUBMITTED',
        ]);

        return redirect()->route('fc-reg.registration.status')
            ->with('success', 'Registration submitted successfully! Please note your roll number.');
    }

    private function checkAndMarkDocsDone(string $username): void
    {
        $mandatoryIds = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->where('is_mandatory', 1)->pluck('id');
        $uploadedIds = FcJoiningRelatedDocumentsDetailsMaster::where('username', $username)
            ->where('is_uploaded', 1)->pluck('document_master_id');

        if ($mandatoryIds->diff($uploadedIds)->isEmpty()) {
            StudentMaster::where('username', $username)->update(['docs_done' => 1]);
        }
    }
}

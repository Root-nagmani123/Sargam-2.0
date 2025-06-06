@extends('admin.layouts.master')

@section('title', isset($memoType) ? 'Edit Memo Type' : 'Add Memo Type')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Memo Type Master" />
    <x-session_message />

    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ isset($memoType) ? 'Edit' : 'Add' }} Memo Type
            </h4>
            <hr>

            <form method="POST" action="{{ route('master.memo.type.master.store') }}" enctype="multipart/form-data">
                @csrf
                @if(isset($memoType))
                    <input type="hidden" name="pk" value="{{ encrypt($memoType->pk) }}">
                @endif

                <div class="row">
                    <!-- Memo Type Name -->
                   <div class="col-md-6">
                        <div class="mb-3">
                            <label for="memo_type_name" class="form-label">Memo Type Name <span style="color:red;">*</span></label>
                            <input type="text" name="memo_type_name" class="form-control"
                                value="{{ old('memo_type_name', $memoType->memo_type_name ?? '') }}" required>
                            @error('memo_type_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Memo Document Upload -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="memo_doc_upload" class="form-label">Upload Document</label>
                            <input type="file" name="memo_doc_upload" class="form-control" accept=".pdf,.doc,.docx">
                            <small class="text-muted">Supported formats: PDF, Word</small>
                            @if(isset($memoType) && $memoType->memo_doc_upload)
                                <small class="d-block mt-1">
                                    Existing File: 
                                    <a href="{{ asset('storage/' . $memoType->memo_doc_upload) }}" target="_blank">
                                        {{ $memoType->memo_doc_upload }}
                                    </a>
                                </small>
                            @endif
                            @error('memo_doc_upload')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Status -->
               <div class="col-md-6">
                    <div class="mb-3">
                        <label for="active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                        <select name="active_inactive" class="form-select" required>
                            <option value="1" {{ (old('active_inactive', $memoType->active_inactive ?? 1) == 1) ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="2" {{ (old('active_inactive', $memoType->active_inactive ?? 1) == 2) ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                        @error('active_inactive')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        {{ isset($memoType) ? 'Update' : 'Submit' }}
                    </button>
                    <a href="{{ route('master.memo.type.master.index') }}" class="btn btn-secondary">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

<?php

namespace App\Exports;

use App\Models\IssueLogManagement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class IssueManagementExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = IssueLogManagement::with([
            'category',
            'priority',
            'subCategoryMappings.subCategory',
        ]);

        // User scope (non-admin sees only own issues)
        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $query->where(function ($q) {
                $q->where('employee_master_pk', Auth::user()->user_id)
                    ->orWhere('issue_logger', Auth::user()->user_id)
                    ->orWhere('assigned_to', Auth::user()->user_id)
                    ->orWhere('created_by', Auth::user()->user_id);
            });
        }

        // Raised by: "self" = raised by himself only
        if (!empty($this->filters['raised_by']) && $this->filters['raised_by'] === 'self') {
            $query->where('created_by', Auth::user()->user_id);
        }

        // Search
        if (!empty($this->filters['search'])) {
            $term = trim($this->filters['search']);
            $query->where(function ($q) use ($term) {
                if (is_numeric($term)) {
                    $q->orWhere('pk', $term);
                }
                $q->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('issue_category', 'like', "%{$term}%"))
                    ->orWhereHas('subCategoryMappings.subCategory', fn ($sq) => $sq->where('issue_sub_category', 'like', "%{$term}%"));
            });
        }

        // Category
        if (!empty($this->filters['category'])) {
            $query->where('issue_category_master_pk', $this->filters['category']);
        }

        // Priority
        if (!empty($this->filters['priority'])) {
            $query->where('issue_priority_master_pk', $this->filters['priority']);
        }

        // Date range
        if (!empty($this->filters['date_from'])) {
            $from = Carbon::parse($this->filters['date_from'])->startOfDay()->format('Y-m-d');
            $query->where('created_date', '>=', $from);
        }
        if (!empty($this->filters['date_to'])) {
            $to = Carbon::parse($this->filters['date_to'])->endOfDay()->format('Y-m-d');
            $query->where('created_date', '<=', $to);
        }

        // Status / tab
        $tab = $this->filters['tab'] ?? 'active';
        if (isset($this->filters['status']) && $this->filters['status'] !== '') {
            $query->where('issue_status', (int) $this->filters['status']);
        } elseif ($tab === 'archive') {
            $query->where('issue_status', 2);
        } else {
            $query->whereIn('issue_status', [0, 1, 3, 6]);
        }

        $issues = $query->orderBy('created_date', 'desc')->get();

        return $issues->map(function ($issue) {
            $subCategories = $issue->subCategoryMappings
                ->map(fn ($m) => optional($m->subCategory)->issue_sub_category)
                ->filter()
                ->unique()
                ->implode(', ');
            return [
                $issue->pk,
                $issue->created_date ? $issue->created_date->format('d-m-Y H:i') : 'N/A',
                $issue->category->issue_category ?? 'N/A',
                $subCategories ?: 'N/A',
                $issue->description ?? '',
                $issue->priority->priority ?? 'N/A',
                $issue->status_label,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Category',
            'Sub-Category',
            'Description',
            'Priority',
            'Status',
        ];
    }
}

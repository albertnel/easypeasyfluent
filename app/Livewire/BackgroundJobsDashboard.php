<?php

namespace App\Livewire;

use App\Models\BackgroundJob;
use Livewire\Component;
use Livewire\WithPagination;

class BackgroundJobsDashboard extends Component
{
    use WithPagination;

    public $search = ''; // For filtering jobs by class or method
    public $statusFilter = ''; // For filtering jobs by status
    public $sortField = 'id'; // Default sort field
    public $sortDirection = 'desc'; // Default sort direction

    public function updatingSearch()
    {
        $this->resetPage(); // Reset pagination when search changes
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $jobs = BackgroundJob::query()
            ->when($this->search, function ($query) {
                $query->where('class', 'like', '%' . $this->search . '%')
                      ->orWhere('method', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.background-jobs-dashboard', [
            'jobs' => $jobs,
        ]);
    }
}

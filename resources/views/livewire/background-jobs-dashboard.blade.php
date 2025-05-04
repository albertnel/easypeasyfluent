<div>
    <div class="mb-4">
        <input
            type="text"
            wire:model.debounce.300ms="search"
            placeholder="Search by class or method..."
            class="border p-2 rounded w-full"
        />

        <select wire:model="statusFilter" class="border p-2 rounded mt-2">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="running">Running</option>
            <option value="success">Success</option>
            <option value="failed">Failed</option>
            <option value="scheduled">Scheduled</option>
        </select>
    </div>

    <table class="table-auto w-full border-collapse border border-gray-300">
        <thead>
            <tr>
                <th wire:click="sortBy('priority')" class="cursor-pointer">
                    Priority
                    @if ($sortField === 'priority')
                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('class')" class="cursor-pointer">
                    Class
                    @if ($sortField === 'class')
                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('method')" class="cursor-pointer">
                    Method
                    @if ($sortField === 'method')
                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jobs as $job)
                <tr>
                    <td>{{ $job->priority }}</td>
                    <td>{{ $job->class }}</td>
                    <td>{{ $job->method }}</td>
                    <td>{{ ucfirst($job->status) }}</td>
                    <td>{{ $job->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        <button wire:click="retryJob({{ $job->id }})" class="text-blue-500">Retry</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No jobs found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $jobs->links() }}
    </div>
</div>

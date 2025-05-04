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
        <thead class="bg-gray-100">
            <tr>
                <th wire:click="sortBy('id')" class="cursor-pointer px-4 py-2 border">
                    ID
                    @if ($sortField === 'id')
                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('priority')" class="cursor-pointer px-4 py-2 border">
                    Priority
                    @if ($sortField === 'priority')
                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('class')" class="cursor-pointer px-4 py-2 border">
                    Class
                    @if ($sortField === 'class')
                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('method')" class="cursor-pointer px-4 py-2 border">
                    Method
                    @if ($sortField === 'method')
                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 border">Status</th>
                <th wire:click="sortBy('created_at')" class="cursor-pointer px-4 py-2 border">
                    Created At
                    @if ($sortField === 'created_at')
                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 border">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jobs as $job)
                <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                    <td class="px-4 py-2 border">{{ $job->id }}</td>
                    <td class="px-4 py-2 border">{{ $job->priority }}</td>
                    <td class="px-4 py-2 border">{{ $job->class }}</td>
                    <td class="px-4 py-2 border">{{ $job->method }}</td>
                    <td class="px-4 py-2 border">{{ ucfirst($job->status) }}</td>
                    <td class="px-4 py-2 border">{{ $job->created_at->format('Y-m-d H:i:s') }}</td>
                    <td class="px-4 py-2 border">
                        <button wire:click="retryJob({{ $job->id }})" class="text-blue-500 hover:underline">Retry</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center px-4 py-2">No jobs found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $jobs->links() }}
    </div>
</div>

<x-layouts.app title="Audit Log">
    <x-page-header title="Audit Log" subtitle="Catatan perubahan dan kejadian keamanan. Hanya nama field yang dicatat, bukan nilainya. Log tidak dapat diubah atau dihapus." />
    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
        <select name="entity" class="input sm:w-56"><option value="">Semua entitas</option>@foreach ($entities as $e)<option @selected(request('entity') === $e)>{{ $e }}</option>@endforeach</select>
        <select name="action" class="input sm:w-56"><option value="">Semua aksi</option>@foreach ($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select>
        <input type="number" name="entity_id" value="{{ request('entity_id') }}" placeholder="ID record" class="input sm:w-36">
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Pelaku</th><th>Entitas</th><th>ID</th><th>Aksi</th><th>Detail</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ \App\Support\Format::dateTime($log->created_at) }}</td>
                        <td>{{ $log->actor?->displayName() ?? 'Sistem / tamu' }}</td>
                        <td>{{ $log->entity_type }}</td>
                        <td>{{ $log->entity_id }}</td>
                        <td><x-badge :color="$log->action === 'Login-security-event' ? 'red' : 'slate'">{{ $log->action }}</x-badge></td>
                        <td class="max-w-md text-xs break-words text-slate-500">
                            @if ($log->changed_fields)
                                @foreach ($log->changed_fields as $k => $v)
                                    <div><span class="font-medium">{{ $k }}:</span> {{ is_array($v) ? implode(', ', $v) : $v }}</div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada catatan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.app>

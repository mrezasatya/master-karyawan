<x-layouts.app :title="$project->project_name">
    <div class="mb-2"><a href="{{ route('projects.index') }}" class="link text-sm">← Data Proyek</a></div>
    <x-page-header :title="$project->project_name" :subtitle="($project->project_code ? 'Kode '.$project->project_code.' · ' : '').'Status '.$project->status" />
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>NIK</th><th>Nama</th><th>Jabatan</th><th>Mulai</th><th>Selesai</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assignments as $a)
                    <tr>
                        <td class="font-mono text-xs">{{ $a->employee->nik }}</td>
                        <td><a class="link" href="{{ route('employees.show', [$a->employee, 'tab' => 'pekerjaan']) }}">{{ $a->employee->nama }}</a></td>
                        <td>{{ $a->employee->jobtitle }}</td>
                        <td>{{ \App\Support\Format::date($a->assignment_start) }}</td>
                        <td>{{ \App\Support\Format::date($a->assignment_end) }}</td>
                        <td><x-status-badge :status="$a->assignment_status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada karyawan yang ditugaskan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $assignments->links() }}</div>
</x-layouts.app>

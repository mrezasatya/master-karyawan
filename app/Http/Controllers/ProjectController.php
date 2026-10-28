<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()
            ->withCount(['assignments as active_count' => fn ($q) => $q->where('assignment_status', 'Aktif')])
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->whereRaw('LOWER(project_name) LIKE ?', ['%'.mb_strtolower($t).'%'])->orWhere('project_code', 'like', "%$t%")))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('status')->orderBy('project_name')
            ->paginate(25)->withQueryString();

        return view('projects.index', ['projects' => $projects]);
    }

    public function store(Request $request)
    {
        $project = Project::create($this->validated($request, null, 'prj-new'));

        return redirect()->route('projects.index')->with('success', "Proyek {$project->project_name} ditambahkan.");
    }

    public function show(Project $project)
    {
        $assignments = $project->assignments()->with('employee')->orderBy('assignment_status')->orderByDesc('assignment_start')->paginate(50);

        return view('projects.show', ['project' => $project, 'assignments' => $assignments]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $this->validated($request, $project, 'prj-'.$project->id);
        $activeCount = $project->assignments()->where('assignment_status', 'Aktif')->count();

        if ($data['status'] === 'Nonaktif' && $project->status === 'Aktif' && $activeCount > 0 && ! $request->boolean('confirm_deactivate')) {
            return back()->withInput()->with('warning', "Proyek ini masih memiliki {$activeCount} penugasan aktif. Centang konfirmasi untuk tetap menonaktifkan; riwayat penugasan tidak dihapus.")
                ->with('needs_confirm', $project->id);
        }

        $project->update($data);

        return back()->with('success', 'Proyek diperbarui.');
    }

    private function validated(Request $request, ?Project $project, string $bag): array
    {
        $data = $request->validateWithBag($bag, [
            'project_code' => ['nullable', 'string', 'max:50', Rule::unique('projects', 'project_code')->ignore($project?->id)],
            'project_name' => ['required', 'string', 'max:200', Rule::unique('projects', 'project_name')->ignore($project?->id)],
            'status' => ['required', Rule::in(config('karyawan.options.project_status'))],
        ], ['project_name.unique' => 'Nama proyek sudah ada.', 'project_code.unique' => 'Kode proyek sudah dipakai.']);
        $data['project_code'] = isset($data['project_code']) ? trim($data['project_code']) : null;
        $data['project_name'] = trim($data['project_name']);

        return $data;
    }
}

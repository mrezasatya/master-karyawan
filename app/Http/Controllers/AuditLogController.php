<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()->with('actor.employee')
            ->when($request->query('entity'), fn ($q, $e) => $q->where('entity_type', $e))
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
            ->when($request->query('entity_id'), fn ($q, $id) => $q->where('entity_id', $id))
            ->orderByDesc('id')
            ->paginate(50)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'entities' => AuditLog::query()->distinct()->orderBy('entity_type')->pluck('entity_type'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}

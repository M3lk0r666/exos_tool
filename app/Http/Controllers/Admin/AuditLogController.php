<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('audit.view');

        $logs = AuditLog::with('user:id,name')
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->integer('user')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $users = User::orderBy('name')->pluck('name', 'id');
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit.index', compact('logs', 'users', 'actions'));
    }
}

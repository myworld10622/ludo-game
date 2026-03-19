<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('admin.audit-logs.index', [
            'logs' => AuditLog::query()
                ->latest()
                ->paginate(20),
        ]);
    }
}

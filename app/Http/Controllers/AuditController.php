<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Audit Log — menu tersendiri untuk penelusuran formal (v3.1 §9/§10).
 * Otorisasi via route (can:audit.view).
 */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user', 'document')->latest('created_at');

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }
        if ($q = $request->input('q')) {
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('nrp', 'like', "%{$q}%"));
        }

        return view('audit.index', [
            'logs' => $query->paginate(30)->withQueryString(),
            'filters' => $request->only('action', 'q'),
        ]);
    }
}

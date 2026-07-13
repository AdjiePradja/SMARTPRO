<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /** "Waiting for approval" page for pending/rejected accounts (Task 1.5). */
    public function pending(Request $request)
    {
        $user = $request->user();

        // Active users have no business here — send them to the dashboard.
        if ($user->status === 'active') {
            return redirect()->route('dashboard');
        }

        return view('auth.pending', [
            'name' => $user->name,
            'status' => $user->status,
        ]);
    }
}

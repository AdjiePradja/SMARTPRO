<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

    /** Informasi Akun — tersedia untuk semua role (v3.1 §7). */
    public function account(Request $request)
    {
        return view('account.info', ['user' => $request->user()->load('department', 'roles')]);
    }

    /**
     * Sunting info akun sendiri: foto profil, nomor HP, email. Field identitas
     * (nama, NRP, jabatan, departemen, peran) tetap dikelola admin — tak diubah
     * di sini. Foto lama diganti/dihapus dengan bersih.
     */
    public function updateAccount(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $user->nomor_hp = $validated['nomor_hp'] ?? null;
        $user->email = $validated['email'] ?? null;

        // Hapus foto (jika diminta) atau ganti dengan yang baru.
        if ($request->boolean('remove_photo') && $user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $user->photo_path = null;
        }

        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $user->photo_path = $request->file('photo')->store('avatars', 'public');
        }

        $user->save();

        return redirect()->route('account.info')->with('status', 'Informasi akun berhasil diperbarui.');
    }
}

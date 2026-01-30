<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LockScreenController extends Controller
{
    /**
     * Show the lock screen page.
     */
    public function show()
    {
        return view('auth.lock-screen');
    }

    /**
     * Unlock: validate password and clear locked session.
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        session()->forget('locked');

        return redirect()->route('admin.dashboard');
    }

    /**
     * Set session locked and redirect to lock screen.
     */
    public function lock(Request $request)
    {
        session(['locked' => true]);

        return redirect()->route('lock-screen');
    }
}

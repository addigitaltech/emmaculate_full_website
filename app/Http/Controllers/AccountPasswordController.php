<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Signed-in teachers, parents and staff change their own password here (students sign in with Student ID and PIN instead). */
class AccountPasswordController extends Controller
{
    public function edit(Request $request): View
    {
        abort_if($request->user()->hasRole('Student'), 403);

        return view('portal.password', ['settings' => \App\Models\SchoolSettings::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasRole('Student'), 403);

        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();

        return redirect()->route('portal.dashboard')->with('success', 'Your password has been changed.');
    }
}

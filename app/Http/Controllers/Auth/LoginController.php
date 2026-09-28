<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        // A logged-in player mid-game has their seat tracked under player_id_{pin} in this
        // same session. Invalidating the session for logout would silently kick them out of
        // that game too, so carry those keys over into the fresh session.
        $playerSeats = collect($request->session()->all())
            ->filter(fn ($value, $key) => str_starts_with($key, 'player_id_'));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        foreach ($playerSeats as $key => $value) {
            $request->session()->put($key, $value);
        }

        return redirect()->route('play.join');
    }
}

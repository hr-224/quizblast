<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlayerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PlayerAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:player_accounts'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $account = PlayerAccount::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        session(['player_account_id' => $account->id]);

        return redirect()->route('play.join')->with('success', 'Account created! Join a game to start tracking your stats.');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $account = PlayerAccount::where('email', $request->email)->first();

        if (!$account || !Hash::check($request->password, $account->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        session(['player_account_id' => $account->id]);

        return redirect()->route('play.join')->with('success', 'Logged in!');
    }

    public function logout()
    {
        session()->forget('player_account_id');
        return redirect()->route('play.join');
    }

    public function stats()
    {
        $accountId = session('player_account_id');
        if (!$accountId) return redirect()->route('player.login');

        $account     = PlayerAccount::findOrFail($accountId);
        $recentGames = $account->getRecentGames();

        return view('auth.player-stats', compact('account', 'recentGames'));
    }
}

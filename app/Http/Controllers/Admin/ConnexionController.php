<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConnexionController extends Controller
{
    public function formulaire(): View
    {
        return view('admin.connexion');
    }

    public function connecte(Request $request): RedirectResponse
    {
        $identifiants = $request->validate(
            ['email' => ['required', 'email'], 'password' => ['required', 'string']],
            ['email.*' => 'Indique ton e-mail.', 'password.*' => 'Indique ton mot de passe.'],
        );

        // 5 essais par minute pour un même e-mail depuis une même adresse IP
        $cle = 'connexion:'.Str::lower($identifiants['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            throw ValidationException::withMessages(['email' => 'Trop d\'essais. Réessaie dans '.RateLimiter::availableIn($cle).' secondes.']);
        }

        if (! Auth::attempt($identifiants, $request->boolean('souvenir'))) {
            RateLimiter::hit($cle, 60);
            throw ValidationException::withMessages(['email' => 'E-mail ou mot de passe incorrect.']);
        }

        RateLimiter::clear($cle);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.tableau'));
    }

    public function deconnecte(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.connexion');
    }
}

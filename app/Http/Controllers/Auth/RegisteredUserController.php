<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Onderdeel: Accountaanmaak met gevalideerde invoer, wachtwoordhash en gecontroleerde roltoekenning.
 * Eisen: FE-01, FE-13, RV-02, RV-03, RV-07, TE-03, TE-04.
 * Ontwerp: T-03 (accountaanmaak), T-24 (roltoegang).
 * Bouw: T-04 (accountaanmaak), T-25 (roltoegang), T-27 (wachtwoordopslag), T-29 (invoercontrole).
 * Geplande controle: T-05, T-26, T-28, T-30.
 */
class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user() && $request->user()->role !== 'inspector') {
            return to_route('dashboard');
        }

        return Inertia::render('auth/register', [
            'canCreateInspector' => $request->user()?->role === 'inspector',
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $canCreateInspector = $request->user()?->role === 'inspector';

        abort_if($request->user() && ! $canCreateInspector, 403);
        abort_if($request->boolean('is_inspector') && ! $canCreateInspector, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_inspector' => ['sometimes', 'boolean'],
        ], [
            'password.min' => 'Je wachtwoord moet minimaal :min tekens bevatten.',
            'password.confirmed' => 'De wachtwoorden komen niet overeen.',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->role = $canCreateInspector && $request->boolean('is_inspector') ? 'inspector' : 'customer';
        $user->save();

        event(new Registered($user));

        if ($canCreateInspector) {
            return to_route('register')->with('status', 'Het account is aangemaakt.');
        }

        Auth::login($user);

        return to_route('dashboard');
    }
}

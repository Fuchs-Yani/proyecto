<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:beneficiario,profesor,student'],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'career_year' => [
                Rule::requiredIf(fn (): bool => $request->input('role') === 'student'),
                'nullable',
                'integer',
                Rule::in([2, 3]),
            ],
        ], [
            'career_year.required' => 'Indicá en qué año de la carrera estás.',
            'career_year.integer' => 'El año de la carrera debe ser 2° o 3°.',
            'career_year.in' => 'El año de la carrera debe ser 2° o 3°.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'institution_name' => $request->role === 'beneficiario' ? $request->institution_name : null,
            'career_year' => $request->role === 'student' ? (int) $request->career_year : null,
        ]);

        // El rol se guarda únicamente en Spatie; users.role fue eliminado.
        $user->assignRole(Role::firstOrCreate([
            'name' => $request->role,
            'guard_name' => 'web',
        ]));

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Roles disponibles con su explicación (se muestran en la UI). */
    public const ROLES = [
        'admin' => [
            'label' => 'Administrador',
            'description' => 'Acceso total: además de operar, gestiona usuarios y roles, reglas de análisis, configuración y auditoría.',
        ],
        'engineer' => [
            'label' => 'Ingeniero',
            'description' => 'Operación completa: clientes, equipos, subida y análisis de capturas, edición y emisión de reportes. Sin acceso a administración.',
        ],
        'reader' => [
            'label' => 'Lectura',
            'description' => 'Solo consulta: puede ver clientes, equipos, análisis y reportes, sin modificar nada ni subir archivos.',
        ],
    ];

    public function index(): View
    {
        $this->authorize('users.manage');

        $users = User::with('roles:id,name')->orderBy('name')->paginate(15);

        return view('admin.users.index', ['users' => $users, 'roles' => self::ROLES]);
    }

    public function create(): View
    {
        $this->authorize('users.manage');

        return view('admin.users.form', ['user' => null, 'roles' => self::ROLES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('users.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::default()],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->assignRole($data['role']);

        AuditLogger::log('created', $user, ['email' => $user->email, 'role' => $data['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario «{$user->name}» creado con rol ".self::ROLES[$data['role']]['label'].'.');
    }

    public function edit(User $user): View
    {
        $this->authorize('users.manage');

        return view('admin.users.form', ['user' => $user, 'roles' => self::ROLES]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::default()],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ]);

        // Protección: no degradar al último administrador
        if ($user->hasRole('admin') && $data['role'] !== 'admin' && $this->isLastAdmin($user)) {
            return back()->withErrors(['role' => 'No puedes quitar el rol al único administrador del sistema.']);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        $user->syncRoles([$data['role']]);

        AuditLogger::log('updated', $user, ['email' => $user->email, 'role' => $data['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario «{$user->name}» actualizado.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.manage');

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta desde aquí.']);
        }

        if ($user->hasRole('admin') && $this->isLastAdmin($user)) {
            return back()->withErrors(['user' => 'No puedes eliminar al único administrador del sistema.']);
        }

        AuditLogger::log('deleted', $user, ['email' => $user->email]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado.');
    }

    private function isLastAdmin(User $user): bool
    {
        return User::role('admin')->where('id', '!=', $user->id)->count() === 0;
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser; // 1. Importar la interfaz de Filament
use Filament\Panel; // Importar Panel para el type-hinting
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'institution_name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser // 2. Implementar FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Ideas publicadas por el usuario.
     */
    public function projectIdeas(): HasMany
    {
        return $this->hasMany(ProjectIdea::class, 'organizer_id');
    }

    /**
     * Postulaciones del usuario como estudiante.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'student_id');
    }

    /**
     * Antes de borrar al usuario, elimina sus ideas mediante Eloquent para que
     * también se borren definitivamente los archivos de sus adjuntos. Si no,
     * la FK en cascada borra las filas pero deja los archivos huérfanos.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->projectIdeas()->get()->each(function (ProjectIdea $projectIdea): void {
                $projectIdea->delete();
            });
        });
    }

    /**
     * Devuelve los roles del usuario con etiquetas en español.
     */
    public function roleLabel(): string
    {
        return $this->getRoleNames()
            ->map(fn (string $role): string => match ($role) {
                'student' => 'Estudiante',
                'beneficiario' => 'Beneficiario',
                'profesor' => 'Profesor',
                'admin' => 'Administrador',
                default => ucfirst($role),
            })
            ->join(', ');
    }

    /**
     * Determina quiénes pueden acceder al panel de administración.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser; // 1. Importar la interfaz de Filament
use Filament\Panel; // Importar Panel para el type-hinting
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'institution_name', 'career_year', 'profile_photo_path'])]
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
            'career_year' => 'integer',
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
     * Postulación activa del alumno, si ya se postuló a una idea.
     */
    public function activeAssignment(): ?Assignment
    {
        return $this->assignments()
            ->active()
            ->with('projectIdea')
            ->first();
    }

    /**
     * Filtra usuarios por nombre, email o institución.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%')
                ->orWhere('institution_name', 'like', '%'.$term.'%');
        });
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
     * Devuelve la URL pública de la foto de perfil, si tiene una.
     */
    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo_path
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;
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

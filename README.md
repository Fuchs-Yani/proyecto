# Banco de Ideas y Proyectos de Software

Aplicación web para conectar estudiantes, docentes y beneficiarios alrededor de ideas y proyectos de software reales.

## Requisitos

- PHP 8.3 o superior
- Composer
- MySQL
- Node.js y pnpm

## Instalación

La forma rápida es usar el script de Composer, que instala dependencias, crea el `.env`, genera la clave, migra **y carga todos los datos demo**:

```bash
composer setup
```

O paso a paso:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
pnpm install
pnpm build
```

> `php artisan migrate --seed` ejecuta `DatabaseSeeder`, que carga roles, permisos, cuentas de los cuatro roles, proyectos en todos los estados, postulaciones, avances, adjuntos y notificaciones.

Para volver a dejar el escenario demo como recién instalado:

```bash
php artisan db:seed
```

Para borrar todo y recrearlo desde cero:

```bash
php artisan migrate:fresh --seed
```

## Qué incluye el escenario demo

- **9 proyectos** en todos los estados:
  - disponibles con cupo libre, con cupo completo y pendientes de revisión;
  - en desarrollo (incluido uno iniciado antes de completar el cupo);
  - terminados (con las 5 etapas aprobadas);
  - cancelado con motivo.
- **11 postulaciones** en todos los estados: postulado, aceptado, en progreso, completado, rechazado y abandonado.
- **20 avances** repartidos entre Análisis, Diseño, Desarrollo, Pruebas y Entrega, con avances aprobados, pendientes de revisión y rechazados.
- Adjuntos PDF/PNG, imágenes de evidencia en avances y notificaciones para alumnos y beneficiarios.
- El proyecto **“Sistema web para mostrar animalitos en adopción”** tiene 1 aceptado y 1 pendiente con el cupo completo, ideal para probar el cartel de inicio del proyecto.

Todos los seeders son idempotentes: se pueden ejecutar las veces que haga falta sin duplicar registros.

## Cuentas demo

Contraseña para todas: `password123`.

| Rol | Correo |
| --- | --- |
| Admin | `admin@admin.com` |
| Profesor | `profesor@test.com` |
| Profesor | `ricardo.salas@test.com` |
| Alumno 2° año | `estudiante@test.com` |
| Alumno 2° año | `juan.perez@test.com` |
| Alumno 2° año | `sofia.ramirez@test.com` |
| Alumno 2° año | `mateo.alvarez@test.com` |
| Alumno 3° año | `valentina.torres@test.com` |
| Alumno 3° año | `nicolas.herrera@test.com` |
| Alumno 3° año | `camila.rojas@test.com` |
| Alumno 3° año | `lucas.medina@test.com` |
| Beneficiario | `refugio@test.com` |
| Beneficiario | `escuela@test.com` |
| Beneficiario | `maria@test.com` |
| Beneficiario | `lucia@test.com` |
| Beneficiario | `beneficiario@test.com` |
| Beneficiario | `salud@test.com` |
| Beneficiario | `vecinal@test.com` |
| Beneficiario | `deportes@test.com` |
| Beneficiario | `estudio@test.com` |

> Estas cuentas son solo para pruebas. Antes de publicar el proyecto, eliminarlas y crear las cuentas reales con `php artisan app:create-admin`.

## Seeders individuales (opcional)

Cada seeder se puede ejecutar por separado:

```bash
php artisan db:seed --class=DemoDataSeeder
php artisan db:seed --class=TestAccountsSeeder
php artisan db:seed --class=StudentAccountsSeeder
php artisan db:seed --class=ProfessorAccountSeeder
php artisan db:seed --class=BeneficiaryProjectsSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder
```

- **`RolesAndPermissionsSeeder`**: solo roles y permisos.
- **`TestAccountsSeeder`**: admin, profesor, alumno y beneficiario base.
- **`StudentAccountsSeeder`**: 7 alumnos adicionales.
- **`ProfessorAccountSeeder`**: profesor adicional.
- **`BeneficiaryProjectsSeeder`**: beneficiarios con sus ideas y adjuntos.
- **`DemoDataSeeder`**: todo el escenario completo. `DatabaseSeeder` lo ejecuta automáticamente.

## Roles

- `student`: explora ideas disponibles, se postula/retira y envía avances.
- `beneficiario`: publica ideas, administra sus adjuntos (máximo 2 archivos por idea) y consulta el progreso aprobado.
- `profesor`: revisa ideas, define cupos, acepta postulantes y aprueba o rechaza avances.
- `admin`: acceso completo al panel de Filament.

Los roles se guardan únicamente con Spatie Permission. Las columnas antiguas `users.role` e `users.is_admin` fueron eliminadas para evitar datos duplicados.

## Pruebas

```bash
php artisan test
```

## Panel de administración

El panel Filament está disponible en `/admin`.

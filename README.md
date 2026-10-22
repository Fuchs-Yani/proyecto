# Banco de Ideas y Proyectos de Software

Aplicación web para conectar estudiantes, docentes y beneficiarios alrededor de ideas y proyectos de software reales.

## Requisitos

- PHP 8.3 o superior
- Composer
- MySQL
- Node.js y npm

## Instalación

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan db:seed
npm install
npm run build
```

`php artisan db:seed` crea **solo roles y permisos**. No carga usuarios, ideas, postulaciones ni adjuntos de ejemplo.

## Primer administrador

Después de migrar y sembrar la base, crear el primer usuario administrador real:

```bash
php artisan app:create-admin
```

El comando pide nombre, correo y contraseña. También acepta opciones:

```bash
php artisan app:create-admin --name="Nombre" --email="admin@ejemplo.com" --password="una-clave-segura"
```

Luego se puede ingresar desde `/login` o directamente desde `/admin/login`.

## Cuentas de prueba (solo desarrollo)

No se crean automáticamente. Para recrearlas:

```bash
php artisan db:seed --class=TestAccountsSeeder
```

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Admin | `admin@admin.com` | `password123` |
| Estudiante | `estudiante@test.com` | `password123` |
| Beneficiario | `beneficiario@test.com` | `password123` |
| Profesor | `profesor@test.com` | `password123` |

> Estas cuentas son solo para pruebas. Antes de publicar el proyecto, eliminarlas y crear las cuentas reales.

## Proyectos de ejemplo (solo desarrollo)

Para crear 4 beneficiarios con una idea cada uno, más un PDF de requerimientos y dos imágenes de inspiración por idea:

```bash
php artisan db:seed --class=BeneficiaryProjectsSeeder
```

| Beneficiario | Correo | Idea |
| --- | --- | --- |
| Refugio Huellitas de Esperanza | `refugio@test.com` | Sistema de adopciones |
| Escuela Primaria N° 12 | `escuela@test.com` | Gestión de biblioteca escolar |
| María González | `maria@test.com` | Gestión de despensa |
| Lucía Fernández | `lucia@test.com` | Gestión de medicación |

Todas usan la contraseña `password123`. El seeder es idempotente: se puede volver a ejecutar sin duplicar las ideas ni los adjuntos.

## Estudiantes de prueba (solo desarrollo)

Para crear 7 estudiantes adicionales:

```bash
php artisan db:seed --class=StudentAccountsSeeder
```

| Nombre | Correo |
| --- | --- |
| Juan Pérez | `juan.perez@test.com` |
| Sofía Ramírez | `sofia.ramirez@test.com` |
| Mateo Álvarez | `mateo.alvarez@test.com` |
| Valentina Torres | `valentina.torres@test.com` |
| Nicolás Herrera | `nicolas.herrera@test.com` |
| Camila Rojas | `camila.rojas@test.com` |
| Lucas Medina | `lucas.medina@test.com` |

Todos usan la contraseña `password123`.

## Profesor adicional (solo desarrollo)

Para crear un profesor adicional:

```bash
php artisan db:seed --class=ProfessorAccountSeeder
```

| Nombre | Correo | Contraseña |
| --- | --- | --- |
| Ricardo Salas | `ricardo.salas@test.com` | `password123` |

## Roles

- `student`: explora ideas disponibles, se postula y retira postulaciones.
- `beneficiario`: publica ideas, administra sus adjuntos y ve las postulaciones recibidas.
- `profesor`: consulta ideas y postulaciones para seguimiento.
- `admin`: acceso completo al panel de Filament.

Los roles se guardan únicamente con Spatie Permission. Las columnas antiguas `users.role` e `users.is_admin` fueron eliminadas para evitar datos duplicados.

## Pruebas

```bash
php artisan test
```

## Panel de administración

El panel Filament está disponible en `/admin`.

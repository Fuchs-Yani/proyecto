<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Attachment;
use App\Models\ProjectIdea;
use App\Models\ProjectUpdate;
use App\Models\ProjectUpdateImage;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\ApplicationReviewed;
use App\Notifications\ProjectIdeaReviewed;
use App\Notifications\ProjectUpdateReviewed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Carga un escenario completo para mostrar todas las funcionalidades del
 * sistema: proyectos en cada estado, postulaciones en cada estado y avances
 * aprobados, pendientes y rechazados en las cinco etapas.
 *
 * Se puede ejecutar las veces que haga falta: vuelve a dejar el escenario
 * demo en el mismo estado sin duplicar registros.
 *
 * Uso:
 * php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private User $professor;

    /**
     * @var array<string, User>
     */
    private array $students = [];

    /**
     * @var array<string, User>
     */
    private array $beneficiaries = [];

    /**
     * @var array<string, ProjectIdea>
     */
    private array $projects = [];

    /**
     * @var array<string, Assignment>
     */
    private array $assignments = [];

    /**
     * @var array<string, ProjectUpdate>
     */
    private array $updates = [];

    public function run(): void
    {
        // Roles, permisos y cuentas base.
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(TestAccountsSeeder::class);
        $this->call(StudentAccountsSeeder::class);
        $this->call(ProfessorAccountSeeder::class);
        $this->call(BeneficiaryProjectsSeeder::class);

        $this->seedUsers();
        $this->seedProjects();
        $this->resetDemoActivity();
        $this->seedAssignments();
        $this->seedUpdates();
        $this->seedNotifications();

        $this->command?->info('Escenario demo completo cargado.');
        $this->command?->info('Contraseña de todas las cuentas: password123');
        $this->command?->info('Profesor: profesor@test.com · Alumno: estudiante@test.com · Beneficiario: escuela@test.com · Admin: admin@admin.com');
    }

    private function seedUsers(): void
    {
        $this->user('Administrador General', 'admin@admin.com', 'admin');

        $this->professor = $this->user('Profesor de Prueba', 'profesor@test.com', 'profesor');
        $this->user('Ricardo Salas', 'ricardo.salas@test.com', 'profesor');

        $students = [
            'estudiante' => ['Estudiante de Prueba', 'estudiante@test.com', 2],
            'juan' => ['Juan Pérez', 'juan.perez@test.com', 2],
            'sofia' => ['Sofía Ramírez', 'sofia.ramirez@test.com', 2],
            'mateo' => ['Mateo Álvarez', 'mateo.alvarez@test.com', 2],
            'valentina' => ['Valentina Torres', 'valentina.torres@test.com', 3],
            'nicolas' => ['Nicolás Herrera', 'nicolas.herrera@test.com', 3],
            'camila' => ['Camila Rojas', 'camila.rojas@test.com', 3],
            'lucas' => ['Lucas Medina', 'lucas.medina@test.com', 3],
        ];

        foreach ($students as $key => [$name, $email, $careerYear]) {
            $this->students[$key] = $this->user($name, $email, 'student', null, $careerYear);
        }

        $beneficiaries = [
            'refugio' => ['Refugio Huellitas de Esperanza', 'refugio@test.com', 'Refugio Huellitas de Esperanza'],
            'escuela' => ['Escuela Primaria N° 12', 'escuela@test.com', 'Escuela Primaria N° 12'],
            'maria' => ['María González', 'maria@test.com', null],
            'lucia' => ['Lucía Fernández', 'lucia@test.com', null],
            'beneficiario' => ['Beneficiario de Prueba', 'beneficiario@test.com', 'Institución de Prueba'],
            'salud' => ['Centro de Salud San Martín', 'salud@test.com', 'Centro de Salud San Martín'],
            'vecinal' => ['Asociación Vecinal El Progreso', 'vecinal@test.com', 'Asociación Vecinal El Progreso'],
            'deportes' => ['Complejo Deportivo Los Pinos', 'deportes@test.com', 'Complejo Deportivo Los Pinos'],
            'estudio' => ['Estudio Contable Reyes', 'estudio@test.com', 'Estudio Contable Reyes'],
        ];

        foreach ($beneficiaries as $key => [$name, $email, $institution]) {
            $this->beneficiaries[$key] = $this->user($name, $email, 'beneficiario', $institution);
        }
    }

    private function user(
        string $name,
        string $email,
        string $role,
        ?string $institution = null,
        ?int $careerYear = null,
    ): User {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password123'),
                'institution_name' => $institution,
                'career_year' => $careerYear,
            ],
        );

        $user->syncRoles([$role]);

        return $user;
    }

    private function seedProjects(): void
    {
        $projects = [
            'animalitos' => [
                'title' => 'Sistema web para mostrar animalitos en adopción',
                'organizer' => 'refugio',
                'description' => 'Sitio web para que el refugio publique los animales disponibles, muestre sus fichas y reciba solicitudes de adopción en línea.',
                'status' => 'available',
                'max_students' => 2,
                'tags' => ['adopción', 'animales', 'refugio'],
                'accent' => '#C76D4A',
            ],
            'biblioteca' => [
                'title' => 'Sistema de gestión para la biblioteca escolar',
                'organizer' => 'escuela',
                'description' => 'Sistema para catalogar los libros de la escuela, registrar préstamos y devoluciones y conocer la disponibilidad en tiempo real.',
                'status' => 'in_development',
                'max_students' => 2,
                'tags' => ['educación', 'biblioteca', 'préstamos'],
                'accent' => '#1F3A5F',
            ],
            'despensa' => [
                'title' => 'Sistema de gestión para una despensa',
                'organizer' => 'maria',
                'description' => 'Sistema para controlar productos, ventas, compras y stock de una despensa, con alertas y reportes básicos.',
                'status' => 'available',
                'max_students' => 3,
                'tags' => ['comercio', 'inventario', 'stock'],
                'accent' => '#2F6B4F',
            ],
            'medicacion' => [
                'title' => 'Sistema de gestión para tomar medicación',
                'organizer' => 'lucia',
                'description' => 'Aplicación para registrar medicamentos, recibir recordatorios y llevar un historial de dosis tomadas, con una vista para el cuidador.',
                'status' => 'completed',
                'max_students' => 1,
                'tags' => ['salud', 'recordatorios', 'accesibilidad'],
                'accent' => '#5B7DB1',
            ],
            'prestamero' => [
                'title' => 'Prestamero Digital',
                'organizer' => 'beneficiario',
                'description' => 'Aplicación para registrar préstamos de dinero, cuotas, pagos y saldos pendientes de una pequeña financiera de barrio.',
                'status' => 'in_development',
                'max_students' => 2,
                'started_early' => true,
                'tags' => ['finanzas', 'préstamos', 'seguimiento'],
                'accent' => '#6D28D9',
            ],
            'turnos' => [
                'title' => 'Sistema de turnos para el centro de salud',
                'organizer' => 'salud',
                'description' => 'Sistema para que los pacientes reserven turnos con los profesionales del centro de salud y reciban recordatorios.',
                'status' => 'available',
                'max_students' => null,
                'tags' => ['salud', 'turnos'],
                'accent' => '#0F766E',
            ],
            'noticias' => [
                'title' => 'Portal de noticias del barrio',
                'organizer' => 'vecinal',
                'description' => 'Portal para publicar noticias, eventos y comunicados de la asociación vecinal, con categorías y suscripción por correo.',
                'status' => 'available',
                'max_students' => 4,
                'tags' => ['comunidad', 'noticias'],
                'accent' => '#B45309',
            ],
            'canchas' => [
                'title' => 'Sistema de reservas para canchas de fútbol',
                'organizer' => 'deportes',
                'description' => 'Sistema para reservar turnos de las canchas, cobrar señas y administrar la disponibilidad del complejo deportivo.',
                'status' => 'cancelled',
                'max_students' => 3,
                'cancellation_reason' => 'El beneficiario decidió postergar el proyecto hasta el próximo año por falta de presupuesto.',
                'cancelled_at' => now()->subDays(6),
                'tags' => ['deportes', 'reservas'],
                'accent' => '#15803D',
            ],
            'facturacion' => [
                'title' => 'Sistema de facturación para estudio contable',
                'organizer' => 'estudio',
                'description' => 'Sistema para emitir facturas, registrar pagos y generar reportes mensuales del estudio contable.',
                'status' => 'completed',
                'max_students' => 1,
                'tags' => ['contabilidad', 'facturación'],
                'accent' => '#7C2D12',
            ],
        ];

        foreach ($projects as $key => $data) {
            $idea = ProjectIdea::updateOrCreate(
                [
                    'organizer_id' => $this->beneficiaries[$data['organizer']]->id,
                    'title' => $data['title'],
                ],
                [
                    'description' => $data['description'],
                    'status' => $data['status'],
                    'max_students' => $data['max_students'],
                    'started_early' => $data['started_early'] ?? false,
                    'cancellation_reason' => $data['cancellation_reason'] ?? null,
                    'cancelled_at' => $data['cancelled_at'] ?? null,
                ],
            );

            $tagIds = collect($data['tags'])
                ->map(fn (string $tag): int => Tag::firstOrCreate(['name' => $tag])->id)
                ->all();

            $idea->tags()->sync($tagIds);

            $this->projects[$key] = $idea;
            $this->seedAttachments($idea, $data['accent']);
        }
    }

    /**
     * Deja los proyectos demo sin postulaciones ni avances para recrearlos.
     * El borrado usa Eloquent para eliminar también las imágenes del disco.
     */
    private function resetDemoActivity(): void
    {
        foreach ($this->projects as $idea) {
            $idea->assignments()->with('updates.images')->get()->each(
                fn (Assignment $assignment) => $assignment->delete(),
            );
        }
    }

    private function seedAssignments(): void
    {
        // Proyecto disponible con el cupo completo entre 1 aceptado y 1
        // pendiente: sirve para ver el cartel de "iniciar proyecto".
        $this->assignments['animalitos_juan'] = $this->assignment(
            $this->projects['animalitos'],
            $this->students['juan'],
            Assignment::STATUS_ACCEPTED,
            now()->subDays(18),
            now()->subDays(16),
        );
        $this->assignments['animalitos_camila'] = $this->assignment(
            $this->projects['animalitos'],
            $this->students['camila'],
            Assignment::STATUS_APPLIED,
            now()->subDays(4),
        );

        // Proyecto en desarrollo con dos integrantes y avances en varias etapas.
        $this->assignments['biblioteca_sofia'] = $this->assignment(
            $this->projects['biblioteca'],
            $this->students['sofia'],
            Assignment::STATUS_ACCEPTED,
            now()->subDays(25),
            now()->subDays(23),
        );
        $this->assignments['biblioteca_mateo'] = $this->assignment(
            $this->projects['biblioteca'],
            $this->students['mateo'],
            Assignment::STATUS_IN_PROGRESS,
            now()->subDays(24),
            now()->subDays(22),
        );

        // Proyecto disponible con lugar libre para probar una postulación.
        $this->assignments['despensa_lucas'] = $this->assignment(
            $this->projects['despensa'],
            $this->students['lucas'],
            Assignment::STATUS_APPLIED,
            now()->subDays(3),
        );

        // Proyecto disponible con una postulación pendiente de evaluación.
        $this->assignments['noticias_nicolas'] = $this->assignment(
            $this->projects['noticias'],
            $this->students['nicolas'],
            Assignment::STATUS_APPLIED,
            now()->subDays(2),
        );

        // Proyecto en desarrollo iniciado antes de completar el cupo.
        $this->assignments['prestamero_valentina'] = $this->assignment(
            $this->projects['prestamero'],
            $this->students['valentina'],
            Assignment::STATUS_IN_PROGRESS,
            now()->subDays(20),
            now()->subDays(18),
        );

        // Proyecto terminado con todas las etapas aprobadas.
        $this->assignments['medicacion_lucas'] = $this->assignment(
            $this->projects['medicacion'],
            $this->students['lucas'],
            Assignment::STATUS_COMPLETED,
            now()->subDays(90),
            now()->subDays(88),
            now()->subDays(15),
        );

        $this->assignments['facturacion_lucas'] = $this->assignment(
            $this->projects['facturacion'],
            $this->students['lucas'],
            Assignment::STATUS_COMPLETED,
            now()->subDays(70),
            now()->subDays(68),
            now()->subDays(10),
        );

        // Historial de una postulación rechazada y otra abandonada.
        $this->assignments['canchas_lucas'] = $this->assignment(
            $this->projects['canchas'],
            $this->students['lucas'],
            Assignment::STATUS_REJECTED,
            now()->subDays(30),
        );
        $this->assignments['canchas_camila'] = $this->assignment(
            $this->projects['canchas'],
            $this->students['camila'],
            Assignment::STATUS_WITHDRAWN,
            now()->subDays(28),
            now()->subDays(27),
        );
    }

    private function assignment(
        ProjectIdea $idea,
        User $student,
        string $status,
        mixed $appliedAt,
        mixed $startedAt = null,
        mixed $finishedAt = null,
    ): Assignment {
        $updatedAt = $finishedAt ?? $startedAt ?? $appliedAt;

        return Assignment::create([
            'project_idea_id' => $idea->id,
            'student_id' => $student->id,
            'status' => $status,
            'applied_at' => $appliedAt,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'created_at' => $appliedAt,
            'updated_at' => $updatedAt,
        ]);
    }

    private function seedUpdates(): void
    {
        $sofia = $this->assignments['biblioteca_sofia'];
        $mateo = $this->assignments['biblioteca_mateo'];
        $valentina = $this->assignments['prestamero_valentina'];
        $lucasMedicacion = $this->assignments['medicacion_lucas'];
        $lucasFacturacion = $this->assignments['facturacion_lucas'];

        // Biblioteca: 3 etapas aprobadas, una pendiente y un avance rechazado.
        $this->updates['biblioteca_analisis_sofia'] = $this->update(
            $sofia,
            ProjectUpdate::STAGE_ANALYSIS,
            ProjectUpdate::STATUS_APPROVED,
            'Relevamos los procesos actuales de la biblioteca: préstamos en cuadernos, control de socios y devoluciones. Documentamos los casos de uso y el alcance del sistema.',
            now()->subDays(20),
        );
        $this->updates['biblioteca_diseno_sofia'] = $this->update(
            $sofia,
            ProjectUpdate::STAGE_DESIGN,
            ProjectUpdate::STATUS_APPROVED,
            'Entregamos los wireframes de catálogo, préstamos y devoluciones, la paleta institucional y el flujo de navegación para bibliotecarios y docentes.',
            now()->subDays(15),
        );
        $this->updates['biblioteca_desarrollo_sofia'] = $this->update(
            $sofia,
            ProjectUpdate::STAGE_DEVELOPMENT,
            ProjectUpdate::STATUS_APPROVED,
            'Implementamos el catálogo con búsqueda por título, autor e ISBN, el registro de préstamos y la vista de disponibilidad en tiempo real.',
            now()->subDays(8),
            ['repository_url' => 'https://github.com/demo/biblioteca-escolar', 'image' => true],
        );
        $this->updates['biblioteca_pruebas_sofia'] = $this->update(
            $sofia,
            ProjectUpdate::STAGE_TESTING,
            ProjectUpdate::STATUS_PENDING,
            'Probamos el alta de un préstamo, la devolución con atraso y la búsqueda por ISBN. Detectamos un detalle en el cálculo de vencimientos y adjuntamos la evidencia.',
            now()->subDays(2),
            ['image' => true],
        );
        $this->updates['biblioteca_analisis_mateo'] = $this->update(
            $mateo,
            ProjectUpdate::STAGE_ANALYSIS,
            ProjectUpdate::STATUS_APPROVED,
            'Analizamos los reportes que usa la biblioteca y definimos los indicadores: libros más prestados, préstamos activos y atrasos.',
            now()->subDays(19),
        );
        $this->updates['biblioteca_diseno_mateo'] = $this->update(
            $mateo,
            ProjectUpdate::STAGE_DESIGN,
            ProjectUpdate::STATUS_REJECTED,
            'Primera propuesta del panel de reportes. Faltan los filtros por curso y el detalle de atrasos.',
            now()->subDays(14),
        );
        $this->updates['biblioteca_desarrollo_mateo'] = $this->update(
            $mateo,
            ProjectUpdate::STAGE_DEVELOPMENT,
            ProjectUpdate::STATUS_APPROVED,
            'Desarrollamos el panel de reportes con filtros por curso y rango de fechas, y la exportación a PDF.',
            now()->subDays(7),
        );

        // Prestamero: iniciado con un solo integrante, con avance pendiente y
        // un avance rechazado.
        $this->updates['prestamero_analisis'] = $this->update(
            $valentina,
            ProjectUpdate::STAGE_ANALYSIS,
            ProjectUpdate::STATUS_APPROVED,
            'Relevamos cómo se registran hoy los préstamos y armamos el modelo de datos de clientes, cuotas y pagos.',
            now()->subDays(15),
            ['image' => true],
        );
        $this->updates['prestamero_diseno'] = $this->update(
            $valentina,
            ProjectUpdate::STAGE_DESIGN,
            ProjectUpdate::STATUS_PENDING,
            'Prototipo de las pantallas de alta de préstamo, calendario de cuotas y registro de pagos.',
            now()->subDays(3),
        );
        $this->updates['prestamero_desarrollo'] = $this->update(
            $valentina,
            ProjectUpdate::STAGE_DEVELOPMENT,
            ProjectUpdate::STATUS_REJECTED,
            'Avance inicial del módulo de pagos. Todavía no valida cuotas vencidas ni saldos parciales.',
            now()->subDays(9),
        );

        // Medicación: proyecto terminado con las cinco etapas aprobadas.
        $this->updates['medicacion_analisis'] = $this->update(
            $lucasMedicacion,
            ProjectUpdate::STAGE_ANALYSIS,
            ProjectUpdate::STATUS_APPROVED,
            'Documentamos los requerimientos de accesibilidad, el flujo de recordatorios y la vista de solo lectura para cuidadores.',
            now()->subDays(80),
        );
        $this->updates['medicacion_diseno'] = $this->update(
            $lucasMedicacion,
            ProjectUpdate::STAGE_DESIGN,
            ProjectUpdate::STATUS_APPROVED,
            'Diseñamos las pantallas con texto grande, alto contraste y botones amplios para registrar una toma en dos toques.',
            now()->subDays(65),
        );
        $this->updates['medicacion_desarrollo'] = $this->update(
            $lucasMedicacion,
            ProjectUpdate::STAGE_DEVELOPMENT,
            ProjectUpdate::STATUS_APPROVED,
            'Implementamos el registro de medicamentos, los horarios y los recordatorios con confirmación de toma.',
            now()->subDays(45),
        );
        $this->updates['medicacion_pruebas'] = $this->update(
            $lucasMedicacion,
            ProjectUpdate::STAGE_TESTING,
            ProjectUpdate::STATUS_APPROVED,
            'Probamos el flujo completo con personas mayores y ajustamos los mensajes y el tamaño de los controles.',
            now()->subDays(30),
        );
        $this->updates['medicacion_entrega'] = $this->update(
            $lucasMedicacion,
            ProjectUpdate::STAGE_DELIVERY,
            ProjectUpdate::STATUS_APPROVED,
            'Entregamos la aplicación, el historial de prueba, el manual de uso y una capacitación breve para Lucía y su cuidador.',
            now()->subDays(15),
            ['image' => true],
        );

        // Facturación: segundo proyecto terminado para el catálogo público.
        $this->updates['facturacion_analisis'] = $this->update(
            $lucasFacturacion,
            ProjectUpdate::STAGE_ANALYSIS,
            ProjectUpdate::STATUS_APPROVED,
            'Relevamos el circuito actual de facturación, los comprobantes y los reportes que necesita el estudio cada mes.',
            now()->subDays(60),
        );
        $this->updates['facturacion_diseno'] = $this->update(
            $lucasFacturacion,
            ProjectUpdate::STAGE_DESIGN,
            ProjectUpdate::STATUS_APPROVED,
            'Diseñamos el alta de facturas, la lista de pagos y el tablero mensual del estudio.',
            now()->subDays(48),
        );
        $this->updates['facturacion_desarrollo'] = $this->update(
            $lucasFacturacion,
            ProjectUpdate::STAGE_DEVELOPMENT,
            ProjectUpdate::STATUS_APPROVED,
            'Implementamos la emisión de facturas, el registro de pagos y la exportación de reportes a PDF y Excel.',
            now()->subDays(35),
        );
        $this->updates['facturacion_pruebas'] = $this->update(
            $lucasFacturacion,
            ProjectUpdate::STAGE_TESTING,
            ProjectUpdate::STATUS_APPROVED,
            'Probamos la emisión de facturas con distintos importes, la anulación y los reportes mensuales.',
            now()->subDays(22),
        );
        $this->updates['facturacion_entrega'] = $this->update(
            $lucasFacturacion,
            ProjectUpdate::STAGE_DELIVERY,
            ProjectUpdate::STATUS_APPROVED,
            'Entregamos el sistema, la carga inicial de clientes y el manual de uso para el estudio contable.',
            now()->subDays(10),
        );
    }

    private function update(
        Assignment $assignment,
        string $stage,
        string $status,
        string $description,
        mixed $createdAt,
        array $options = [],
    ): ProjectUpdate {
        $createdAt = $createdAt ?? now()->subDays(5);
        $approvedAt = $status === ProjectUpdate::STATUS_APPROVED
            ? $createdAt->copy()->addDay()
            : null;

        if ($approvedAt && $approvedAt->isFuture()) {
            $approvedAt = now()->subDay();
        }

        $update = ProjectUpdate::create([
            'assignment_id' => $assignment->id,
            'project_idea_id' => $assignment->project_idea_id,
            'student_id' => $assignment->student_id,
            'description' => $description,
            'repository_url' => $options['repository_url'] ?? null,
            'stage' => $stage,
            'status' => $status,
            'approved_at' => $approvedAt,
            'approved_by' => $approvedAt ? $this->professor->id : null,
            'created_at' => $createdAt,
            'updated_at' => $approvedAt ?? $createdAt,
        ]);

        if ($options['image'] ?? false) {
            $this->attachImage($update, $stage);
        }

        return $update;
    }

    private function seedNotifications(): void
    {
        $users = array_merge(array_values($this->students), array_values($this->beneficiaries));

        foreach ($users as $user) {
            $user->notifications()->delete();
        }

        $this->students['juan']->notify(
            new ApplicationReviewed($this->assignments['animalitos_juan'], Assignment::STATUS_ACCEPTED),
        );

        $this->students['sofia']->notify(
            new ProjectUpdateReviewed($this->updates['biblioteca_desarrollo_sofia'], ProjectUpdate::STATUS_APPROVED),
        );

        $this->students['mateo']->notify(
            new ProjectUpdateReviewed($this->updates['biblioteca_diseno_mateo'], ProjectUpdate::STATUS_REJECTED),
        );

        $this->students['valentina']->notify(
            new ProjectUpdateReviewed($this->updates['prestamero_analisis'], ProjectUpdate::STATUS_APPROVED),
        );

        $this->students['lucas']->notify(
            new ProjectUpdateReviewed($this->updates['medicacion_entrega'], ProjectUpdate::STATUS_APPROVED),
        );

        $reviewedProjects = [
            'refugio' => 'animalitos',
            'escuela' => 'biblioteca',
            'maria' => 'despensa',
            'lucia' => 'medicacion',
            'beneficiario' => 'prestamero',
            'vecinal' => 'noticias',
            'deportes' => 'canchas',
            'estudio' => 'facturacion',
        ];

        foreach ($reviewedProjects as $beneficiary => $project) {
            $idea = $this->projects[$project];

            $this->beneficiaries[$beneficiary]->notify(
                new ProjectIdeaReviewed($idea, $idea->max_students),
            );
        }
    }

    private function seedAttachments(ProjectIdea $idea, string $accent): void
    {
        if ($idea->attachments()->exists()) {
            return;
        }

        $directory = 'attachments/demo/'.$idea->id;

        $pdfPath = $directory.'/requerimientos.pdf';
        Storage::disk('public')->put(
            $pdfPath,
            $this->makePdf($idea->title, $idea->description, $accent),
        );

        Attachment::create([
            'project_idea_id' => $idea->id,
            'file_name' => 'Requerimientos - '.$idea->title.'.pdf',
            'file_path' => $pdfPath,
            'file_type' => 'pdf',
            'uploaded_at' => now(),
        ]);

        $pngPath = $directory.'/inspiracion.png';
        Storage::disk('public')->put(
            $pngPath,
            $this->makeImage('Inspiración visual', $idea->title, $accent, 1000, 600),
        );

        Attachment::create([
            'project_idea_id' => $idea->id,
            'file_name' => 'Inspiración 1 - '.$idea->title.'.png',
            'file_path' => $pngPath,
            'file_type' => 'png',
            'uploaded_at' => now(),
        ]);
    }

    private function attachImage(ProjectUpdate $update, string $stage): void
    {
        $path = 'progress-images/demo/avance-'.$update->id.'.png';
        $png = $this->makeImage(
            ProjectUpdate::STAGES[$stage] ?? 'Avance',
            $update->student?->name ?? 'Equipo del proyecto',
            '#1D4ED8',
            800,
            450,
        );

        Storage::disk('public')->put($path, $png);

        ProjectUpdateImage::create([
            'project_update_id' => $update->id,
            'file_path' => $path,
            'original_name' => 'evidencia-'.$stage.'.png',
            'mime_type' => 'image/png',
            'size' => strlen($png),
        ]);
    }

    private function makeImage(string $heading, string $subheading, string $accent, int $width, int $height): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return $this->fallbackPng();
        }

        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, $this->allocateColor($image, '#F8FAFC'));
        imagefilledrectangle($image, 0, 0, $width, (int) ($height * 0.28), $this->allocateColor($image, $accent));

        foreach (['#FFFFFF', '#E2E8F0', '#CBD5E1'] as $index => $hex) {
            $barWidth = (int) ($width * 0.28);
            $x = (int) ($width * 0.08) + $index * ($barWidth + 20);
            imagefilledrectangle($image, $x, (int) ($height * 0.42), $x + $barWidth, (int) ($height * 0.86), $this->allocateColor($image, $hex));
        }

        $font = $this->font();
        if ($font !== null) {
            imagettftext($image, (int) ($width / 32), 0, (int) ($width * 0.06), (int) ($height * 0.16), $this->allocateColor($image, '#FFFFFF'), $font, $heading);
            imagettftext($image, (int) ($width / 52), 0, (int) ($width * 0.06), (int) ($height * 0.22), $this->allocateColor($image, '#FFFFFF'), $font, $subheading);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return is_string($png) && $png !== '' ? $png : $this->fallbackPng();
    }

    private function makePdf(string $title, string $description, string $accent): string
    {
        [$r, $g, $b] = $this->hexToRgb($accent);
        $content = "q {$r} {$g} {$b} rg 50 790 495 6 re f Q\n";
        $y = 750.0;

        foreach ($this->wrapText($title, 55) as $line) {
            $content .= $this->pdfText($line, 18, true, 50, $y, $accent, 24);
        }

        $y -= 6;

        foreach ($this->wrapText($description, 95) as $line) {
            $content .= $this->pdfText($line, 11, false, 50, $y, '#334155', 16);
        }

        $y -= 12;
        $content .= $this->pdfText('Documento demo del Banco de Ideas y Proyectos de Software', 9, false, 50, $y, '#64748B', 14);
        $content .= "BT\n/F1 9 Tf\n0.39 0.45 0.55 rg\n1 0 0 1 50 40 Tm\n("
            .$this->pdfEscape('Banco de Ideas y Proyectos de Software')
            .") Tj\nET\n";

        return $this->buildPdf($content);
    }

    private function pdfText(string $text, int $size, bool $bold, int $x, float &$y, string $color, int $lineHeight): string
    {
        [$r, $g, $b] = $this->hexToRgb($color);
        $font = $bold ? 'F2' : 'F1';
        $line = "BT\n{$font} {$size} Tf\n{$r} {$g} {$b} rg\n1 0 0 1 {$x} {$y} Tm\n("
            .$this->pdfEscape($text)
            .") Tj\nET\n";
        $y -= $lineHeight;

        return $line;
    }

    private function buildPdf(string $content): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            6 => '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream',
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";

        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function wrapText(string $text, int $maxChars): array
    {
        $text = trim((string) preg_replace('/\s+/', ' ', $text));

        return explode("\n", wordwrap($text, $maxChars, "\n", true));
    }

    private function pdfEscape(string $text): string
    {
        $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

        if ($encoded === false) {
            $encoded = (string) preg_replace('/[^\x20-\x7E]/', '?', $text);
        }

        $encoded = str_replace('\\', '\\\\', $encoded);
        $encoded = str_replace('(', '\\(', $encoded);
        $encoded = str_replace(')', '\\)', $encoded);

        return $encoded;
    }

    private function font(): ?string
    {
        $paths = [
            'C:/Windows/Fonts/arialbd.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function allocateColor($image, string $hex): int
    {
        [$r, $g, $b] = $this->hexToRgb($hex);

        return imagecolorallocate($image, (int) $r, (int) $g, (int) $b);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function fallbackPng(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        );
    }
}

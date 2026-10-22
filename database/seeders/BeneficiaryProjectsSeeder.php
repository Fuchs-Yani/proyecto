<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BeneficiaryProjectsSeeder extends Seeder
{
    /**
     * Crea 4 beneficiarios de prueba con una idea cada uno.
     *
     * Cada idea incluye:
     * - un PDF con el detalle de lo que se busca;
     * - dos imágenes PNG de inspiración con la paleta de colores.
     *
     * No se ejecuta desde DatabaseSeeder. Para usarlo:
     * php artisan db:seed --class=BeneficiaryProjectsSeeder
     */
    public function run(): void
    {
        // Asegura roles y permisos, sin crear los datos demo antiguos.
        $this->call(DatabaseSeeder::class);

        foreach ($this->projects() as $data) {
            $beneficiary = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'institution_name' => $data['institution_name'],
                ],
            );

            $beneficiary->syncRoles(['beneficiario']);

            $idea = ProjectIdea::updateOrCreate(
                [
                    'organizer_id' => $beneficiary->id,
                    'title' => $data['idea']['title'],
                ],
                [
                    'description' => $data['idea']['description'],
                    'status' => 'available',
                ],
            );

            $directory = 'attachments/beneficiarios/'.$data['slug'];

            $this->storeDocument($idea, $directory, $data);
            $this->storeInspirations($idea, $directory, $data);
        }

        $this->command?->info('4 beneficiarios, 4 ideas, 4 PDF y 8 imágenes de inspiración creados.');
        $this->command?->info('Contraseña de las cuentas: password123');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function projects(): array
    {
        return [
            [
                'slug' => 'refugio-huellitas',
                'name' => 'Refugio Huellitas de Esperanza',
                'email' => 'refugio@test.com',
                'institution_name' => 'Refugio Huellitas de Esperanza',
                'colors' => ['#C76D4A', '#F4E9DC', '#7A9A7E', '#2F3A36', '#E9B872'],
                'image_subtitle' => 'Paleta cálida, hogar y confianza',
                'image_note' => 'Fotos grandes, tonos tierra y mensajes que transmitan esperanza.',
                'idea' => [
                    'title' => 'Sistema web para mostrar animalitos en adopción',
                    'description' => 'Un sitio web para que el refugio muestre los animales disponibles y las personas puedan iniciar una solicitud de adopción.',
                ],
                'document_subtitle' => 'Refugio Huellitas de Esperanza · Documento de requerimientos',
                'sections' => [
                    [
                        'heading' => 'Situación actual',
                        'body' => [
                            'El refugio difunde los animales en redes sociales y por WhatsApp. La información se pierde, se repite y no hay un lugar central para consultar fichas actualizadas.',
                        ],
                    ],
                    [
                        'heading' => 'Objetivo',
                        'body' => [
                            'Crear un sitio web público, cálido y fácil de usar, donde las personas puedan conocer los animales disponibles, filtrar por especie, tamaño o edad y enviar una solicitud de adopción.',
                        ],
                    ],
                    [
                        'heading' => 'Funciones principales',
                        'bullets' => [
                            'Catálogo de animales con foto principal, galería, nombre, edad, sexo, tamaño, estado de salud y estado de adopción.',
                            'Filtros por especie, edad, tamaño y compatibilidad con niños u otros animales.',
                            'Ficha individual con historia, requisitos de adopción y botón de contacto.',
                            'Formulario de solicitud de adopción con datos del interesado y seguimiento.',
                            'Panel para que el refugio publique, edite y marque animales como adoptados.',
                            'Diseño responsive, accesible y con carga rápida en celulares.',
                        ],
                    ],
                    [
                        'heading' => 'Identidad visual',
                        'body' => [
                            'Paleta cálida y confiable: terracota, crema, verde salvia y gris carbón. Tipografías redondeadas, fotos grandes y mensajes claros. Evitar un estilo triste o de lástima; transmitir esperanza y hogar.',
                        ],
                    ],
                    [
                        'heading' => 'Entregables esperados',
                        'body' => [
                            'Prototipo navegable, sitio responsive, panel de administración y manual básico de uso.',
                        ],
                    ],
                    [
                        'heading' => 'Criterios de aceptación',
                        'body' => [
                            'Un visitante puede encontrar un animal y enviar una solicitud en menos de 3 minutos. El refugio puede actualizar el estado de adopción sin conocimientos técnicos.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'biblioteca-escolar',
                'name' => 'Escuela Primaria N° 12',
                'email' => 'escuela@test.com',
                'institution_name' => 'Escuela Primaria N° 12',
                'colors' => ['#1F3A5F', '#F2C14E', '#F7F3E8', '#667085', '#D9E2EC'],
                'image_subtitle' => 'Orden, lectura y cercanía escolar',
                'image_note' => 'Tablas claras, acentos mostaza y una navegación simple para bibliotecarios.',
                'idea' => [
                    'title' => 'Sistema de gestión para la biblioteca escolar',
                    'description' => 'Un sistema para catalogar los libros de la escuela, registrar préstamos y devoluciones y conocer la disponibilidad en tiempo real.',
                ],
                'document_subtitle' => 'Escuela Primaria N° 12 · Documento de requerimientos',
                'sections' => [
                    [
                        'heading' => 'Situación actual',
                        'body' => [
                            'La biblioteca registra préstamos en cuadernos y planillas. No hay un catálogo único ni alertas de vencimiento, y se pierde el seguimiento de los libros.',
                        ],
                    ],
                    [
                        'heading' => 'Objetivo',
                        'body' => [
                            'Digitalizar el catálogo y la circulación de libros para que bibliotecarios y docentes puedan gestionar préstamos, devoluciones y disponibilidad de forma simple.',
                        ],
                    ],
                    [
                        'heading' => 'Funciones principales',
                        'bullets' => [
                            'Catálogo con título, autor, editorial, género, ubicación y cantidad de ejemplares.',
                            'Búsqueda por título, autor, ISBN o palabra clave.',
                            'Registro de alumnos y docentes, con préstamos y fecha de vencimiento.',
                            'Devoluciones, renovaciones y alertas de atraso.',
                            'Reportes de libros más prestados, préstamos activos y atrasos.',
                            'Roles de bibliotecario, docente y administrador.',
                        ],
                    ],
                    [
                        'heading' => 'Identidad visual',
                        'body' => [
                            'Paleta institucional: azul marino, amarillo mostaza, crema y gris. Interfaz ordenada, con tablas legibles y acentos cálidos. Debe sentirse escolar, moderna y fácil de usar.',
                        ],
                    ],
                    [
                        'heading' => 'Entregables esperados',
                        'body' => [
                            'Sistema web responsive, base de datos de prueba, manual de uso y una capacitación breve para el personal.',
                        ],
                    ],
                    [
                        'heading' => 'Criterios de aceptación',
                        'body' => [
                            'Un bibliotecario puede registrar un préstamo y una devolución sin ayuda. La disponibilidad de un libro se consulta al instante.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'despensa-maria',
                'name' => 'María González',
                'email' => 'maria@test.com',
                'institution_name' => null,
                'colors' => ['#2F6B4F', '#F28C28', '#F7F1E3', '#344054', '#A8C5B4'],
                'image_subtitle' => 'Comercio de barrio, stock y ventas',
                'image_note' => 'Botones grandes, números legibles y una experiencia cómoda en tablet.',
                'idea' => [
                    'title' => 'Sistema de gestión para una despensa',
                    'description' => 'Un sistema para controlar productos, ventas, compras y stock de una despensa, con alertas y reportes básicos.',
                ],
                'document_subtitle' => 'María González · Documento de requerimientos',
                'sections' => [
                    [
                        'heading' => 'Situación actual',
                        'body' => [
                            'La despensa controla stock y ventas de forma manual. Se producen faltantes, errores de precio y no hay claridad sobre qué productos se venden más.',
                        ],
                    ],
                    [
                        'heading' => 'Objetivo',
                        'body' => [
                            'Contar con un sistema simple para registrar productos, ventas, compras y stock, con alertas de reposición y reportes básicos para tomar decisiones.',
                        ],
                    ],
                    [
                        'heading' => 'Funciones principales',
                        'bullets' => [
                            'Alta y edición de productos con código, precio de compra, precio de venta y stock mínimo.',
                            'Registro de ventas con carrito, medios de pago y comprobante.',
                            'Entradas de mercadería y actualización automática del stock.',
                            'Alertas de stock bajo y productos próximos a vencer.',
                            'Reportes de ventas diarias, productos más vendidos y ganancias.',
                            'Usuarios con permisos de dueño y empleado.',
                        ],
                    ],
                    [
                        'heading' => 'Identidad visual',
                        'body' => [
                            'Paleta fresca y comercial: verde oscuro, naranja, crema y gris. Botones grandes, números legibles y navegación pensada para usar desde una tablet en el mostrador.',
                        ],
                    ],
                    [
                        'heading' => 'Entregables esperados',
                        'body' => [
                            'Sistema web responsive, carga inicial de productos y manual de uso.',
                        ],
                    ],
                    [
                        'heading' => 'Criterios de aceptación',
                        'body' => [
                            'Una venta puede registrarse en menos de 1 minuto. El stock se actualiza automáticamente y el dueño puede ver el resumen del día.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'medicacion-lucia',
                'name' => 'Lucía Fernández',
                'email' => 'lucia@test.com',
                'institution_name' => null,
                'colors' => ['#5B7DB1', '#B8A1D9', '#F7F8FC', '#344054', '#D6E4F0'],
                'image_subtitle' => 'Calma, claridad y accesibilidad',
                'image_note' => 'Texto grande, alto contraste y mensajes positivos para acompañar sin alarmar.',
                'idea' => [
                    'title' => 'Sistema de gestión para tomar medicación',
                    'description' => 'Una aplicación para registrar medicamentos, recibir recordatorios y llevar un historial de dosis tomadas.',
                ],
                'document_subtitle' => 'Lucía Fernández · Documento de requerimientos',
                'sections' => [
                    [
                        'heading' => 'Situación actual',
                        'body' => [
                            'Lucía toma varios medicamentos en horarios distintos y a veces olvida dosis o no recuerda si ya las tomó. Las alarmas del celular no llevan un historial.',
                        ],
                    ],
                    [
                        'heading' => 'Objetivo',
                        'body' => [
                            'Crear una aplicación sencilla y accesible que recuerde horarios, registre dosis tomadas y permita compartir el seguimiento con un familiar o cuidador.',
                        ],
                    ],
                    [
                        'heading' => 'Funciones principales',
                        'bullets' => [
                            'Registro de medicamentos, dosis, frecuencia y horarios.',
                            'Recordatorios configurables con confirmación de toma.',
                            'Historial diario y semanal de dosis tomadas, omitidas y atrasadas.',
                            'Alertas cuando una dosis no se confirma.',
                            'Vista para cuidador con permisos de solo lectura.',
                            'Diseño accesible: texto grande, alto contraste y pocos pasos.',
                        ],
                    ],
                    [
                        'heading' => 'Identidad visual',
                        'body' => [
                            'Paleta calmada y clara: azul sereno, lavanda suave, blanco y gris. Evitar el alarmismo; usar mensajes positivos e indicadores visuales simples.',
                        ],
                    ],
                    [
                        'heading' => 'Entregables esperados',
                        'body' => [
                            'Prototipo navegable, aplicación responsive, historial de prueba y guía de uso.',
                        ],
                    ],
                    [
                        'heading' => 'Criterios de aceptación',
                        'body' => [
                            'Una persona mayor puede registrar una toma en dos toques. El cuidador puede ver el historial sin modificar la configuración.',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeDocument(ProjectIdea $idea, string $directory, array $data): void
    {
        $fileName = 'Requerimientos - '.$data['name'].'.pdf';
        $path = $directory.'/requerimientos.pdf';

        Storage::disk('public')->put(
            $path,
            $this->makePdf(
                $data['idea']['title'],
                $data['document_subtitle'],
                $data['sections'],
                $data['colors'][0],
            ),
        );

        $this->saveAttachment($idea, $fileName, $path, 'pdf');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeInspirations(ProjectIdea $idea, string $directory, array $data): void
    {
        foreach ([1, 2] as $variant) {
            $fileName = 'Inspiración '.$variant.' - '.$data['name'].'.png';
            $path = $directory.'/inspiracion-'.$variant.'.png';

            Storage::disk('public')->put($path, $this->makeInspirationImage($data, $variant));

            $this->saveAttachment($idea, $fileName, $path, 'png');
        }
    }

    private function saveAttachment(ProjectIdea $idea, string $fileName, string $path, string $type): void
    {
        Attachment::updateOrCreate(
            [
                'project_idea_id' => $idea->id,
                'file_name' => $fileName,
            ],
            [
                'file_path' => $path,
                'file_type' => $type,
                'uploaded_at' => now(),
            ],
        );
    }

    /**
     * Genera un PDF simple de una página con el detalle de la idea.
     *
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function makePdf(string $title, string $subtitle, array $sections, string $accent): string
    {
        $content = '';
        [$r, $g, $b] = $this->hexToRgb($accent);

        $content .= "q {$r} {$g} {$b} rg 50 800 495 5 re f Q\n";
        $y = 770.0;

        $this->addPdfText($content, $title, 18, true, 50, $y, $accent, 22, 55);
        $y -= 4;
        $this->addPdfText($content, $subtitle, 10, false, 50, $y, '#5B6470', 15, 90);
        $y -= 14;

        foreach ($sections as $section) {
            if ($y < 80) {
                break;
            }

            $this->addPdfText($content, $section['heading'], 12, true, 50, $y, $accent, 17, 80);

            foreach ($section['body'] ?? [] as $paragraph) {
                $this->addPdfText($content, $paragraph, 10, false, 50, $y, '#222222', 14, 92);
                $y -= 2;
            }

            foreach ($section['bullets'] ?? [] as $bullet) {
                $this->addPdfText($content, '• '.$bullet, 10, false, 58, $y, '#222222', 14, 90);
            }

            $y -= 8;
        }

        $content .= "BT\n/F1 8 Tf\n0.45 0.45 0.45 rg\n1 0 0 1 50 35 Tm\n("
            .$this->pdfEscape('Documento generado para el Banco de Ideas y Proyectos de Software')
            ." ) Tj\nET\n";

        return $this->buildPdf($content);
    }

    private function addPdfText(
        string &$content,
        string $text,
        float $size,
        bool $bold,
        float $x,
        float &$y,
        string $color,
        float $lineHeight,
        int $maxChars,
    ): void {
        [$r, $g, $b] = $this->hexToRgb($color);
        $font = $bold ? 'F2' : 'F1';

        foreach ($this->wrapText($text, $maxChars) as $line) {
            $content .= "BT\n{$font} {$size} Tf\n{$r} {$g} {$b} rg\n"
                ."1 0 0 1 {$x} {$y} Tm\n("
                .$this->pdfEscape($line)
                .") Tj\nET\n";

            $y -= $lineHeight;
        }
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

    /**
     * Genera una imagen de inspiración con la paleta de la idea.
     *
     * @param  array<string, mixed>  $data
     */
    private function makeInspirationImage(array $data, int $variant): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('La extensión GD es necesaria para generar las imágenes de inspiración.');
        }

        $width = 1200;
        $height = 800;
        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $background = $this->allocateHex($image, '#F8F5F0');
        imagefill($image, 0, 0, $background);

        $titleFont = $this->font(true);
        $regularFont = $this->font(false);

        if ($variant === 1) {
            $this->drawCover($image, $data, $titleFont, $regularFont, $width);
        } else {
            $this->drawMockup($image, $data, $titleFont, $regularFont);
        }

        ob_start();
        imagepng($image, null, 6);
        $png = ob_get_clean();
        imagedestroy($image);

        if ($png === false) {
            throw new RuntimeException('No se pudo generar la imagen de inspiración.');
        }

        return $png;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function drawCover($image, array $data, string $titleFont, string $regularFont, int $width): void
    {
        $primary = $this->allocateHex($image, $data['colors'][0]);
        imagefilledrectangle($image, 0, 0, $width, 190, $primary);

        $this->drawText($image, 60, 95, 32, $titleFont, '#FFFFFF', $data['idea']['title']);
        $this->drawText($image, 60, 145, 20, $regularFont, '#F8F5F0', $data['image_subtitle']);

        $x = 60;
        foreach ($data['colors'] as $hex) {
            $color = $this->allocateHex($image, $hex);
            imagefilledrectangle($image, $x, 250, $x + 190, 430, $color);
            $this->drawText($image, $x, 470, 16, $titleFont, '#2F3A36', strtoupper($hex));
            $x += 215;
        }

        $this->circle($image, 1000, 620, 150, $data['colors'][2]);
        $this->circle($image, 1080, 680, 85, $data['colors'][4]);

        $this->drawText($image, 60, 600, 22, $titleFont, '#2F3A36', 'Ambiente y personalidad');
        $this->drawText($image, 60, 640, 18, $regularFont, '#5B6470', $data['image_note']);
        $this->drawText($image, 60, 760, 14, $regularFont, '#7A7A7A', 'Inspiración visual · Banco de Ideas y Proyectos de Software');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function drawMockup($image, array $data, string $titleFont, string $regularFont): void
    {
        $this->drawText($image, 60, 80, 36, $titleFont, '#2F3A36', 'Inspiración de interfaz');
        $this->drawText($image, 60, 120, 18, $regularFont, '#5B6470', $data['image_subtitle']);

        $white = $this->allocateHex($image, '#FFFFFF');
        $border = $this->allocateHex($image, '#D8DEE5');
        $header = $this->allocateHex($image, $data['colors'][0]);
        $sidebar = $this->allocateHex($image, $data['colors'][1]);

        imagefilledrectangle($image, 60, 160, 1140, 720, $white);
        imagerectangle($image, 60, 160, 1140, 720, $border);
        imagefilledrectangle($image, 61, 161, 1139, 215, $header);
        imagefilledrectangle($image, 61, 216, 300, 719, $sidebar);

        foreach (['#FF6B6B', '#FFD93D', '#6BCB77'] as $index => $hex) {
            $this->circle($image, 85 + ($index * 28), 188, 8, $hex);
        }

        $navLabels = ['Inicio', 'Listado', 'Detalle', 'Reportes', 'Ajustes'];
        foreach ($navLabels as $index => $label) {
            $y = 250 + ($index * 55);
            $this->roundedRect($image, 85, $y, 275, $y + 35, 9, $index === 0 ? $data['colors'][0] : '#FFFFFF');
            $this->drawText(
                $image,
                105,
                $y + 24,
                16,
                $regularFont,
                $index === 0 ? '#FFFFFF' : '#344054',
                $label,
            );
        }

        foreach ([0, 1, 2] as $index) {
            $x = 340 + ($index * 260);
            $this->roundedRect($image, $x, 270, $x + 235, 480, 14, '#FFFFFF');
            $this->roundedRect($image, $x + 18, 292, $x + 217, 360, 10, $data['colors'][1]);
            $this->drawText($image, $x + 18, 390, 18, $titleFont, '#2F3A36', 'Elemento '.($index + 1));
            $this->drawText($image, $x + 18, 418, 14, $regularFont, '#667085', 'Texto de apoyo');
            $this->roundedRect($image, $x + 18, 440, $x + 120, 466, 8, $data['colors'][2]);
        }

        $this->roundedRect($image, 340, 640, 620, 695, 12, $data['colors'][0]);
        $this->drawText($image, 370, 676, 18, $titleFont, '#FFFFFF', 'Acción principal');
        $this->drawText($image, 60, 760, 14, $regularFont, '#7A7A7A', 'Inspiración visual · Banco de Ideas y Proyectos de Software');
    }

    private function drawText($image, int $x, int $y, int $size, string $font, string $hex, string $text): void
    {
        imagettftext($image, $size, 0, $x, $y, $this->allocateHex($image, $hex), $font, $text);
    }

    private function circle($image, int $centerX, int $centerY, int $radius, string $hex): void
    {
        imagefilledellipse(
            $image,
            $centerX,
            $centerY,
            $radius * 2,
            $radius * 2,
            $this->allocateHex($image, $hex),
        );
    }

    private function roundedRect($image, int $x1, int $y1, int $x2, int $y2, int $radius, string $hex): void
    {
        $color = $this->allocateHex($image, $hex);

        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        foreach ([[$x1 + $radius, $y1 + $radius], [$x2 - $radius, $y1 + $radius], [$x1 + $radius, $y2 - $radius], [$x2 - $radius, $y2 - $radius]] as [$x, $y]) {
            imagefilledellipse($image, $x, $y, $radius * 2, $radius * 2, $color);
        }
    }

    private function font(bool $bold): string
    {
        $paths = $bold
            ? ['C:/Windows/Fonts/arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf']
            : ['C:/Windows/Fonts/arial.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException('No se encontró una fuente TTF para generar las imágenes.');
    }

    private function allocateHex($image, string $hex): int
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

    /**
     * @return array<int, string>
     */
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
}

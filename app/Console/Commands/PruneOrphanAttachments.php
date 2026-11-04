<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('attachments:prune {--dry-run : Muestra los archivos que se eliminarían sin borrarlos}')]
#[Description('Elimina los archivos de adjuntos que ya no tienen un registro en la base de datos')]
class PruneOrphanAttachments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = Storage::disk('public');
        $referencedPaths = Attachment::query()
            ->pluck('file_path')
            ->filter()
            ->flip();

        $orphanPaths = collect($disk->allFiles('attachments'))
            ->reject(fn (string $path): bool => $referencedPaths->has($path))
            ->values();

        if ($orphanPaths->isEmpty()) {
            $this->info('No se encontraron archivos huérfanos.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("Se eliminarían {$orphanPaths->count()} archivo(s) huérfano(s):");

            $orphanPaths->each(function (string $path): void {
                $this->line(" - {$path}");
            });

            return self::SUCCESS;
        }

        $disk->delete($orphanPaths->all());

        $this->info("Se eliminaron {$orphanPaths->count()} archivo(s) huérfano(s).");

        return self::SUCCESS;
    }
}

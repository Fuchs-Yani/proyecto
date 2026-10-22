<?php

use App\Models\Attachment;
use App\Models\ProjectIdea;
use App\Models\User;
use Database\Seeders\BeneficiaryProjectsSeeder;
use Illuminate\Support\Facades\Storage;

test('beneficiary projects seeder creates four beneficiaries, ideas and attachments', function () {
    Storage::fake('public');

    $this->seed(BeneficiaryProjectsSeeder::class);

    expect(User::where('email', 'refugio@test.com')->first()->hasRole('beneficiario'))->toBeTrue()
        ->and(User::where('email', 'escuela@test.com')->first()->institution_name)->toBe('Escuela Primaria N° 12')
        ->and(User::where('email', 'maria@test.com')->first()->institution_name)->toBeNull()
        ->and(User::where('email', 'lucia@test.com')->first()->hasRole('beneficiario'))->toBeTrue()
        ->and(ProjectIdea::count())->toBe(4)
        ->and(Attachment::count())->toBe(12);

    foreach (Attachment::all() as $attachment) {
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    $pdf = Attachment::where('file_type', 'pdf')->first();

    expect(Storage::disk('public')->get($pdf->file_path))->toStartWith('%PDF-1.4');
});

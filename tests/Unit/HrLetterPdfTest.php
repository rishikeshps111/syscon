<?php

use App\Models\GeneratedHrLetter;
use App\Support\HrLetterPdf;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

pest()->extend(TestCase::class);

function samplePdfLetter(?string $logo): GeneratedHrLetter
{
    $letter = new GeneratedHrLetter();
    $letter->forceFill([
        'letter_number' => 'HRL-TEST',
        'subject' => 'Test Letter',
        'content' => '<p>Saved letter content</p>',
        'header_logo' => $logo,
    ]);

    return $letter;
}

it('renders a PDF without fetching a missing logo URL', function () {
    Storage::fake('public');
    $letter = samplePdfLetter('missing/logo.png');

    $html = view('hr-letter.pdf', ['hrLetter' => $letter])->render();
    expect($html)->not->toContain('storage/missing/logo.png');

    $response = app(HrLetterPdf::class)->response($letter, 'inline');
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toBe('inline; filename="HRL-TEST.pdf"')
        ->and($response->getContent())->toStartWith('%PDF-');
});

it('embeds an existing local logo and returns an attachment PDF', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logos/logo.png', base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII='
    ));
    $letter = samplePdfLetter('logos/logo.png');

    $html = view('hr-letter.pdf', ['hrLetter' => $letter])->render();
    expect($html)->toContain('data:image/png;base64,')
        ->not->toContain('storage/logos/logo.png');

    $response = app(HrLetterPdf::class)->response($letter);
    expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="HRL-TEST.pdf"')
        ->and($response->getContent())->toStartWith('%PDF-');
});

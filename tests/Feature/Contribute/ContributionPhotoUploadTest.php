<?php

use App\Models\Contribution;
use App\Models\Photo;
use App\Services\Media\PhotoUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Queue::fake();
    Storage::fake('public');
});

it('accepts a valid jpeg upload and creates a Photo polymorphique', function () {
    $photo = UploadedFile::fake()->image('test.jpg', 2400, 1600);    // 2400x1600 → resize a 1600

    $payload = [
        'name' => 'Test Place',
        'type' => 'cafe',
        'description' => 'Une description sufisamment longue pour passer la validation min:30.',
        'photo' => $photo,
    ];

    $this->post('/contribuer', $payload)->assertRedirect('/contribuer/merci');

    expect(Contribution::count())->toBe(1)
        ->and(Photo::count())->toBe(1);

    $photo = Photo::first();
    $contrib = Contribution::first();

    expect($photo->uploadable_type)->toBe($contrib->getMorphClass())
        ->and($photo->uploadable_id)->toBe($contrib->id)
        ->and($photo->disk)->toBe('public')
        ->and($photo->mime_type)->toBe('image/jpeg')
        ->and($photo->width)->toBeLessThanOrEqual(1600);

    Storage::disk('public')->assertExists($photo->path);
});

it('PhotoUploadService rejects a non-image file via magic bytes check', function () {
    Storage::fake('public');

    $textFile = UploadedFile::fake()->createWithContent('hack.jpg', 'this is not an image at all');

    $contrib = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'X', 'type' => 'cafe', 'description' => str_repeat('x', 50)],
        'status' => Contribution::STATUS_PENDING,
    ]);

    expect(fn () => app(PhotoUploadService::class)->storeFor($textFile, $contrib))
        ->toThrow(RuntimeException::class);

    expect(Photo::count())->toBe(0);
});

it('rejects an image larger than 5 MB', function () {
    $oversized = UploadedFile::fake()->image('big.jpg', 4000, 4000)->size(6000);

    $this->post('/contribuer', [
        'name' => 'Test',
        'type' => 'cafe',
        'description' => 'Une description sufisamment longue pour passer la validation min:30.',
        'photo' => $oversized,
    ])->assertSessionHasErrors('photo');
});

it('does not block contribution submission if photo upload fails (graceful)', function () {
    // Simule un upload qui plante en mockant le service
    $this->mock(PhotoUploadService::class, function ($mock) {
        $mock->shouldReceive('storeFor')->andThrow(new RuntimeException('Disk full'));
    });

    $photo = UploadedFile::fake()->image('test.jpg', 800, 600);

    $this->post('/contribuer', [
        'name' => 'Test Place',
        'type' => 'cafe',
        'description' => 'Une description sufisamment longue pour passer la validation min:30.',
        'photo' => $photo,
    ])->assertRedirect('/contribuer/merci');

    // La contribution est créée, juste sans photo
    expect(Contribution::count())->toBe(1)
        ->and(Photo::count())->toBe(0);
});

it('contribution without photo still works (photo optional)', function () {
    $this->post('/contribuer', [
        'name' => 'Test no photo',
        'type' => 'cafe',
        'description' => 'Une description sufisamment longue pour passer la validation min:30.',
    ])->assertRedirect('/contribuer/merci');

    expect(Contribution::count())->toBe(1)
        ->and(Photo::count())->toBe(0);
});

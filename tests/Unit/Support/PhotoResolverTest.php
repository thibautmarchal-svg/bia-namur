<?php

use App\Support\PhotoResolver;

beforeEach(function () {
    config()->set('bia-photos.places.test-place', [
        'path' => 'images/defaults/places/citadelle-de-namur',
        'alt' => 'Test alt',
        'credit' => 'Test author',
        'license' => 'CC BY 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
        'source_url' => 'https://example.com/photo',
    ]);
});

it('returns null when no default and no override exists', function () {
    expect(PhotoResolver::for('places', 'unknown-slug-zzz', 'alt'))->toBeNull();
});

it('returns the default photo with srcset when configured', function () {
    $photo = PhotoResolver::for('places', 'test-place', 'Override alt');

    expect($photo)->toBeArray()
        ->and($photo['url'])->toContain('citadelle-de-namur-1600.webp')
        ->and($photo['src_jpg'])->toContain('citadelle-de-namur.jpg')
        ->and($photo['srcset'])->toContain('800w')
        ->and($photo['srcset'])->toContain('1600w')
        ->and($photo['credit'])->toBe('Test author')
        ->and($photo['license'])->toBe('CC BY 4.0')
        ->and($photo['license_url'])->toBe('https://creativecommons.org/licenses/by/4.0/')
        ->and($photo['source_url'])->toBe('https://example.com/photo')
        ->and($photo['is_override'])->toBeFalse();
});

it('uses the alt override when provided', function () {
    $photo = PhotoResolver::for('places', 'test-place', 'Override alt');
    expect($photo['alt'])->toBe('Override alt');
});

it('uses the alt from config if no override provided', function () {
    $photo = PhotoResolver::for('places', 'test-place');
    expect($photo['alt'])->toBe('Test alt');
});

it('detects a personal override file in public/images/places/', function () {
    $publicDir = public_path('images/places');
    if (! is_dir($publicDir)) {
        mkdir($publicDir, 0755, true);
    }
    $personalFile = $publicDir . '/test-override.jpg';
    file_put_contents($personalFile, 'fake jpeg bytes');

    try {
        $photo = PhotoResolver::for('places', 'test-override', 'My place');

        expect($photo)->toBeArray()
            ->and($photo['is_override'])->toBeTrue()
            ->and($photo['url'])->toContain('test-override.jpg')
            ->and($photo['credit'])->toBeNull()
            ->and($photo['license'])->toBeNull()
            ->and($photo['alt'])->toBe('My place');
    } finally {
        @unlink($personalFile);
    }
});

it('prefers webp over jpg when both override files exist', function () {
    $publicDir = public_path('images/places');
    if (! is_dir($publicDir)) {
        mkdir($publicDir, 0755, true);
    }
    $jpg = $publicDir . '/dual-test.jpg';
    $webp = $publicDir . '/dual-test.webp';
    file_put_contents($jpg, 'jpg');
    file_put_contents($webp, 'webp');

    try {
        $photo = PhotoResolver::for('places', 'dual-test');
        expect($photo['url'])->toContain('dual-test.webp');
    } finally {
        @unlink($jpg);
        @unlink($webp);
    }
});

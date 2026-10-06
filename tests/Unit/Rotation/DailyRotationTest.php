<?php

declare(strict_types=1);

use Marko\Log\File\Rotation\DailyRotation;
use Marko\Log\File\Rotation\RotationStrategyInterface;
use Marko\Testing\Fake\FakeClock;

it('implements RotationStrategyInterface', function () {
    $rotation = new DailyRotation(new FakeClock());

    expect($rotation)->toBeInstanceOf(RotationStrategyInterface::class);
});

it('names the log file from the injected clock\'s date', function () {
    $rotation = new DailyRotation(new FakeClock('2026-01-21 09:30:00'));

    $path = $rotation->getCurrentPath('/var/log', 'app');

    expect($path)->toBe('/var/log/app-2026-01-21.log');
});

it('rolls over to a new file when the clock crosses midnight', function () {
    $clock = new FakeClock('2026-01-21 23:59:59');
    $rotation = new DailyRotation($clock);

    $path1 = $rotation->getCurrentPath('/var/log', 'app');
    $clock->travel('+1 second');
    $path2 = $rotation->getCurrentPath('/var/log', 'app');

    expect($path1)->toBe('/var/log/app-2026-01-21.log')
        ->and($path2)->toBe('/var/log/app-2026-01-22.log')
        ->and($rotation->needsRotation($path1))->toBeTrue()
        ->and($rotation->needsRotation($path2))->toBeFalse();
});

it('handles trailing slash in base path', function () {
    $rotation = new DailyRotation(new FakeClock('2026-01-21'));

    $path = $rotation->getCurrentPath('/var/log/', 'app');

    expect($path)->toBe('/var/log/app-2026-01-21.log');
});

it('uses different channel names', function () {
    $rotation = new DailyRotation(new FakeClock('2026-01-21'));

    $appPath = $rotation->getCurrentPath('/var/log', 'app');
    $apiPath = $rotation->getCurrentPath('/var/log', 'api');

    expect($appPath)->toBe('/var/log/app-2026-01-21.log')
        ->and($apiPath)->toBe('/var/log/api-2026-01-21.log');
});

it('indicates no rotation needed for current date file', function () {
    $rotation = new DailyRotation(new FakeClock('2026-01-21'));

    $currentPath = $rotation->getCurrentPath('/var/log', 'app');

    expect($rotation->needsRotation($currentPath))->toBeFalse();
});

it('indicates rotation needed for previous date file', function () {
    $rotation = new DailyRotation(new FakeClock('2026-01-22'));

    $oldPath = '/var/log/app-2026-01-21.log';

    expect($rotation->needsRotation($oldPath))->toBeTrue();
});

it('handles files without date pattern', function () {
    $rotation = new DailyRotation(new FakeClock());

    expect($rotation->needsRotation('/var/log/app.log'))->toBeFalse();
});

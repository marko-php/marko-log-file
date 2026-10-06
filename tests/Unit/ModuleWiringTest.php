<?php

declare(strict_types=1);

use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\Container;
use Marko\Core\Container\PreferenceRegistry;
use Marko\Log\Contracts\LogFormatterInterface;
use Marko\Log\Contracts\LoggerInterface;
use Marko\Log\File\Rotation\DailyRotation;
use Marko\Log\File\Rotation\SizeRotation;
use Marko\Log\Formatter\LineFormatter;
use Marko\Testing\Fake\FakeClock;
use Marko\Testing\Fake\FakeConfigRepository;
use Psr\Clock\ClockInterface;

it('resolves a logger that stamps and rotates on the bound clock', function () {
    $dir = sys_get_temp_dir() . '/marko-log-module-' . bin2hex(random_bytes(8));
    $module = require dirname(__DIR__, 2) . '/module.php';
    $container = new Container(new PreferenceRegistry());
    $container->instance(ConfigRepositoryInterface::class, new FakeConfigRepository([
        'log.path' => $dir,
        'log.channel' => 'app',
        'log.level' => 'debug',
        'log.file_mode' => 0600,
        'log.dir_mode' => 0700,
    ]));
    $container->instance(LogFormatterInterface::class, new LineFormatter());
    $container->instance(ClockInterface::class, new FakeClock('2026-01-21 09:30:15'));

    foreach ($module['bindings'] as $abstract => $concrete) {
        $container->bind($abstract, $concrete);
    }

    $container->get(LoggerInterface::class)->info('Wired');
    $file = $dir . '/app-2026-01-21.log';
    $content = file_get_contents($file);

    unlink($file);
    rmdir($dir);

    expect($content)->toContain('[2026-01-21 09:30:15] app.INFO: Wired');
});

it('applies a Preference that replaces DailyRotation', function () {
    $dir = sys_get_temp_dir() . '/marko-log-module-' . bin2hex(random_bytes(8));
    $module = require dirname(__DIR__, 2) . '/module.php';
    $preferences = new PreferenceRegistry();
    $preferences->register(DailyRotation::class, SizeRotation::class);
    $container = new Container($preferences);
    $container->instance(ConfigRepositoryInterface::class, new FakeConfigRepository([
        'log.path' => $dir,
        'log.channel' => 'app',
        'log.level' => 'debug',
        'log.file_mode' => 0600,
        'log.dir_mode' => 0700,
    ]));
    $container->instance(LogFormatterInterface::class, new LineFormatter());
    $container->instance(ClockInterface::class, new FakeClock('2026-01-21 09:30:15'));

    foreach ($module['bindings'] as $abstract => $concrete) {
        $container->bind($abstract, $concrete);
    }

    $container->get(LoggerInterface::class)->info('Sized');
    $exists = is_file($dir . '/app.log');

    array_map(unlink(...), glob($dir . '/*'));
    rmdir($dir);

    expect($exists)->toBeTrue();
});

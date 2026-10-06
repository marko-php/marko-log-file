<?php

declare(strict_types=1);

use Marko\Log\Config\LogConfig;
use Marko\Log\File\Factory\FileLoggerFactory;
use Marko\Log\File\Rotation\DailyRotation;
use Marko\Log\Formatter\LineFormatter;
use Marko\Testing\Fake\FakeClock;
use Marko\Testing\Fake\FakeConfigRepository;

it('builds a logger whose rotation and records follow the injected clock', function () {
    $dir = sys_get_temp_dir() . '/marko-log-factory-' . bin2hex(random_bytes(8));
    $clock = new FakeClock('2026-01-21 09:30:15');
    $factory = new FileLoggerFactory(
        config: new LogConfig(new FakeConfigRepository([
            'log.path' => $dir,
            'log.channel' => 'app',
            'log.level' => 'debug',
        ])),
        formatter: new LineFormatter(),
        clock: $clock,
        rotation: new DailyRotation($clock),
    );

    $factory->create()->info('Hello');
    $file = $dir . '/app-2026-01-21.log';
    $content = file_get_contents($file);

    unlink($file);
    rmdir($dir);

    expect($content)->toContain('[2026-01-21 09:30:15] app.INFO: Hello');
});

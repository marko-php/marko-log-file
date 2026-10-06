<?php

declare(strict_types=1);

namespace Marko\Log\File\Factory;

use Marko\Log\Config\LogConfig;
use Marko\Log\Contracts\LogFormatterInterface;
use Marko\Log\Contracts\LoggerInterface;
use Marko\Log\File\Driver\FileLogger;
use Marko\Log\File\Rotation\RotationStrategyInterface;
use Psr\Clock\ClockInterface;

/**
 * The rotation is injected rather than built here, so the container resolves
 * it (bound to DailyRotation in module.php): it shares the bound clock, and a
 * #[Preference] for DailyRotation applies.
 */
readonly class FileLoggerFactory
{
    public function __construct(
        private LogConfig $config,
        private LogFormatterInterface $formatter,
        private ClockInterface $clock,
        private RotationStrategyInterface $rotation,
    ) {}

    public function create(): LoggerInterface
    {
        return new FileLogger(
            path: $this->config->path(),
            channel: $this->config->channel(),
            minimumLevel: $this->config->level(),
            formatter: $this->formatter,
            clock: $this->clock,
            rotation: $this->rotation,
            fileMode: $this->config->fileMode(),
            dirMode: $this->config->dirMode(),
        );
    }
}

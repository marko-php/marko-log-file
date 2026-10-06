<?php

declare(strict_types=1);

namespace Marko\Log\File\Driver;

use Marko\Log\Contracts\LogFormatterInterface;
use Marko\Log\Contracts\LoggerInterface;
use Marko\Log\Exceptions\LogWriteException;
use Marko\Log\File\Rotation\DailyRotation;
use Marko\Log\File\Rotation\RotationStrategyInterface;
use Marko\Log\LogLevel;
use Marko\Log\LogRecord;
use Psr\Clock\ClockInterface;

readonly class FileLogger implements LoggerInterface
{
    private RotationStrategyInterface $rotation;

    /**
     * Without a rotation strategy, files rotate daily on the same clock that stamps the records.
     * New log files get $fileMode and a newly created log directory gets $dirMode (owner-only by default).
     */
    public function __construct(
        private string $path,
        private string $channel,
        private LogLevel $minimumLevel,
        private LogFormatterInterface $formatter,
        private ClockInterface $clock,
        ?RotationStrategyInterface $rotation = null,
        private int $fileMode = 0600,
        private int $dirMode = 0700,
    ) {
        $this->rotation = $rotation ?? new DailyRotation($this->clock);
    }

    public function emergency(
        string $message,
        array $context = [],
    ): void {
        $this->log(LogLevel::Emergency, $message, $context);
    }

    public function alert(
        string $message,
        array $context = [],
    ): void {
        $this->log(LogLevel::Alert, $message, $context);
    }

    public function critical(
        string $message,
        array $context = [],
    ): void {
        $this->log(LogLevel::Critical, $message, $context);
    }

    public function error(
        string $message,
        array $context = [],
    ): void {
        $this->log(LogLevel::Error, $message, $context);
    }

    public function warning(
        string $message,
        array $context = [],
    ): void {
        $this->log(LogLevel::Warning, $message, $context);
    }

    public function notice(
        string $message,
        array $context = [],
    ): void {
        $this->log(LogLevel::Notice, $message, $context);
    }

    public function info(
        string $message,
        array $context = [],
    ): void {
        $this->log(LogLevel::Info, $message, $context);
    }

    public function debug(
        string $message,
        array $context = [],
    ): void {
        $this->log(LogLevel::Debug, $message, $context);
    }

    /**
     * @throws LogWriteException
     */
    public function log(
        LogLevel $level,
        string $message,
        array $context = [],
    ): void {
        // Check if level meets threshold
        if (!$level->meetsThreshold($this->minimumLevel)) {
            return;
        }

        $record = new LogRecord(
            level: $level,
            message: $message,
            context: $context,
            datetime: $this->clock->now(),
            channel: $this->channel,
        );

        $this->write($record);
    }

    /**
     * @throws LogWriteException
     */
    private function write(
        LogRecord $record,
    ): void {
        $this->ensureDirectoryExists();

        $filePath = $this->rotation->getCurrentPath($this->path, $this->channel);
        $line = $this->formatter->format($record);

        $this->ensureFileExists($filePath);

        $result = file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            throw LogWriteException::forPath($filePath);
        }
    }

    /**
     * The file is created empty and restricted before any record is written, so log content is never
     * readable under the process umask's default (typically world-readable 0644) mode.
     *
     * @throws LogWriteException
     */
    private function ensureFileExists(
        string $filePath,
    ): void {
        if (is_file($filePath)) {
            return;
        }

        if (!touch($filePath)) {
            throw LogWriteException::forPath($filePath, 'could not create the log file');
        }

        if (!chmod($filePath, $this->fileMode)) {
            throw LogWriteException::forPath($filePath, sprintf('could not set mode 0%o', $this->fileMode));
        }
    }

    /**
     * @throws LogWriteException
     */
    private function ensureDirectoryExists(): void
    {
        if (is_dir($this->path)) {
            if (!is_writable($this->path)) {
                throw LogWriteException::directoryNotWritable($this->path);
            }

            return;
        }

        if (!mkdir($this->path, $this->dirMode, true) && !is_dir($this->path)) {
            throw LogWriteException::directoryNotWritable($this->path);
        }

        // mkdir() is filtered by the umask; chmod applies the configured mode exactly.
        if (!chmod($this->path, $this->dirMode)) {
            throw LogWriteException::directoryNotWritable($this->path);
        }
    }
}

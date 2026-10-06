<?php

declare(strict_types=1);

use Marko\Core\Container\ContainerInterface;
use Marko\Log\Contracts\LoggerInterface;
use Marko\Log\File\Factory\FileLoggerFactory;
use Marko\Log\File\Rotation\DailyRotation;
use Marko\Log\File\Rotation\RotationStrategyInterface;

return [
    'bindings' => [
        LoggerInterface::class => function (ContainerInterface $container): LoggerInterface {
            return $container->get(FileLoggerFactory::class)->create();
        },
        // Closure so DailyRotation is resolved by class name and a #[Preference] replacing it applies.
        RotationStrategyInterface::class => function (ContainerInterface $container): RotationStrategyInterface {
            return $container->get(DailyRotation::class);
        },
    ],
];

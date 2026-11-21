<?php

declare(strict_types=1);

use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/helpers',
        __DIR__ . '/migrations',
        __DIR__ . '/public/index.php',
        __DIR__ . '/shared',
    ])
    ->withSkip([
        __DIR__ . '/modules',
        ClassPropertyAssignToConstructorPromotionRector::class,
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        SetList::TYPE_DECLARATION,
    ])
    ->withRules([
        DeclareStrictTypesRector::class,
    ]);

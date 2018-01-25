<?php
/**
 * This file is part of the nepada/bust-cache.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

declare(strict_types = 1);

namespace Nepada\Bridges\BustCacheDI;

use Latte;
use Nepada\BustCache\BustCacheMacro;
use Nette;
use Nette\Bridges\ApplicationLatte\ILatteFactory;


class BustCacheExtension extends Nette\DI\CompilerExtension
{

    /** @var string */
    private $wwwDir;

    /** @var bool */
    private $debugMode;


    /**
     * @param string $wwwDir
     * @param bool $debugMode
     */
    public function __construct(string $wwwDir, bool $debugMode = false)
    {
        $this->wwwDir = $wwwDir;
        $this->debugMode = $debugMode;
    }

    public function loadConfiguration(): void
    {
        $this->validateConfig([]);
    }

    public function beforeCompile(): void
    {
        $container = $this->getContainerBuilder();
        $latteFactory = $container->getByType(ILatteFactory::class);
        if ($latteFactory !== null) {
            $container->getDefinition($latteFactory)->addSetup(
                '?->onCompile[] = function (' . Latte\Engine::class . ' $engine): void { $engine->addMacro("bustCache", new ' . BustCacheMacro::class . '(?, ?)); }',
                ['@self', $this->wwwDir, $this->debugMode]
            );
        }
    }

}

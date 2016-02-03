<?php
/**
 * This file is part of the nepada/bust-cache.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

namespace NepadaTests\Bridges\BustCacheDI;

use Latte;
use Nette;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Tester;
use Tester\Assert;


require_once __DIR__ . '/../../bootstrap.php';


class BustCacheExtensionTest extends Tester\TestCase
{

    /** @var Nette\DI\Container */
    private $container;


    public function setUp()
    {
        $configurator = new Nette\Configurator;
        $configurator->setTempDirectory(TEMP_DIR);
        $configurator->setDebugMode(TRUE);
        $configurator->addParameters(array('wwwDir' => __DIR__ . '/../../fixtures'));
        $configurator->addConfig(__DIR__ . '/../../fixtures/config.neon');
        $this->container = $configurator->createContainer();
    }

    public function testContainer()
    {
        /** @var Latte\Engine $latte */
        $latte = $this->container->getByType(ILatteFactory::class)->create();
        $latte->setLoader(new Latte\Loaders\StringLoader);
        Assert::noError(function () use ($latte) {$latte->compile('{bustCache}');});
    }

}


\run(new BustCacheExtensionTest());

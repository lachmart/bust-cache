<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\BustCacheDI;

use Latte;
use Nepada\BustCache\CacheBustingStrategies\ContentHash;
use Nepada\BustCache\CacheBustingStrategies\ModificationTime;
use Nepada\BustCache\CacheBustingStrategy;
use Nepada\BustCache\Caching\Cache;
use Nepada\BustCache\Caching\NetteCache;
use Nepada\BustCache\Caching\NullCache;
use NepadaTests\Environment;
use NepadaTests\TestCase;
use Nette;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class BustCacheExtensionTest extends TestCase
{

    private const TEST_TEMPLATE = "<script src=\"{bustCache /test.txt}\"></script>\n<link href=\"{bustCache dynamic /test.txt}\">\n<link rel=\"stylesheet\" href=\"{bustCache \$file}\">";

    public function testNoCache(): void
    {
        $configurator = $this->createConfigurator(false);
        unset($configurator->defaultExtensions['cache']);
        $container = $configurator->createContainer();
        Assert::type(NullCache::class, $container->getByType(Cache::class));
        $latte = $this->createLatte($container);

        $compiledCode = $latte->compile(self::TEST_TEMPLATE);
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr(\'/test.txt?a1d0c6e83f\') /* line 1 */;',
            $compiledCode,
        );
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr($this->global->bustCachePathProcessor->__invoke(\'/test.txt\', true)) /* line 2 */;',
            $compiledCode,
        );
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr($this->global->bustCachePathProcessor->__invoke($file, false)) /* line 3 */;',
            $compiledCode,
        );

        $renderedCode = $latte->renderToString(self::TEST_TEMPLATE, ['file' => '/test.txt']);
        Assert::contains('<script src="/test.txt?a1d0c6e83f"></script>', $renderedCode);
        Assert::contains('<link href="/test.txt?a1d0c6e83f">', $renderedCode);
        Assert::contains('<link rel="stylesheet" href="/test.txt?a1d0c6e83f">', $renderedCode);
    }

    public function testDebugMode(): void
    {
        $container = $this->createConfigurator(true)->createContainer();
        Assert::type(ModificationTime::class, $container->getByType(CacheBustingStrategy::class));
        Assert::type(NetteCache::class, $container->getByType(Cache::class));
        $latte = $this->createLatte($container);

        $compiledCode = $latte->compile(self::TEST_TEMPLATE);
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr($this->global->bustCachePathProcessor->__invoke(\'/test.txt\', true)) /* line 1 */;',
            $compiledCode,
        );
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr($this->global->bustCachePathProcessor->__invoke(\'/test.txt\', true)) /* line 2 */;',
            $compiledCode,
        );
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr($this->global->bustCachePathProcessor->__invoke($file, true)) /* line 3 */;',
            $compiledCode,
        );

        $renderedCode = $latte->renderToString(self::TEST_TEMPLATE, ['file' => '/test.txt']);
        Assert::match('%A?%<script src="/test.txt?%d%"></script>%A?%', $renderedCode);
        Assert::match('%A?%<link href="/test.txt?%d%">%A?%', $renderedCode);
        Assert::match('%A?%<link rel="stylesheet" href="/test.txt?%d%">%A?%', $renderedCode);
    }

    public function testProductionMode(): void
    {
        $container = $this->createConfigurator(false)->createContainer();
        Assert::type(ContentHash::class, $container->getByType(CacheBustingStrategy::class));
        Assert::type(NetteCache::class, $container->getByType(Cache::class));
        $latte = $this->createLatte($container);

        $compiledCode = $latte->compile(self::TEST_TEMPLATE);
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr(\'/test.txt?a1d0c6e83f\') /* line 1 */;',
            $compiledCode,
        );
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr($this->global->bustCachePathProcessor->__invoke(\'/test.txt\', true)) /* line 2 */;',
            $compiledCode,
        );
        Assert::contains(
            'echo LR\Filters::escapeHtmlAttr($this->global->bustCachePathProcessor->__invoke($file, false)) /* line 3 */;',
            $compiledCode,
        );

        $renderedCode = $latte->renderToString(self::TEST_TEMPLATE, ['file' => '/test.txt']);
        Assert::contains('<script src="/test.txt?a1d0c6e83f"></script>', $renderedCode);
        Assert::contains('<link href="/test.txt?a1d0c6e83f">', $renderedCode);
        Assert::contains('<link rel="stylesheet" href="/test.txt?a1d0c6e83f">', $renderedCode);
    }

    private function createConfigurator(bool $debugMode): Nette\Configurator
    {
        $configurator = new Nette\Configurator();
        $configurator->setTempDirectory(Environment::getTempDir());
        $configurator->setDebugMode($debugMode);
        $configurator->addParameters(['wwwDir' => __DIR__ . '/../../fixtures']);
        $configurator->addConfig(__DIR__ . '/../../fixtures/config.neon');
        return $configurator;
    }

    private function createLatte(Nette\DI\Container $container): Latte\Engine
    {
        $latte = $container->getByType(LatteFactory::class)->create();
        $latte->setLoader(new Latte\Loaders\StringLoader());
        return $latte;
    }

}


(new BustCacheExtensionTest())->run();

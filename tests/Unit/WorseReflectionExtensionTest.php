<?php

namespace Phpactor\Extension\WorseReflection\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\ClassToFile\ClassToFileExtension;
use Phpactor\Extension\ComposerAutoloader\ComposerAutoloaderExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\WorseReflection\Reflector;

class WorseReflectionExtensionTest extends TestCase
{
    public function testProvideReflector()
    {
        $reflector = $this->createReflector([
            FilePathResolverExtension::PARAM_APPLICATION_ROOT => __DIR__ . '/../..',
        ]);
        $this->assertEquals((string) $reflector->reflectClass(__CLASS__)->name(), __CLASS__);
    }

    public function testRegistersTaggedFramewalkers()
    {
        $reflector = $this->createReflector([
            FilePathResolverExtension::PARAM_APPLICATION_ROOT => __DIR__ . '/../..',
        ]);
        $frame = $reflector->reflectClass(__CLASS__)->methods()->get('testRegistersTaggedFramewalkers')->frame();
        $this->assertCount(1, $frame->locals()->byName('test_variable'));
    }

    public function testProvideReflectorWithStubs()
    {
        $reflector = $this->createReflector([
            FilePathResolverExtension::PARAM_APPLICATION_ROOT => __DIR__ . '/../..'
        ]);
        $this->assertEquals((string) $reflector->reflectClass(__CLASS__)->name(), __CLASS__);
    }

    public function testProvideReflectorWithStubsAndCustomCacheDir()
    {
        $reflector = $this->createReflector([
            FilePathResolverExtension::PARAM_APPLICATION_ROOT => __DIR__,
            WorseReflectionExtension::PARAM_STUB_DIR => __DIR__ . '/../../vendor/jetbrains/phpstorm-stubs',
            WorseReflectionExtension::PARAM_STUB_CACHE_DIR => $cachePath = __DIR__ . '/../../stubs'
        ]);
        $this->assertEquals((string) $reflector->reflectClass(__CLASS__)->name(), __CLASS__);
        $this->assertFileExists($cachePath);
    }

    private function createReflector(array $params = []): Reflector
    {
        $container = PhpactorContainer::fromExtensions([
            WorseReflectionExtension::class,
            FilePathResolverExtension::class,
            ClassToFileExtension::class,
            ComposerAutoloaderExtension::class,
            LoggingExtension::class,
            TestExtension::class,
        ], $params);

        return $container->get(WorseReflectionExtension::SERVICE_REFLECTOR);
    }
}

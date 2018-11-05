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
        $reflector = $this->createReflector();
        $this->assertEquals((string) $reflector->reflectClass(__CLASS__)->name(), __CLASS__);
    }

    private function createReflector(array $params = []): Reflector
    {
        $container = PhpactorContainer::fromExtensions([
            WorseReflectionExtension::class,
            FilePathResolverExtension::class,
            ClassToFileExtension::class,
            ComposerAutoloaderExtension::class,
            LoggingExtension::class
        ], $params);

        return $container->get(WorseReflectionExtension::SERVICE_REFLECTOR);
    }
}

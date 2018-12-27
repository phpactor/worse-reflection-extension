<?php

namespace Phpactor\Extension\WorseReflection;

use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\ClassToFile\ClassToFileExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\WorseReflection\Bridge\Phpactor\MemberProvider\DocblockMemberProvider;
use Phpactor\WorseReflection\Core\SourceCodeLocator\NativeReflectionFunctionSourceLocator;
use Phpactor\WorseReflection\Bridge\PsrLog\PsrLogger;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\Bridge\Phpactor\ClassToFileSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Container\Extension;
use Phpactor\MapResolver\Resolver;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Container;
use Phpactor\WorseReflection\Bridge\TolerantParser\Parser\CachedParser;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflector\TolerantFactory;

class WorseReflectionExtension implements Extension
{
    const SERVICE_REFLECTOR = 'worse_reflection.reflector';
    const TAG_SOURCE_LOCATOR = 'worse_reflection.source_locator';
    const TAG_FRAME_WALKER = 'worse_reflection.frame_walker';
    const TAG_MEMBER_PROVIDER = 'worse_reflection.member_provider';

    const PARAM_ENABLE_CACHE = 'worse_reflection.enable_cache';
    const PARAM_STUB_DIR = 'worse_reflection.stub_dir';
    const PARAM_STUB_CACHE_DIR = 'worse_reflection.cache_dir';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_ENABLE_CACHE => true,
            self::PARAM_STUB_CACHE_DIR => '%cache%/worse-reflection',
            self::PARAM_STUB_DIR => '%application_root%/vendor/jetbrains/phpstorm-stubs',
        ]);
    }

    public function load(ContainerBuilder $container)
    {
        $this->registerReflection($container);
        $this->registerSourceLocators($container);
        $this->registerMemberProviders($container);
    }

    private function registerReflection(ContainerBuilder $container)
    {
        $container->register(self::SERVICE_REFLECTOR, function (Container $container) {
            $builder = ReflectorBuilder::create()
                ->withSourceReflectorFactory(new TolerantFactory($container->get('worse_reflection.tolerant_parser')))
                ->enableContextualSourceLocation();

            if ($container->getParameter(self::PARAM_ENABLE_CACHE)) {
                $builder->enableCache();
            }
        
            foreach (array_keys($container->getServiceIdsForTag(self::TAG_SOURCE_LOCATOR)) as $serviceId) {
                $builder->addLocator($container->get($serviceId));
            }

            foreach (array_keys($container->getServiceIdsForTag(self::TAG_FRAME_WALKER)) as $serviceId) {
                $builder->addFrameWalker($container->get($serviceId));
            }

            foreach (array_keys($container->getServiceIdsForTag(self::TAG_MEMBER_PROVIDER)) as $serviceId) {
                $builder->addMemberProvider($container->get($serviceId));
            }
        
            $builder->withLogger(
                new PsrLogger($container->get(LoggingExtension::SERVICE_LOGGER))
            );
        
            return $builder->build();
        });

        $container->register('worse_reflection.tolerant_parser', function (Container $container) {
            return new CachedParser();
        });
    }

    private function registerSourceLocators(ContainerBuilder $container)
    {
        $container->register('worse_reflection.locator.stub', function (Container $container) {
            $resolver = $container->get(FilePathResolverExtension::SERVICE_FILE_PATH_RESOLVER);
            return new StubSourceLocator(
                ReflectorBuilder::create()->build(),
                $resolver->resolve($container->getParameter(self::PARAM_STUB_DIR)),
                $resolver->resolve($container->getParameter(self::PARAM_STUB_CACHE_DIR))
            );
        }, [ self::TAG_SOURCE_LOCATOR => []]);

        $container->register('worse_reflection.locator.function', function (Container $container) {
            return new NativeReflectionFunctionSourceLocator();
        }, [ self::TAG_SOURCE_LOCATOR => []]);

        $container->register('worse_reflection.locator.worse', function (Container $container) {
            return new ClassToFileSourceLocator($container->get(ClassToFileExtension::SERVICE_CONVERTER));
        }, [ self::TAG_SOURCE_LOCATOR => []]);
    }

    private function registerMemberProviders(ContainerBuilder $container)
    {
        $container->register('worse_reflection.member_provider.docblock', function (Container $container) {
            return new DocblockMemberProvider();
        }, [ self::TAG_MEMBER_PROVIDER => []]);
    }
}

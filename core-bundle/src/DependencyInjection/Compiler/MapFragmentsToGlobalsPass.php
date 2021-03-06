<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\ContentProxy;
use Contao\CoreBundle\EventListener\GlobalsMapListener;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\ModuleProxy;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MapFragmentsToGlobalsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $tags = $this->getFragmentTags($container, ContentElementReference::TAG_NAME);
        $elements = $this->getGlobalsMap($tags, 'TL_CTE', ContentProxy::class);

        $tags = $this->getFragmentTags($container, FrontendModuleReference::TAG_NAME);
        $modules = $this->getGlobalsMap($tags, 'FE_MOD', ModuleProxy::class);

        $listener = new Definition(GlobalsMapListener::class, [array_merge($elements, $modules)]);
        $listener->setPublic(true);
        $listener->addTag('contao.hook', ['hook' => 'initializeSystem', 'priority' => 255]);

        $container->setDefinition('contao.listener.'.ContainerBuilder::hash($listener), $listener);
    }

    /**
     * @return array<string,array<int|string,array<int|string,string>>>
     */
    private function getGlobalsMap(array $tags, string $globalsKey, string $proxyClass): array
    {
        $values = [];

        foreach ($tags as $attributes) {
            $values[$globalsKey][$attributes['category']][$attributes['type']] = $proxyClass;
        }

        return $values;
    }

    /**
     * @throws InvalidConfigurationException
     *
     * @return string[]
     */
    private function getFragmentTags(ContainerBuilder $container, string $tag): array
    {
        $result = [];

        foreach ($this->findAndSortTaggedServices($tag, $container) as $priority => $reference) {
            $definition = $container->findDefinition($reference);

            foreach ($definition->getTag($tag) as $attributes) {
                if (!isset($attributes['category'])) {
                    throw new InvalidConfigurationException(
                        sprintf('Missing category for "%s" fragment on service ID "%s"', $tag, (string) $reference)
                    );
                }

                if (!isset($attributes['type'])) {
                    throw new InvalidConfigurationException(
                        sprintf('Missing type for "%s" fragment on service ID "%s"', $tag, (string) $reference)
                    );
                }

                $result[] = $attributes;
            }
        }

        return $result;
    }
}

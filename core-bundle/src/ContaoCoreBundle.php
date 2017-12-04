<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

use Contao\CoreBundle\DependencyInjection\Compiler\AddAssetsPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddImagineClassPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\MapFragmentsToGlobalsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\PickerProviderPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterHookListenersPass;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\DependencyInjection\FragmentRendererPass;

class ContaoCoreBundle extends Bundle
{
    public const SCOPE_BACKEND = 'backend';
    public const SCOPE_FRONTEND = 'frontend';

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ContaoCoreExtension
    {
        return new ContaoCoreExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function registerCommands(Application $application): void
    {
        // disable automatic command registration
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new AddPackagesPass($container->getParameter('kernel.root_dir').'/../vendor/composer/installed.json')
        );

        // Add the assets packages after the Composer packages
        $container->addCompilerPass(new AddAssetsPackagesPass());

        $container->addCompilerPass(new AddSessionBagsPass());
        $container->addCompilerPass(new AddResourcesPathsPass());
        $container->addCompilerPass(new AddImagineClassPass());
        $container->addCompilerPass(new DoctrineMigrationsPass());
        $container->addCompilerPass(new PickerProviderPass());
        $container->addCompilerPass(new RegisterFragmentsPass());
        $container->addCompilerPass(new FragmentRendererPass('contao.fragment.handler'));
        $container->addCompilerPass(new MapFragmentsToGlobalsPass());
        $container->addCompilerPass(new RegisterHookListenersPass(), PassConfig::TYPE_OPTIMIZE);
    }
}
<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image;

use Contao\Config;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\File;
use Contao\Image as LegacyImage;
use Contao\Image\DeferredResizer as ImageResizer;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeCoordinates;
use Contao\Image\ResizeOptions;
use Contao\System;
use Imagine\Gd\Imagine as GdImagine;

/**
 * Resizes image objects and executes the legacy hooks.
 */
class LegacyResizer extends ImageResizer implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var LegacyImage|null
     */
    private $legacyImage;

    /**
     * {@inheritdoc}
     */
    public function resize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        $this->framework->initialize(true);

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');

        if ($this->hasExecuteResizeHook() || $this->hasGetImageHook()) {
            @trigger_error('Using the "executeResize" and "getImage" hooks has been deprecated and will no longer work in Contao 5.0. Replace the "contao.image.resizer" service instead.', E_USER_DEPRECATED);

            $this->legacyImage = null;
            $legacyPath = $image->getPath();

            if (0 === strpos($legacyPath, $rootDir.'/') || 0 === strpos($legacyPath, $rootDir.'\\')) {
                $legacyPath = substr($legacyPath, \strlen($rootDir) + 1);
                $this->legacyImage = new LegacyImage(new File($legacyPath));
                $this->legacyImage->setTargetWidth($config->getWidth());
                $this->legacyImage->setTargetHeight($config->getHeight());
                $this->legacyImage->setResizeMode($config->getMode());
                $this->legacyImage->setZoomLevel($config->getZoomLevel());

                if (
                    ($targetPath = $options->getTargetPath())
                    && (0 === strpos($targetPath, $rootDir.'/') || 0 === strpos($targetPath, $rootDir.'\\'))
                ) {
                    $this->legacyImage->setTargetPath(substr($targetPath, \strlen($rootDir) + 1));
                }

                $importantPart = $image->getImportantPart();
                $imageSize = $image->getDimensions()->getSize();

                $this->legacyImage->setImportantPart([
                    'x' => $importantPart->getX() * $imageSize->getWidth(),
                    'y' => $importantPart->getY() * $imageSize->getHeight(),
                    'width' => $importantPart->getWidth() * $imageSize->getWidth(),
                    'height' => $importantPart->getHeight() * $imageSize->getHeight(),
                ]);
            }
        }

        if ($this->legacyImage && $this->hasExecuteResizeHook()) {
            foreach ($GLOBALS['TL_HOOKS']['executeResize'] as $callback) {
                $return = System::importStatic($callback[0])->{$callback[1]}($this->legacyImage);

                if (\is_string($return)) {
                    return $this->createImage($image, $rootDir.'/'.$return);
                }
            }
        }

        return parent::resize($image, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeResize(ImageInterface $image, ResizeCoordinates $coordinates, string $path, ResizeOptions $options): ImageInterface
    {
        if ($this->legacyImage && $this->hasGetImageHook()) {
            $rootDir = System::getContainer()->getParameter('kernel.project_dir');

            foreach ($GLOBALS['TL_HOOKS']['getImage'] as $callback) {
                $return = System::importStatic($callback[0])->{$callback[1]}(
                    $this->legacyImage->getOriginalPath(),
                    $this->legacyImage->getTargetWidth(),
                    $this->legacyImage->getTargetHeight(),
                    $this->legacyImage->getResizeMode(),
                    $this->legacyImage->getCacheName(),
                    new File($this->legacyImage->getOriginalPath()),
                    $this->legacyImage->getTargetPath(),
                    $this->legacyImage
                );

                if (\is_string($return)) {
                    return $this->createImage($image, $rootDir.'/'.$return);
                }
            }
        }

        if ($image->getImagine() instanceof GdImagine) {
            $dimensions = $image->getDimensions();

            /** @var Config $config */
            $config = $this->framework->getAdapter(Config::class);
            $gdMaxImgWidth = $config->get('gdMaxImgWidth');
            $gdMaxImgHeight = $config->get('gdMaxImgHeight');

            // Return the path to the original image if it cannot be handled
            if (
                $dimensions->getSize()->getWidth() > $gdMaxImgWidth
                || $dimensions->getSize()->getHeight() > $gdMaxImgHeight
                || $coordinates->getSize()->getWidth() > $gdMaxImgWidth
                || $coordinates->getSize()->getHeight() > $gdMaxImgHeight
            ) {
                return $this->createImage($image, $image->getPath());
            }
        }

        return parent::executeResize($image, $coordinates, $path, $options);
    }

    private function hasExecuteResizeHook(): bool
    {
        return !empty($GLOBALS['TL_HOOKS']['executeResize']) && \is_array($GLOBALS['TL_HOOKS']['executeResize']);
    }

    private function hasGetImageHook(): bool
    {
        return !empty($GLOBALS['TL_HOOKS']['getImage']) && \is_array($GLOBALS['TL_HOOKS']['getImage']);
    }
}

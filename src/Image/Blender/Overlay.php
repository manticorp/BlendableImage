<?php
namespace Manticorp\Image\Blender;

class Overlay extends \Manticorp\Image\Blender
{
    public function _blend($opacity = 1, $fill = 1)
    {
        // OVERLAY MODE {
        $destX = ($this->base->getWidth()  - $this->top->getWidth() ) / 2;
        $destY = ($this->base->getHeight() - $this->top->getHeight()) / 2;
        // This line causes all GD operations to use the overlay algorithm
        // when blending pixels together.
        $baseImg = $this->base->getImage();
        imagelayereffect($baseImg, IMG_EFFECT_OVERLAY);
        // Blend the top image onto the base image.
        imagecopy(
            $baseImg, // destination
            $this->top->getImage(), // source
            // destination x and y
            $destX, $destY,
            // x, y, width, and height of the area of the source to copy
            0, 0, $this->base->getWidth(), $this->base->getHeight()
        );
        // } OVERLAY
        return $baseImg;
    }

    public function _imagickBlend($opacity = 1, $fill = 1)
    {
        $baseImg    = $this->base->getImage();
        $overlayImg = $this->top->getImage();

        $overlayImg->setImageOpacity($opacity);

        $baseImg->compositeImage($overlayImg, \Imagick::COMPOSITE_OVERLAY, 0, 0);

        return $baseImg;
    }
}
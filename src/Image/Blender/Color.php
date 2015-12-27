<?php
namespace Manticorp\Image\Blender;

class Color extends \Manticorp\Image\Blender
{
    public function _blend($opacity = 1, $fill = 1)
    {
        $opacity = min(max($opacity,0),1);

        if($opacity === 0){
            return $this->base->getImage();
        }

        $destX = ($this->base->getWidth()  - $this->top->getWidth()) / 2;
        $destY = ($this->base->getHeight() - $this->top->getHeight()) / 2;

        $w = $this->top->getWidth();
        $h = $this->top->getHeight();

        $baseImg    = $this->base->getImage();
        $overlayImg = $this->top->getImage();

        $baseIsTrueColor = $this->base->getIsTrueColor();
        $topIsTrueColor  = $this->top->getIsTrueColor();

        for ($x = 0; $x < $w; ++$x) {
            for ($y = 0; $y < $h; ++$y) {

                $baseColor = $this->getColorAtPixel($baseImg,    $x + $destX, $y + $destY, $baseIsTrueColor);
                $topColor  = $this->getColorAtPixel($overlayImg, $x,          $y,          $topIsTrueColor );

                // First get the colors for the base and top pixels.
                $destColor = $baseColor = $this->normalisePixel($baseColor);
                $topColor  = $this->normalisePixel($topColor);

                $destHsl = $this->rgbToHsl($destColor);
                $topHsl  = $this->rgbToHsl($topColor);
                $destHsl = array('hue' => $topHsl['hue'], 'saturation' => $topHsl['saturation'], 'luminance' => $destHsl['luminance']);

                $destColor = $this->hslToRgb($destHsl);
                $destColor['alpha'] = $baseColor['alpha'];

                if($opacity !== 1) {
                    $destColor = $this->opacityPixel($baseColor, $destColor, $opacity);
                }
                // ...I wonder if this will work...
                if($topColor['alpha'] != 0) {
                    $destColor = $this->opacityPixel($baseColor, $destColor, 1-$topColor['alpha']);
                }

                $destColor = $this->integerPixel($this->deNormalisePixel($destColor));

                // Now that we have a valid color index, set the pixel to that color.
                imagesetpixel(
                    $baseImg,
                    $x + $destX, $y + $destY,
                    $this->getColorIndex($baseImg, $destColor)
                );
            }
        }

        return $baseImg;
    }

    public function _imagickBlend($opacity = 1, $fill = 1)
    {
        $baseImg    = $this->base->getImage();
        $overlayImg = $this->top->getImage();

        $overlayImg->setImageOpacity($opacity);

        $baseImg->compositeImage($overlayImg, \Imagick::COMPOSITE_COLORIZE, 0, 0);

        return $baseImg;
    }
}
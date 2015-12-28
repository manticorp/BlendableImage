<?php
namespace Manticorp\Image;

class Blender
{
    public $base;
    public $top;
    public $hasImagick;

    public function __construct(\Manticorp\Image $base, \Manticorp\Image $top)
    {
        $this->hasImagick  = \Manticorp\Image::hasImagick();
        $this->base = $base;
        $this->top  = clone $top;
    }

    public function blend($opacity = 1, $fill = 1)
    {
        if ($this->hasImagick) {
            if (method_exists($this, '_imagickBlend')) {
                return call_user_func_array(array($this, '_imagickBlend'), func_get_args());
            } else {
                throw new \InvalidArgumentException('Blend type not available when Imagick extension is loaded - sorry!');
            }
        } else {
            return call_user_func_array(array($this, '_blend'), func_get_args());
        }
    }

    public function _blend($opacity = 1, $fill = 1)
    {
        return $this->base;
    }

    public function genericBlend($opacity = 1, $fill = 1, $mode = 'COPY')
    {
        $class = new \ReflectionClass("\Imagick");
        if ($class->hasConstant('COMPOSITE_'.strtoupper($mode))) {
            $baseImg    = $this->base->getImage();
            $overlayImg = $this->top->getImage();

            $overlayImg->setImageOpacity($opacity);

            $baseImg->compositeImage($overlayImg, constant('\Imagick::COMPOSITE_'.strtoupper($mode)), 0, 0);
        } else {
            throw new \InvalidArgumentException('Blending mode ' . $mode . ' not available using Imagick');
            return null;
        }
        return $baseImg;
    }

    public function normalisePixel($pixel)
    {
        return array(
            'red'   => $pixel['red']    / 255.0,
            'green' => $pixel['green']  / 255.0,
            'blue'  => $pixel['blue']   / 255.0,
            'alpha' => $pixel['alpha']  / 127.0,
        );
    }

    public function deNormalisePixel($pixel)
    {
        return array(
            'red'   => $pixel['red']    * 255.0,
            'green' => $pixel['green']  * 255.0,
            'blue'  => $pixel['blue']   * 255.0,
            'alpha' => $pixel['alpha']  * 127.0,
        );
    }

    public function integerPixel($pixel)
    {
        return array(
            'red'   => (int) $pixel['red']  ,
            'green' => (int) $pixel['green'],
            'blue'  => (int) $pixel['blue'] ,
            'alpha' => (int) $pixel['alpha'],
        );
    }

    public function opacityPixel($bottom, $top, $opacity = 1)
    {
        $opacity = max(min($opacity, 1), 0);
        if ($opacity == 1) {
            return $top;
        }
        if ($opacity == 0) {
            return $bottom;
        }

        $left  = 1-$opacity;
        $right = $opacity;
        foreach ($bottom as $color => &$value) {
            $value = (($value * $left) + ($top[$color] * $right));
        }
        return $bottom;
    }

    public function getColorAtPixel($img, $x, $y, $isTrueColor = true)
    {
        $color = imagecolorat($img, $x, $y);
        // If the image is true-color, we simply use bitwise operations to separate out
        // red, green, blue, and alpha from the result of imagecolorat above.
        if ($isTrueColor) {
            $color = array(
                'red'   => ($color >> 16) & 0xFF,
                'green' => ($color >> 8) & 0xFF,
                'blue'  => $color & 0xFF,
                'alpha' => ($color & 0x7F000000) >> 24,
            );
        } // If the image uses indexed color, we can get the color components by looking up
        // the color index in the image's color table.
        else {
            $color = imagecolorsforindex($img, $color);
        }
        return $color;
    }

    public function getColorIndex($img, $color)
    {
        // Now set the destination pixel.
        $colorIndex = imagecolorallocatealpha($img, $color['red'], $color['green'], $color['blue'], $color['alpha']);

        // If we failed to allocate the color, try to find the already allocated color
        // that is closest to what we want.
        if ($colorIndex === false) {
            $colorIndex = imagecolorclosestalpha($img, $color['red'], $color['green'], $color['blue'], $color['alpha']);
        }

        return $colorIndex;
    }

    /**
     * Converts an RGB color value to HSL. Conversion formula
     * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
     * Assumes r, g, and b are contained in the set [0, 1] and
     * returns h, s, and l in the set [0, 1].
     *
     * @param   array pixel array('red' => Int, 'green' => Int, 'blue' => Int);
     * @return  Array       The HSL representation, array('hue' => $h, 'saturation' => $s, 'luminance' => $l);
     */
    public function rgbToHsl($pixel)
    {
        $max = max($pixel['red'], $pixel['green'], $pixel['blue']);
        $min = min($pixel['red'], $pixel['green'], $pixel['blue']);
        $h = $s = $l = ($max + $min) / 2;

        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $pixel['red']:
                    $h = ($pixel['green'] -  $pixel['blue']) / $d + ($pixel['green'] < $pixel['blue'] ? 6 : 0);
                    break;
                case $pixel['green']:
                    $h = ($pixel['blue']  -   $pixel['red']) / $d + 2;
                    break;
                case $pixel['blue']:
                    $h = ($pixel['red']   - $pixel['green']) / $d + 4;
                    break;
            }
            $h /= 6;
        } else {
            $h = $s = 0; // achromatic
        }

        return array('hue' => $h, 'saturation' => $s, 'luminance' => $l);
    }

    /**
     * Converts an HSL color value to RGB. Conversion formula
     * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
     * Assumes h, s, and l are contained in the set [0, 1] and
     * returns r, g, and b in the set [0, 255].
     *
     * @param   array pixel A HSL pixel array('hue' => $h, 'saturation' => $s, 'luminance' => $l);
     * @return  Array       The RBG representation, array('red' => Int, 'green' => Int, 'blue' => Int);
     */
    public function hslToRgb($pixel)
    {
        if ($pixel['saturation'] !== 0) {
            $q = $pixel['luminance'] < 0.5 ? $pixel['luminance'] * (1 + $pixel['saturation']) : $pixel['luminance'] + $pixel['saturation'] - $pixel['luminance'] * $pixel['saturation'];
            $p = 2 * $pixel['luminance'] - $q;
            $r = $this->hueToRgb($p, $q, $pixel['hue'] + 1/3);
            $g = $this->hueToRgb($p, $q, $pixel['hue']);
            $b = $this->hueToRgb($p, $q, $pixel['hue'] - 1/3);
        } else {
            $r = $g = $b = $pixel['luminance']; // achromatic
        }

        return array('red' => $r, 'green' => $g, 'blue' => $b);
    }

    public function hueToRgb($p, $q, $t)
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < (1/6)) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < (1/2)) {
            return $q;
        }
        if ($t < (2/3)) {
            return $p + ($q - $p) * (2/3 - $t) * 6;
        }
        return $p;
    }
}

# Manticorp\Image

An image library that gives photoshop-like layer blending image capabilities.

Requires EITHER the PHP GD image library (VERY slow) OR Imagick (fast!)

As an example, using GD image library on my i7 computer with 16GB ram, blending two 500px square images takes over 4 seconds.

This is mostly due to PHP having to iterate over every pixel.

# Installation

simply require the package in composer

```
composer require manticorp/image
```

# Usage

## Simple example

```php
<?php

$filename1 = 'baseimage.png';
$filename2 = 'topimage.png';

$base = new Manticorp\Image($filename1);
$top  = new Manticorp\Image($filename2);

$dim = 500;

$base->setDimensions($dim, $dim);
$top->setDimensions($base->getDimensions());

$bottom->setOutputFn('./temp/output.png');

$opacity = 1;
$fill    = 1; // Currently not implemented

$output = clone $bottom;
$output->blendWith($top, 'Overlay', $opacity, $fill);
echo $output->getImgTag(); // <img src='./temp/output.png'>
```

## Simple example 2

```php
<?php

$filename1 = 'baseimage.png';
$filename2 = 'topimage.png';

$modes = Manticorp\Image::getAvailableBlendingModes();

$base = new Manticorp\Image($filename1);
$top  = new Manticorp\Image($filename2);

$dim = 200;

$base->setDimensions($dim, $dim);
$top->setDimensions($base->getDimensions());

$opacity = 1;
$fill    = 1; // Currently not implemented

foreach($modes as $mode){
    $output = clone $base;
    $output->setOutputFn('./output-' . $mode . '.png');
    $output->blendWith($top, $mode, $opacity, $fill);
    echo "<div style='width:200px; height:300px; float:left;'><h3>".$mode."</h3>";
    echo $output->getImgTag(); // <img src='./output-{$mode}.png'>
    echo "</div>";
}
```

## Magic Methods

An array of magic methods are also available

```php
<?php
$filename1 = 'baseimage.png';
$filename2 = 'topimage.png';

$modes = Manticorp\Image::getAvailableBlendingModes();

$base = new Manticorp\Image($filename1);
$top  = new Manticorp\Image($filename2);

$dim = 200;

$base->setDimensions($dim, $dim);
$top->setDimensions($base->getDimensions());

$opacity = 1;
$fill    = 1; // Currently not implemented

$base->overlayWith($top, $opacity, $fill);

echo $base;
```

## Available blending modes

**Bold** = Photoshop based.

* Add
* Atop
* Blend
* Blur
* BumpMap
* ChangeMask
* Clear
* **Color**
* **ColorBurn**
* **ColorDodge**
* Colorize
* Copy
* CopyBlack
* CopyBlue
* CopyCyan
* CopyGreen
* CopyMagenta
* CopyOpacity
* CopyRed
* CopyYellow
* **Darken**
* DarkenIntensity
* **DarkerColor**
* Default
* Difference
* Displace
* Dissolve
* Distort
* **Divide**
* DivideDST
* DivideSrc
* DST
* DSTatop
* DSTin
* DSTout
* DSTover
* Exclusion
* **HardLight**
* **HardMix**
* **Hue**
* In
* **Lighten**
* LightenIntensity
* **LighterColor**
* **LinearBurn**
* **LinearDodge**
* **LinearLight**
* Luminize
* **Luminosity**
* Mathematics
* Minus
* MinusDST
* MinusSrc
* Modulate
* ModulusAdd
* ModulusSubtract
* **Multiply**
* No
* **Normal**
* Out
* Over
* **Overlay**
* Pegtoplight
* **PinLight**
* Plus
* Replace
* Saturate
* **Saturation**
* **Screen**
* **SoftLight**
* Src
* SrcAtop
* SrcIn
* SrcOut
* SrcOver
* **Subtract**
* Threshold
* Undefined
* **VividLight**
* Xor
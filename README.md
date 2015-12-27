# Manticorp\Image

An image library that gives photoshop-like layer blending image capabilities.

Requires EITHER the PHP GD image library (slow) OR Imagick (fast!)

# Installation

## TODO packagist installation with composer

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
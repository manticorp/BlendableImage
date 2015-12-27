<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

$bottom = new Manticorp\Image(__DIR__ . '/resources/pattern1.png');
$top    = new Manticorp\Image(__DIR__ . '/resources/poplin_small.png');

$dim = 500;

$bottom->setDimensions($dim,$dim);
$top->setDimensions($bottom->getDimensions());

$bottom->blendWith($top, 'Hue', 1, 1);
echo $bottom;
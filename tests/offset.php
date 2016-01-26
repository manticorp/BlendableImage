<?php

$tempFiles = glob('./tmp/*');
array_map('unlink',$tempFiles);

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

$modes = Manticorp\Image::getAvailableBlendingModes();

$filename1 = __DIR__ . '/resources/trettofrappucino.png';
$filename2 = __DIR__ . '/resources/poplin_small.png';

$top = new Manticorp\Image($filename1);
$base  = new Manticorp\Image($filename2);

$top->setDimensions(300,300);
$base->setDimensions(100,100);

$output = clone $top;
$output->setOutputFn('./tmp/output-offset-multiply.png');
$output->blendWith($base, 'multiply', 1, 1, array('offsetx'=>10, 'offsety'=>10));
$output->blendWith($base, 'multiply', 1, 1, array('offsetx'=>10, 'offsety'=>110));
$output->blendWith($base, 'multiply', 1, 1, array('offsetx'=>110, 'offsety'=>10));
$output->blendWith($base, 'multiply', 1, 1, array('offsetx'=>110, 'offsety'=>110));

echo $output->getImgTag(); // <img src='/temp/output.png'>

$output = clone $top;
$output->setOutputFn('./tmp/output-offset-overlay.png');
$output->blendWith($base, 'overlay', 1, 1, array('offsetx'=>10, 'offsety'=>10));
$output->blendWith($base, 'overlay', 1, 1, array('offsetx'=>10, 'offsety'=>110));
$output->blendWith($base, 'overlay', 1, 1, array('offsetx'=>110, 'offsety'=>10));
$output->blendWith($base, 'overlay', 1, 1, array('offsetx'=>110, 'offsety'=>110));

echo $output->getImgTag(); // <img src='/temp/output.png'>

$output = clone $top;
$output->setOutputFn('./tmp/output-offset-hardlight.png');
$output->blendWith($base, 'hardlight', 1, 1, array('offsetx'=>10, 'offsety'=>10));
$output->blendWith($base, 'hardlight', 1, 1, array('offsetx'=>10, 'offsety'=>110));
$output->blendWith($base, 'hardlight', 1, 1, array('offsetx'=>110, 'offsety'=>10));
$output->blendWith($base, 'hardlight', 1, 1, array('offsetx'=>110, 'offsety'=>110));

echo $output->getImgTag(); // <img src='/temp/output.png'>
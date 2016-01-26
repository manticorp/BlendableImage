<?php

$tempFiles = glob('./tmp/*');
array_map('unlink',$tempFiles);

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

$modes = Manticorp\Image::getAvailableBlendingModes();

$filename1 = __DIR__ . '/resources/trettofrappucino.png';
$filename2 = __DIR__ . '/resources/poplin_small.png';

$base = new Manticorp\Image($filename1);
$top  = new Manticorp\Image($filename2);

$dim = 200;

$base->setDimensions($dim, $dim);
$top->setDimensions($base->getDimensions());

$fill    = 1; // Currently not implemented

foreach($modes as $mode){
    echo "<div style='width:{$dim}px;height:".($dim*5*1.3)."px;float:left;'><h1 style='font-size: 16px;'>".$mode."</h1>";

    for($opacity = 0.2; $opacity <=1; $opacity += 0.2){
        echo "<h2 style='font-size:12px;'>Opacity: $opacity</h2>";
        $output = clone $top;
        $output->setOutputFn('./tmp/output-' . $mode . '-' . $opacity.'.png');
        $output->blendWith($base, $mode, $opacity, $fill);

        echo $output->getImgTag(); // <img src='/temp/output.png'>
    }

    echo "</div>";
}
<?php

$tempFiles = glob('./tmp/*');
array_map('unlink',$tempFiles);

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

$modes = Manticorp\Image::getAvailableBlendingModes();

$filename1 = __DIR__ . '/resources/pattern1.png';
$filename2 = __DIR__ . '/resources/poplin_small.png';

$base = new Manticorp\Image($filename1);
$top  = new Manticorp\Image($filename2);

$dim = 100;

$base->setDimensions($dim, $dim);
$top->setDimensions($base->getDimensions());


$opacity = 1;
$fill    = 1; // Currently not implemented

// echo "<pre>";
// var_dump($modes);
// print_r($modes);
// foreach($modes as $mode){
//     echo "<h2>".$mode."</h2>";
// }
// die();

foreach($modes as $mode){
    echo "<div style='width:100px;height:130px;float:left;'><h1 style='font-size: 12px;'>".$mode."</h1>";

    $output = clone $base;
    $output->setOutputFn('./tmp/output-' . $mode . '.png');
    $output->blendWith($top, $mode, $opacity, $fill);

    echo $output->getImgTag(); // <img src='/temp/output.png'>

    echo "</div>";
}
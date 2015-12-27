<?php
namespace Manticorp;

class Image
{
    public  $fn;
    public  $image;
    public  $isTrueColor;
    private $originalWidth;
    private $originalHeight;
    public  $width;
    public  $height;
    public  $outputFn;
    public  $outputType = 'png';
    public  $quality = 9;
    private $needsResize = false;
    private $hasChanged  = true;
    public  $hasImagick;

    private $allowedFileTypes = array(
        'png'   => 'png',
        'jpeg'  => 'jpeg',
        'jpg'   => 'jpeg',
        'gif'   => 'gif',
        'bmp'   => 'wbmp',
        'wbmp'  => 'wbmp',
    );

    public function __construct($fn = null)
    {
        $this->hasImagick  = extension_loaded('imagick');

        if($fn !== null){
            $this->setFile($fn);
        }
        return $this;
    }

    public function __destruct()
    {
        @ imagedestroy($this->image);
    }

    public function __call($method, $arguments)
    {
        if(substr($method, -4) === "With"){
            $blendType = substr($method,0,-4);
            $newArgs = array($arguments[0],$blendType);
            for($i = 1; $i < count($arguments); $i++){
                $newArgs[] = $arguments[$i];
            }
            return call_user_func_array(array($this,'blendWith'),$newArgs);
        }
    }

    public function __toString()
    {
        return $this->getImgTag();
    }

    public function __clone()
    {
        $this->image = $this->getImageResource($this->fn);
    }

    public function resize()
    {
        if($this->needsResize){
            if($this->hasImagick){
                $success = $this->image->scaleimage(
                    $this->getWidth(),
                    $this->getHeight()/*,
                    \Imagick::FILTER_CATROM,
                    1,
                    false*/
                );
                if($success !== true) {
                    throw new \Exception('Failed to resize image.');
                }
            } else {
                $success = imagescale($this->image, $this->width, $this->height, IMG_BILINEAR_FIXED);
                if($success !== false) {
                    $this->image = $success;
                }
            }
        }
        return $this;
    }

    /**
     * Gets the image resource - returns a GD image resource if Imagick isn't present,
     * otherwise, an instance of Imagick is returned
     *
     * @changes depending on Imagick presence
     * @param  string $fn Filename
     * @return object     Imagick or GD Resource depending on whether Imagick is loaded
     */
    public function getImageResource($fn)
    {
        if($this->hasImagick){
            $image = new \Imagick();
            $image->readImage(realpath($fn));
        } else {
            $filetype = explode(".",$fn);
            $filetype = $filetype[count($filetype)-1];
            if(in_array($filetype, array_keys($this->allowedFileTypes))) {
                $function = 'imagecreatefrom'.$this->allowedFileTypes[$filetype];
                $image    = $function($fn);
            } else {
                throw new \InvalidArgumentException("Invalid filetype " . $filetype . " given to Image");
            }
        }
        return $image;
    }

    /**
     * Loads the image file into a resource
     *
     * @changes depending on Imagick presence
     */
    public function loadImage()
    {
        if(is_file($this->fn)){
            $this->image = $this->getImageResource($this->fn);
            if(!$this->hasImagick && !is_null($this->image)){
                $this->isTrueColor    = imageistruecolor($this->image);
                $this->originalWidth  = imagesx($this->image);
                $this->originalHeight = imagesy($this->image);
            }
        }
        return $this;
    }


    /**
     * Blends this with the other $image
     *
     * @changes depending on Imagick presence
     * @param  Image   $image   The top image
     * @param  string  $mode    The mode to use for blending
     * @param  number  $opacity Opacity to use for top layer
     * @param  number  $fill    Fill to use for top layer
     * @return \Image           This
     */
    public function blendWith(\Manticorp\Image $image, $mode = 'normal', $opacity = 1, $fill = 1)
    {
        if(is_numeric($mode)){
            $fill = $opacity;
            $opacity = $mode;
        }
        $this->resize();
        $mode = str_replace(" ","",ucwords(trim($mode)));
        $class = 'Image\\Blender\\'.$mode;
        if(class_exists($class)) {
            $blender = new $class($this, $image);
            $this->setImage($blender->blend($opacity, $fill));
        } else if($this->hasImagick) {
            $blender = new Image\Blender($this, $image);
            $this->setImage($blender->genericBlend($opacity, $fill, $mode));
        } else {
            throw new \InvalidArgumentException('Blending mode ' . $mode . ' not available using GD image library');
        }
        return $this->changed();
    }

    /**
     * Generates the output file
     *
     * @changes depending on Imagick presence
     * @return Image this
     */
    public function generateOutputFile()
    {
        if(in_array($this->outputType, array_keys($this->allowedFileTypes))) {
            if($this->hasImagick){
                // $this->image->setImageCompressionQuality(max(0,min($this->quality*10,100)))
                $fn = realpath(pathinfo($this->outputFn, PATHINFO_DIRNAME)) . DIRECTORY_SEPARATOR .  pathinfo($this->outputFn, PATHINFO_BASENAME);
                $this->image->writeImage($fn);
            } else {
                $function = 'image'.$this->allowedFileTypes[$this->outputType];
                if(file_exists($this->outputFn)){
                    unlink($this->outputFn);
                }
                $function($this->getImage(), $this->outputFn, $this->quality);
            }
        } else {
            throw new \InvalidArgumentException("Invalid filetype " . $this->outputType . " given to Image");
        }
        return $this;
    }

    /**
     * Alias for blendWith
     *
     * @see blendWith
     */
    public function mergeWith(Image $image)
    {
        return call_user_func_array(array($this, 'blendWith'), func_get_args());
    }

    public function setHeight($height)
    {
        $this->height      = $height;
        $this->needsResize = true;
        return $this->changed();
    }

    public function setWidth($width)
    {
        $this->width       = $width;
        $this->needsResize = true;
        return $this->changed();
    }

    public function setDimensions($width, $height = null)
    {
        if(is_array($width)){
            $height = $width['y'];
            $width  = $width['x'];
        }
        if($height == null){
            $height = $width * ($this->originalHeight/$this->originalWidth);
        }
        $this->setWidth($width);
        $this->setHeight($height);
        return $this->changed();
    }

    public function getOriginalWidth()
    {
        return $this->originalWidth;
    }

    public function getOriginalHeight()
    {
        return $this->originalHeight;
    }

    public function getDimensions()
    {
        return array('x' => $this->getWidth(), 'y' => $this->getHeight());
    }

    public function getOriginalDimensions()
    {
        return array('x' => $this->getOriginalWidth(), 'y' => $this->getOriginalHeight());
    }

    public function generateRandomFileName()
    {
        if(!file_exists('./tmp')){
            mkdir('./tmp');
        }
        $this->setOutputFn('./tmp/'.(rand()*1000).'.'.$this->outputType);
        return $this;
    }

    public function changed()
    {
        $this->_setHasChanged(true);
        return $this;
    }

    public function setFile($fn)
    {
        if(is_file($fn)){
            $this->fn = $fn;
            $this->loadImage();
            $this->setWidth($this->getOriginalWidth());
            $this->setHeight($this->getOriginalHeight());
        } else {
            throw new \InvalidArgumentException("File doesn't exist: " . $fn);
        }
        return $this;
    }

    public function getImgTag()
    {
        if(is_null($this->getOutputFn()) || ($this->getHasChanged() && file_exists($this->getOutputFn()))){
            $this->generateRandomFileName();
        }
        if(!file_exists($this->getOutputFn())){
            $this->generateOutputFile();
        }
        return '<img src="' . $this->outputFn . '" />'.PHP_EOL;
    }

    public function getImage()
    {
        return $this->resize()->image;
    }

    /**
     * Gets the value of fn.
     *
     * @return mixed
     */
    public function getFn()
    {
        return $this->fn;
    }

    /**
     * Sets the value of fn.
     *
     * @param mixed $fn the fn
     *
     * @return self
     */
    public function setFn($fn)
    {
        $this->fn = $fn;

        return $this;
    }

    /**
     * Sets the value of image.
     *
     * @param mixed $image the image
     *
     * @return self
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Gets the value of isTrueColor.
     *
     * @return mixed
     */
    public function getIsTrueColor()
    {
        return $this->isTrueColor;
    }

    /**
     * Sets the value of isTrueColor.
     *
     * @param mixed $isTrueColor the is true color
     *
     * @return self
     */
    public function setIsTrueColor($isTrueColor)
    {
        $this->isTrueColor = $isTrueColor;

        return $this;
    }

    /**
     * Sets the value of originalWidth.
     *
     * @param mixed $originalWidth the original width
     *
     * @return self
     */
    private function _setOriginalWidth($originalWidth)
    {
        $this->originalWidth = $originalWidth;

        return $this;
    }

    /**
     * Sets the value of originalHeight.
     *
     * @param mixed $originalHeight the original height
     *
     * @return self
     */
    private function _setOriginalHeight($originalHeight)
    {
        $this->originalHeight = $originalHeight;

        return $this;
    }

    /**
     * Gets the value of width.
     *
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Gets the value of height.
     *
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Gets the value of outputFn.
     *
     * @return mixed
     */
    public function getOutputFn()
    {
        return $this->outputFn;
    }

    /**
     * Sets the value of outputFn.
     *
     * @param mixed $outputFn the output fn
     *
     * @return self
     */
    public function setOutputFn($outputFn)
    {
        $this->outputFn = $outputFn;

        return $this;
    }

    /**
     * Gets the value of outputType.
     *
     * @return mixed
     */
    public function getOutputType()
    {
        return $this->outputType;
    }

    /**
     * Sets the value of outputType.
     *
     * @param mixed $outputType the output type
     *
     * @return self
     */
    public function setOutputType($outputType)
    {
        $this->outputType = $outputType;

        return $this;
    }

    /**
     * Gets the value of quality.
     *
     * @return mixed
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * Sets the value of quality.
     *
     * @param mixed $quality the quality
     *
     * @return self
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Gets the value of allowedFileTypes.
     *
     * @return mixed
     */
    public function getAllowedFileTypes()
    {
        return $this->allowedFileTypes;
    }

    /**
     * Sets the value of allowedFileTypes.
     *
     * @param mixed $allowedFileTypes the allowed file types
     *
     * @return self
     */
    private function _setAllowedFileTypes($allowedFileTypes)
    {
        $this->allowedFileTypes = $allowedFileTypes;

        return $this;
    }

    /**
     * Gets the value of needsResize.
     *
     * @return mixed
     */
    public function getNeedsResize()
    {
        return $this->needsResize;
    }

    /**
     * Sets the value of needsResize.
     *
     * @param mixed $needsResize the needs resize
     *
     * @return self
     */
    private function _setNeedsResize($needsResize)
    {
        $this->needsResize = $needsResize;

        return $this;
    }

    /**
     * Gets the value of hasChanged.
     *
     * @return mixed
     */
    public function getHasChanged()
    {
        return $this->hasChanged;
    }

    /**
     * Sets the value of hasChanged.
     *
     * @param mixed $hasChanged the has changed
     *
     * @return self
     */
    private function _setHasChanged($hasChanged)
    {
        $this->hasChanged = $hasChanged;

        return $this;
    }
}
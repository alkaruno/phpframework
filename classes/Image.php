<?php

class Image
{
    const TYPE_GD = 'gd';
    const TYPE_IMAGEMAGICK = 'imagemagick';

    private $type;
    private $filename;
    private $width;
    private $height;
    private $quality = 85;
    private $thumbnail = false;
    private $crop = false;

    public static function create($filename, $type = self::TYPE_IMAGEMAGICK)
    {
        $image = new Image();
        $image->type = $type;
        $image->filename = $filename;
        return $image;
    }

    public function resize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    public function quality($quality)
    {
        $this->quality = $quality;
        return $this;
    }

    public function crop($value)
    {
        $this->crop = $value;
        return $this;
    }

    public function thumbnail($value)
    {
        $this->thumbnail = $value;
        return $this;
    }

    public function save($filename)
    {
        $pathinfo = pathinfo($filename);
        if (!file_exists($pathinfo['dirname'])) {
            mkdir($pathinfo['dirname'], 0777, true);
        }

        $convert = isset(App::$config['convert']) ? App::$config['convert'] : 'convert';
        $dim = null;

        if ($this->width != null && $this->height != null) {
            if ($this->crop === true) {
                $dim = (2 * $this->width) . 'x -resize x' . (2 * $this->height) . '< -resize 50% -gravity center -crop ' . $this->width . 'x' . $this->height . '+0+0 +repage';
            } else {
                $dim = $this->width . 'x' . $this->height;
            }
        }

        if ($this->width != null && $this->height == null) {
            $dim = $this->width . 'x';
        }

        if ($this->width == null && $this->height != null) {
            $dim = 'x' . $this->height;
        }

        if ($dim == null) {
            throw new Exception('Illegal state');
        }

        $method = $this->thumbnail ? '-thumbnail' : '-resize';

        // TODO не ресейзить картинку, если она уже нужных размеров

        Logger::info(escapeshellcmd($convert . ' ' . $method . ' ' . $dim . ' -quality ' . $this->quality . ' ' . escapeshellarg($this->filename) . ' ' . escapeshellarg($filename)));

        if ($error = exec(escapeshellcmd($convert . ' ' . $method . ' ' . $dim . ' -quality ' . $this->quality . ' ' . escapeshellarg($this->filename) . ' ' . escapeshellarg($filename)))) {
            throw new Exception($error, 500);
        }
    }
}
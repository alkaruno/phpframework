<?php

namespace Xplosio\PhpFramework\View;

abstract class AbstractViewRenderer
{
    protected $viewsPath;
    protected $cachePath;
    protected $options;

    public function __construct($viewsPath, $cachePath, $options)
    {
        $this->viewsPath = $viewsPath;
        $this->cachePath = $cachePath;
        $this->options = $options;
    }

    abstract public function render($filename, $attributes);
}

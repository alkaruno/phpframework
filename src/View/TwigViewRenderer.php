<?php

namespace Xplosio\PhpFramework\View;

class TwigViewRenderer extends AbstractViewRenderer
{
    public function render($filename, $attributes)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($this->viewsPath), [
            'cache' => $this->cachePath,
        ]);

        $twig->display($filename, $attributes);
    }
}

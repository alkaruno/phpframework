<?php

namespace Xplosio\PhpFramework\View;

use Xplosio\PhpFramework\App;

class SmartyViewRenderer extends AbstractViewRenderer
{
    public function render($filename, $attributes)
    {
        $smarty = new \Smarty();
        $smarty->muteExpectedErrors();
        $smarty->setTemplateDir($this->viewsPath);
        $smarty->setCompileDir($this->cachePath);
        $smarty->assign($attributes);

        $pluginsPaths = App::getConfigValue('pluginsPaths', [], $this->options);
        array_walk($pluginsPaths, function ($path) use ($smarty) {
            $smarty->addPluginsDir($path);
        });

        $smarty->display($filename);
    }
}

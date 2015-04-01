<?php

namespace Xplosio\PhpFramework;

interface Filter
{
    public function filter(Request $request);
}
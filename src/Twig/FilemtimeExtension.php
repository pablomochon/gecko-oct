<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FilemtimeExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('filemtime', [$this, 'getFilemtime']),
        ];
    }

    public function getFilemtime($var): int
    {
        return (int) @filemtime(__DIR__ . '/../../public' . $var);
    }
}
<?php

declare(strict_types=1);

namespace App\Enums\PostPlatform;

enum AspectRatio: string
{
    case Square = '1:1';
    case Portrait = '4:5';
    case Landscape = '16:9';
    case Original = 'original';
}

<?php

namespace App\Lab\Concerns;

trait RegistersThemes
{
    public function registerTheme(string $theme = null): void
    {
        $class = basename(str_replace('\\', '/', static::class));

        $theme ??= 'App\\Lab\\Renderers\\' . $class . 'Renderer';

        static::$themes['default'][static::class] = $theme;
    }
}

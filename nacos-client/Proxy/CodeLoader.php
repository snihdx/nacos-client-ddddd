<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Longxiaoyang\NacosClient\Proxy;

use Hyperf\Utils\Composer;

class CodeLoader
{
    public function getCodeByClassName(string $className): string
    {
        $file = Composer::getLoader()->findFile($className);
        if (! $file) {
            return '';
        }
        $content = file_get_contents($file);
        return $content;
    }

    public function getPathByClassName(string $className): string
    {
        return Composer::getLoader()->findFile($className);
    }
}

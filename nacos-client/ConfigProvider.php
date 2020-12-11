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
namespace Longxiaoyang\NacosClient;

use Longxiaoyang\NacosClient\Listener\AddNacosConsumerDefinitionListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                AddNacosConsumerDefinitionListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}

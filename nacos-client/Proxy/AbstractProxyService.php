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

use Longxiaoyang\NacosClient\ServiceClient;
use Psr\Container\ContainerInterface;

abstract class AbstractProxyService
{
    /**
     * @var ServiceClient
     */
    protected $client;

    public function __construct(ContainerInterface $container, string $serviceName, string $serviceInterface, array $options = [])
    {
        $this->client = make(ServiceClient::class, [
            'container' => $container,
            'serviceName' => $serviceName,
            'serviceInterface' => $serviceInterface,
            'options' => $options,
        ]);
    }
}

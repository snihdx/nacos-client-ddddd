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

use App\Common\NacosService;
use Hyperf\Contract\NormalizerInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Longxiaoyang\NacosClient\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Pool\SimplePool\PoolFactory;
use Hyperf\Di\Container;
use Swoole\Coroutine\Http\Client;

class ServiceClient extends AbstractServiceClient
{
    /**
     * @var MethodDefinitionCollectorInterface
     */
    protected $methodDefinitionCollector;

    /**
     * @Inject
     * @var Container
     */
    public $container;

    protected $NacosServicesOptimal = [];


    /**
     * @Inject
     * @var AnnotationCollector
     */
    protected $Annotation;

    /**
     * @var string
     */
    protected $serviceInterface;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    public function __construct(ContainerInterface $container, string $serviceName, string $serviceInterface, array $options = [])
    {
        $this->serviceName      = $serviceName;
        $this->serviceInterface = $serviceInterface;
        /*$this->protocol = $protocol;
        $this->setOptions($options);
        parent::__construct($container);
        $this->normalizer = $container->get(NormalizerInterface::class);
        $this->methodDefinitionCollector = $container->get(MethodDefinitionCollectorInterface::class);*/
    }

    protected function __request(string $method, array $params, ?string $id = null)
    {
        // 获取方法注解中路径
        $path = $this->getRequestPathByMothod($method);

        // 获取注册中心服务最佳节点信息
        // if (empty($this->NacosServicesOptimal[$this->serviceName])) {
        $optimal = $this->NacosServicesOptimal[$this->serviceName] = $this->getNacosServiceInstance($this->serviceName);
        // }

        $domain = sprintf('%s:%d', $optimal->ip, $optimal->port);

        $uri = $domain . $path;

        var_dump($uri);

        $response = $this->send($optimal->ip, $optimal->port, $path,$params);

        return $response;
    }

    /**
     * 通过方法名获取注解中的路径
     * @author xiaolong
     */
    public function getRequestPathByMothod(string $method)
    {
        // 获取方法对应请求路径
        $Annotation = $this->Annotation::getClassMethodAnnotation($this->serviceInterface, $method);
        if (empty($Annotation)) {
            throw new RequestException('path does not exist ');
        }
        $pathObject = array_shift($Annotation);

        if (empty($pathObject) || empty($pathObject->path)) {
            throw new RequestException('path does not exist ');
        }

        return $pathObject->path;
    }

    public function __call(string $method, array $params)
    {
        return $this->__request($method, $params);
    }

    protected function setOptions(array $options): void
    {
        $this->serviceInterface = $options['service_interface'] ?? $this->serviceName;

        if (isset($options['load_balancer'])) {
            $this->loadBalancer = $options['load_balancer'];
        }
    }

    /**
     * 获取注册中心服务最佳节点
     * @author xiaolong
     */
    protected function getNacosServiceInstance(string $serviceName, string $groupName = '', string $namespaceId = '')
    {
        $container = ApplicationContext::getContainer();
        $nacosService = $container->get(NacosService::class);
//         $nacosService = make(NacosService::class, ['enableCache' => true]);

        $instanceInfo = $nacosService->getNacosServiceInstance($serviceName, $groupName, $namespaceId);

        if (!$instanceInfo) {

            throw new RequestException("NacosService {$serviceName} is not found!");
        }

        return $instanceInfo;
    }

    /*protected function send(string $uri, array $params)
    {
        $container = ApplicationContext::getContainer();


        $instance  = $container->get(NacosInstance::class);
        $response  = $instance->client()->request(
            'GET', $uri,
            [
                RequestOptions::QUERY => $params,
            ]
        );

        $content = $response->getBody()->getContents();

        return $content;
    }*/
    protected function send(string $host, int $port, string $uri, array $params)
    {
        try{
            $factory = $this->container->get(PoolFactory::class);

            $ssl = false;

            $poolName = md5($host.':'.$port);

            $pool = $factory->get($poolName, function () use ($host, $port, $ssl) {
                return new Client($host, $port, $ssl);
            }, [
                'max_connections' => 50
            ]);

            $connection = $pool->get();

            $client = $connection->getConnection(); // 即上述 Client.

            // $uri = '/nacos/v1/ns/instance/list?serviceName=provide_service';

            $client->execute($uri);

            $client->setMethod('GET');
            $client->setData($params);
            $result = new \GuzzleHttp\Psr7\Response(
                200,
                isset($client->headers) ? $client->headers : [],
                $client->body
            );

            $s = $result->getBody()->getContents();
        } catch (\Exception $e){
          var_dump('error ');
        } finally {
            $connection->release();
        }


        return $s;
    }
}

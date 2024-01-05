<?php

namespace Aws\Symfony\DependencyInjection;

use AppKernel;
use Aws\AwsClient;
use Aws\CodeDeploy\CodeDeployClient;
use Aws\Lambda\LambdaClient;
use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

final class AwsExtensionTest extends TestCase
{
    /**
     * @var AppKernel
     */
    protected $kernel;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setUp(): void
    {
        $this->kernel = new AppKernel('test', true);
        $this->kernel->boot();

        $this->container = $this->kernel->getContainer();
    }

    /**
     * @test
     */
    public function sdk_config_should_be_passed_directly_to_the_constructor_and_resolved_by_the_sdk()
    {
        $config = $this->kernel->getTestConfig()['aws'];
        $s3Region = isset($config['S3']['region']) ? $config['S3']['region'] : $config['region'];
        $lambdaRegion = isset($config['Lambda']['region']) ? $config['Lambda']['region'] : $config['region'];
        $codeDeployRegion = isset($config['CodeDeploy']['region']) ? $config['CodeDeploy']['region'] : $config['region'];

        $testService = $this->container->get('test_service');

        $this->assertSame($s3Region, $testService->getS3Client()->getRegion());
        $this->assertSame($lambdaRegion, $testService->getLambdaClient()->getRegion());
        $this->assertSame($codeDeployRegion, $testService->getCodeDeployClient()->getRegion());
    }

    /**
     * @test
     *
     */
    public function all_web_services_in_sdk_manifest_should_be_accessible_as_container_services() {
        $testService = $this->container->get('test_service');

        $this->assertInstanceOf(S3Client::class, $testService->getS3Client());
        $this->assertInstanceOf(LambdaClient::class, $testService->getLambdaClient());
        $this->assertInstanceOf(CodeDeployClient::class, $testService->getCodeDeployClient());

        foreach ($testService->getClients() as $client) {
            $this->assertInstanceOf(AwsClient::class, $client);
        }
    }

    /**
     * @test
     */
    public function extension_should_escape_strings_that_begin_with_at_sign()
    {
        $awsExtension = new AwsExtension;
        $config = ['credentials' => [
            'key' => '@@key',
            'secret' => '@@secret'
        ]];

        $containerBuilder = new ContainerBuilder();

        $awsExtension->load([$config], $containerBuilder);

        $awsSdkDefinition = $containerBuilder->getDefinition('aws_sdk');
        $credentialsArgument = $awsSdkDefinition->getArguments()[0]['credentials'];

        $this->assertSame([
            'key' => '@key',
            'secret' => '@secret'
        ], $credentialsArgument);

    }

    /**
     * @test
     */
    public function extension_should_expand_service_references()
    {
        $extension = new AwsExtension;
        $config = ['credentials' => '@aws_sdk'];

        $containerBuilder = new ContainerBuilder();
        $extension->load([$config], $containerBuilder);

        $awsSdkDefinition = $containerBuilder->getDefinition('aws_sdk');
        $credentialsArgument = $awsSdkDefinition->getArguments()[0]['credentials'];

        $this->assertInstanceOf(Reference::class, $credentialsArgument);

        /** @var Reference $credentialsArgument */
        $this->assertSame('aws_sdk', (string) $credentialsArgument);
    }

    /**
     * @test
     */
    public function extension_should_validate_and_merge_configs()
    {
        putenv('AWS_MERGE_CONFIG=true');
        $extension = new AwsExtension;
        $config = [
            'credentials' => false,
            'debug' => [
                'http' => true
            ],
            'stats' => [
                'http' => true
            ],
            'retries' => 5,
            'endpoint' => 'http://localhost:8000',
            'endpoint_discovery' => [
                'enabled' => true,
                'cache_limit' => 1000
            ],
            'http' => [
                'connect_timeout' => 5.5,
                'debug' => true,
                'decode_content' => true,
                'delay' => 1,
                'expect' => true,
                'proxy' => 'http://localhost:9000',
                'sink' => '/path/to/sink',
                'synchronous' => true,
                'stream' => true,
                'timeout' => 3.14,
                'verify' => '/path/to/ca_cert_bundle'
            ],
            'profile' => 'prod',
            'region' => 'us-west-2',
            'retries' => 5,
            'scheme' => 'http',
            'signature_version' => 'v4',
            'ua_append' => [
                'prod',
                'foo'
            ],
            'validate' => [
                'required' => true
            ],
            'version' => 'latest',
            'S3' => [
                'version' => '2006-03-01',
            ]
        ];
        $configDev = [
            'credentials' => '@aws_sdk',
            'debug' => true,
            'stats' => true,
            'ua_append' => 'dev',
            'validate' => true,
        ];

        $containerBuilder = new ContainerBuilder();

        $extension->load([$config, $configDev], $containerBuilder);

        $awsSdkDefinition = $containerBuilder->getDefinition('aws_sdk');
        $awsSdkConfiguration = $awsSdkDefinition->getArguments()[0];

        $this->assertSame(true, $awsSdkConfiguration['validate']);
        $this->assertSame('http://localhost:8000', $awsSdkConfiguration['endpoint']);
        $this->assertSame(5, $awsSdkConfiguration['retries']);
        $this->assertTrue($awsSdkConfiguration['stats']);
        $this->assertTrue($awsSdkConfiguration['debug']);

        $this->assertSame('2006-03-01', $awsSdkConfiguration['S3']['version']);
        $this->assertSame(1000, $awsSdkConfiguration['endpoint_discovery']['cache_limit']);
        $this->assertTrue($awsSdkConfiguration['endpoint_discovery']['enabled']);
    }

    /**
     * @test
     */
    public function extension_should_error_merging_unknown_config_options()
    {
        putenv('AWS_MERGE_CONFIG=true');
        $extension = new AwsExtension;
        $config = [
            'foo' => 'bar'
        ];
        $configDev = [
            'foo' => 'baz'
        ];

        $containerMock = $this->createMock(ContainerBuilder::class);

        try {
            $extension->load([$config, $configDev], $containerMock);
            $this->fail('Should have thrown an Error or RuntimeException');
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \RuntimeException);
        } catch (\Throwable $e) {
            $this->assertTrue($e instanceof \Error);
        }
    }
}

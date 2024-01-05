<?php

namespace Aws\Symfony\DependencyInjection;

use AppKernel;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Lambda\LambdaClient;
use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        (new Filesystem())
            ->remove(implode(DIRECTORY_SEPARATOR, [
                dirname(__DIR__),
                'fixtures',
                'cache',
                'test',
            ]));
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string $format
     */
    public function testContainerShouldCompileAndLoad($format)
    {
        $kernel = new AppKernel('test', true, $format);
        $kernel->boot();

        $testService = $kernel->getContainer()->get('test_service');

        $this->assertInstanceOf(S3Client::class, $testService->getS3Client());
        $this->assertInstanceOf(LambdaClient::class, $testService->getLambdaClient());
        $this->assertNotInstanceOf(DynamoDbClient::class, $testService->getCodeDeployClient());
    }

    public static function formatProvider(): array
    {
        return [
            ['yml'],
            ['php'],
            ['xml'],
        ];
    }
}

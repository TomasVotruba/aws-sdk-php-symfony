# AWS Service Provider for Symfony 6/7

[![Latest Stable Version](https://img.shields.io/packagist/v/tomasvotruba/aws-sdk-php-symfony.svg)](https://packagist.org/packages/tomasvotruba/aws-sdk-php-symfony)
[![Total Downloads](https://img.shields.io/packagist/dt/tomasvotruba/aws-sdk-php-symfony.svg)](https://packagist.org/packages/tomasvotruba/aws-sdk-php-symfony)

A Symfony bundle for including the [AWS SDK for PHP](https://github.com/aws/aws-sdk-php).

<br>

## Install

```bash
composer require tomasvotruba/aws-sdk-php-symfony
```

and add `Aws\Symfony\AwsBundle` to the Kernel:

```php
class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            ...
            new \Aws\Symfony\AwsBundle(),
        ];
    }
    ...
}
```

<br>

## Configuration

By default, configuration is handled by the SDK rather than by the bundle, and
no validation is performed at compile time. Full documentation of the
configuration options available can be read in the [SDK Guide](http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html).

If AWS_MERGE_CONFIG environment variable is set to `true`, configuration
validation and merging are enabled. The bundle validates and merges known
configuration options, including for each service.  Additional configuration
options can be included in a single configuration file, but merging will fail
if non-standard options are specified in more than once.

To use a service for any configuration value, use `@` followed by the service
name, such as `@a_service`. This syntax will be converted to a service during
container compilation. If you want to use a string literal that begins with `@`,
you will need to escape it by adding another `@` sign.

When using the SDK from an EC2 instance, you can write `credentials: ~` to use
[instance profile credentials](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html#instance-profile-credentials).
This syntax means that temporary credentials will be automatically retrieved
from the EC2 instance's metadata server. It's also the preferred technique for
providing credentials to applications running on that specific context.

Sample configuration can be found in the `tests/fixtures` folder for [YAML](https://github.com/tomasvotruba/aws-sdk-php-symfony/blob/master/tests/fixtures/config.yml), [PHP](https://github.com/tomasvotruba/aws-sdk-php-symfony/blob/master/tests/fixtures/config.php), and [XML](https://github.com/tomasvotruba/aws-sdk-php-symfony/blob/master/tests/fixtures/config.xml).

<br>

### Sample YML Configuration

The sample configuration which can be placed in `app/config/config.yml` file.

```yaml
framework:
    secret: "Rosebud was the name of his sled."

aws:
    version: latest
    region: us-east-1
    credentials:
        key: not-a-real-key
        secret: "@@not-a-real-secret" # this will be escaped as '@not-a-real-secret'
    DynamoDb:
        region: us-west-2
    S3:
        version: '2006-03-01'
    Sqs:
        credentials: "@a_service"
    CloudSearchDomain:
        endpoint: https://search-with-some-subdomain.us-east-1.cloudsearch.amazonaws.com

services:
    a_service:
        class: Aws\Credentials\Credentials
        arguments:
            - a-different-fake-key
            - a-different-fake-secret
```


<br>

## Usage

This bundle exposes an instance of the `Aws\Sdk` object as well as instances of
each AWS client object as services to your symfony application. They are name
`aws.{$namespace}`, where `$namespace` is the namespace of the service client.
For instance:

Service | Instance Of
--- | ---
aws.dynamodb | Aws\DynamoDb\DynamoDbClient
aws.ec2 | Aws\Ec2\Ec2Client
aws.s3 | Aws\S3\S3Client
aws_sdk | Aws\Sdk

The services made available depends on which version of the SDK is installed. To
view a full list, run the following command from your application's root
directory:

```bash
php bin/console debug:container aws
```

Full documentation on each of the services listed can be found in the [SDK API docs](http://docs.aws.amazon.com/aws-sdk-php/v3/api/).

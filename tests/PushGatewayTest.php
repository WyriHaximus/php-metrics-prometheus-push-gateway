<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\Metrics\PrometheusPushGateway;

use Http\Mock\Client;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use WyriHaximus\Metrics\Factory;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\PrometheusPushGateway\Method;
use WyriHaximus\Metrics\PrometheusPushGateway\PushGateway;

#[AllowMockObjectsWithoutExpectations]
final class PushGatewayTest extends TestCase
{
    #[Test]
    public function delete(): void
    {
        $registry = Factory::create();
        $registry->counter('c', 'family', new Name('fam'))->counter(new Label('fam', 'hash'))->incr();
        $client      = new Client();
        $pushGateway = new PushGateway(
            $registry,
            $client,
            'https://example.com/',
        );

        $pushGateway->delete('steven');

        $lastRequest = $client->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $lastRequest);

        self::assertSame('https://example.com/metrics/job/steven', (string) $lastRequest->getUri());
        self::assertSame(['text/plain'], $lastRequest->getHeader('Content-Type'));
        self::assertSame(Method::DELETE->value, $lastRequest->getMethod());
        self::assertSame('', $lastRequest->getBody()->getContents());
    }

    #[Test]
    #[DataProvider('writeProvider')]
    public function write(string $method, Method $httpMethod): void
    {
        $registry = Factory::create();
        $registry->counter('c', 'family', new Name('fam'))->counter(new Label('fam', 'hash'))->incr();
        $client      = new Client();
        $pushGateway = new PushGateway(
            $registry,
            $client,
            'https://example.com/',
        );

        /** @phpstan-ignore ergebnis.noSwitch */
        switch ($method) {
            case 'put':
                $pushGateway->put('steven');
                break;
            case 'post':
                $pushGateway->post('steven');
                break;
        }

        $lastRequest = $client->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $lastRequest);

        self::assertSame($httpMethod->value, $lastRequest->getMethod());
        self::assertSame('https://example.com/metrics/job/steven', (string) $lastRequest->getUri());
        self::assertSame(['text/plain'], $lastRequest->getHeader('Content-Type'));

        $body = $lastRequest->getBody()->getContents();
        self::assertStringContainsString('c_total family', $body);
        self::assertStringContainsString('c_total counter', $body);
        self::assertStringContainsString('c_total{fam="hash"} 1', $body);
    }

    /** @return iterable<array<string|Method>> */
    public static function writeProvider(): iterable
    {
        yield 'put' => ['put', Method::PUT];
        yield 'post' => ['post', Method::POST];
    }
}

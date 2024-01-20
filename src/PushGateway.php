<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\PrometheusPushGateway;

use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use WyriHaximus\Metrics\Printer\Prometheus;
use WyriHaximus\Metrics\Registry;

use function rtrim;

final readonly class PushGateway
{
    private string $uri;

    public function __construct(
        private Registry $registry,
        private ClientInterface $client,
        string $address,
    ) {
        $this->uri = rtrim($address, '/') . '/metrics/job/';
    }

    public function put(string $job): void
    {
        $this->write(Method::PUT, $job);
    }

    public function post(string $job): void
    {
        $this->write(Method::POST, $job);
    }

    public function delete(string $job): void
    {
        $this->write(Method::DELETE, $job);
    }

    private function write(Method $method, string $job): void
    {
        $url     = $this->uri . $job;
        $headers = ['Content-Type' => 'text/plain'];

        $this->client->sendRequest(
            new Request(
                $method->value,
                $url,
                $headers,
                $method->name === Method::DELETE->name ? '' : $this->registry->print(new Prometheus()),
            ),
        );
    }
}

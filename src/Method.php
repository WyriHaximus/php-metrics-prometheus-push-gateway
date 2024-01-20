<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\PrometheusPushGateway;

enum Method: string
{
    case PUT    = 'PUT';
    case POST   = 'POST';
    case DELETE = 'DELETE';
}

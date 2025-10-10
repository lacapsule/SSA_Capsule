<?php

declare(strict_types=1);

namespace Tests\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Capsule\Routing\Router;
use Capsule\Http\Request;
use Capsule\Http\HttpException;
use Capsule\Http\ResponseFactory;

#[CoversClass(Router::class)]
#[UsesClass(HttpException::class)]
#[UsesClass(Request::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\Capsule\Http\Response::class)]
#[UsesClass(\Capsule\Http\HeaderBag::class)]
final class RouterTest extends TestCase
{
    private static function req(string $method, string $path): Request
    {
        // ctor: method, path, query, headers, cookies, server, scheme?, host?, port?, rawBody?
        return new Request($method, $path, [], [], [], []);
    }

    public function testExactMatchReturnsHandler(): void
    {
        $router = new Router();
        $ok = fn (Request $r) => ResponseFactory::text('ok');

        $router->add('GET', '/', $ok);

        $handler = $router->match(self::req('GET', '/'));
        $this->assertIsCallable($handler);
        $resp = $handler(self::req('GET', '/'));
        $this->assertSame(200, $resp->getStatus());
        $this->assertSame('ok', $resp->getBody());
    }

    public function testHeadFallsBackToGet(): void
    {
        $router = new Router();
        $ok = fn (Request $r) => ResponseFactory::text('ok');
        $router->add('GET', '/', $ok);

        $handler = $router->match(self::req('HEAD', '/'));
        $this->assertIsCallable($handler);
    }

    public function test405WhenPathExistsButMethodNotAllowed(): void
    {
        $router = new Router();
        $ok = fn (Request $r) => ResponseFactory::text('ok');
        $router->add('POST', '/submit', $ok);

        try {
            $router->match(self::req('GET', '/submit'));
            $this->fail('Expected HttpException 405');
        } catch (HttpException $e) {
            $this->assertSame(405, $e->status);
            $this->assertArrayHasKey('Allow', $e->headers);
            // La valeur Allow est une liste jointe via ", "
            $allow = $e->headers['Allow'][0] ?? '';
            $this->assertStringContainsString('POST', $allow);
        }
    }

    public function test404WhenPathUnknown(): void
    {
        $router = new Router();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Not Found');
        try {
            $router->match(self::req('GET', '/missing'));
        } catch (HttpException $e) {
            $this->assertSame(404, $e->status);
            throw $e;
        }
    }
}

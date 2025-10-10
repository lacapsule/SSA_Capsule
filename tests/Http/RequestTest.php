<?php

declare(strict_types=1);

namespace Tests\Http;

use Capsule\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    /** @var array<string,mixed> */
    private array $backupServer = [];
    /** @var array<string,mixed> */
    private array $backupGet = [];
    /** @var array<string,mixed> */
    private array $backupCookie = [];

    protected function setUp(): void
    {
        $this->backupServer = $_SERVER;
        $this->backupGet    = $_GET;
        $this->backupCookie = $_COOKIE;

        $_SERVER = [];
        $_GET    = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->backupServer;
        $_GET    = $this->backupGet;
        $_COOKIE = $this->backupCookie;
    }

    public function testFromGlobalsBasicMethodAndPath(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PoSt';
        $_SERVER['REQUEST_URI']    = '/a//b/%20c?x=1';

        $req = Request::fromGlobals();

        $this->assertSame('POST', $req->method, 'method uppercased');
        $this->assertSame('/a/b/ c', $req->path, 'path normalized + rawurldecode + // compact');
    }

    public function testInvalidMethodFallsBackToGet(): void
    {
        // Inject a control char to fail the regex ^[A-Z]+$
        $_SERVER['REQUEST_METHOD'] = "IN\nVALID";
        $_SERVER['REQUEST_URI']    = '/';

        $req = Request::fromGlobals();

        $this->assertSame('GET', $req->method);
        $this->assertSame('/', $req->path);
    }

    public function testNullByteInPathResetsToRoot(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = "/foo\0bar";

        $req = Request::fromGlobals();

        $this->assertSame('/', $req->path);
    }

    public function testHeadersExtractionAndSanitization(): void
    {
        $_SERVER['REQUEST_METHOD']   = 'GET';
        $_SERVER['REQUEST_URI']      = '/';
        $_SERVER['HTTP_X_FOO_BAR']   = 'baz';
        $_SERVER['CONTENT_TYPE']     = "text/plain";
        // try header injection attempt in value: CRLF must be stripped
        $_SERVER['HTTP_X_EVIL']      = "evil\r\nInjected: yes";

        $req = Request::fromGlobals();

        $this->assertArrayHasKey('X-Foo-Bar', $req->headers);
        $this->assertSame('baz', $req->headers['X-Foo-Bar']);

        $this->assertArrayHasKey('Content-Type', $req->headers);
        $this->assertSame('text/plain', $req->headers['Content-Type']);

        $this->assertArrayHasKey('X-Evil', $req->headers);
        $this->assertSame('evilInjected: yes', $req->headers['X-Evil'], 'CR/LF stripped in values');
    }

    public function testSchemeHostPortResolution(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['HTTPS']          = 'on';             // anything not "off" → https
        $_SERVER['SERVER_NAME']    = 'fallback.local';
        $_SERVER['SERVER_PORT']    = '8443';
        $_SERVER['HTTP_HOST']      = 'example.org';    // should take precedence

        $req = Request::fromGlobals();

        $this->assertSame('https', $req->scheme);
        $this->assertSame('example.org', $req->host, 'Host header preferred over SERVER_NAME');
        $this->assertSame(8443, $req->port);
    }

    public function testQueryAndCookiesAreWired(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/path?foo=1&bar=b';
        $_GET    = ['foo' => '1', 'bar' => 'b'];
        $_COOKIE = ['sid' => 'abc123', 'prefs' => 'x'];

        $req = Request::fromGlobals();

        $this->assertSame(['foo' => '1', 'bar' => 'b'], $req->query);
        $this->assertSame(['sid' => 'abc123', 'prefs' => 'x'], $req->cookies);
    }
}

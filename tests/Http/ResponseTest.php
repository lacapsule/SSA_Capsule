<?php

declare(strict_types=1);

namespace Tests\Http;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Capsule\Http\Response;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testConstructRejectsInvalidStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Response(99, 'x'); // < 100 → invalide
    }

    public function testImmutabilityHeaders(): void
    {
        $r1 = new Response(200, 'a');
        $r2 = $r1->withHeader('X-Foo', 'bar');

        $this->assertNotSame($r1, $r2, 'withHeader doit retourner un clone');
        $this->assertArrayNotHasKey('X-Foo', $r1->getHeaders(), 'r1 ne doit pas être modifiée');
        $this->assertArrayHasKey('X-Foo', $r2->getHeaders(), 'r2 contient le header ajouté');
        $this->assertSame(['bar'], $r2->getHeaders()['X-Foo']);
    }

    public function testWithBodyIterableIsAccepted(): void
    {
        $iter = (function () {
            yield 'a';
            yield 'b';
        })();
        $r = (new Response(200))->withBody($iter);

        $this->assertIsIterable($r->getBody());
        $collected = '';
        foreach ($r->getBody() as $chunk) {
            $collected .= $chunk;
        }
        $this->assertSame('ab', $collected);
    }

    public function testJsonHelperSetsContentTypeAndBody(): void
    {
        $r = Response::json(['ok' => true], 201);
        $this->assertSame(201, $r->getStatus());
        $this->assertArrayHasKey('Content-Type', $r->getHeaders());
        $this->assertSame(
            ['application/json; charset=utf-8'],
            $r->getHeaders()['Content-Type']
        );

        $body = $r->getBody();
        $this->assertIsString($body);
        $this->assertSame('{"ok":true}', $body);
    }

    public function testTextHelperSetsContentType(): void
    {
        $r = Response::text('Hello');
        $this->assertArrayHasKey('Content-Type', $r->getHeaders());
        $this->assertSame(
            ['text/plain; charset=utf-8'],
            $r->getHeaders()['Content-Type']
        );
        $this->assertSame('Hello', $r->getBody());
    }
}

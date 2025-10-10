<?php

namespace Capsule\Http\Emitter;

use Capsule\Http\Message\Response;

final class SapiEmitter
{
    public function emit(Response $r): void
    {
        $status = $r->getStatus();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $noBody = ($status >= 100 && $status < 200) || $status === 204 || $status === 304 || $method === 'HEAD';

        // Défauts sûrs (si rien fourni)
        $headers = $r->getHeaders();
        if (!isset($headers['Content-Type']) && !$noBody) {
            $headers['Content-Type'] = ['text/plain; charset=utf-8'];
            $headers['X-Content-Type-Options'] = ['nosniff'];
        }

        // Pour 1xx/204/304 : éviter Content-Length / Transfer-Encoding / Content-Type
        if ($noBody) {
            unset($headers['Content-Length'], $headers['Transfer-Encoding']);
            // Content-Type n’a pas de sens sur 204/1xx ; sur 304 il est discutable, on l'omet par prudence.
            unset($headers['Content-Type']);
        }

        $this->assertSafeHeaders($headers);

        if (!headers_sent()) {
            http_response_code($status);

            $compressionOn = (bool) ini_get('zlib.output_compression');
            $hasOB = ob_get_level() > 0;
            $isIterable = is_iterable($r->getBody());
            $hasTE = isset($headers['Transfer-Encoding']);

            $canLen = !$noBody && !$compressionOn && !$hasOB && !$hasTE && !$isIterable;

            if ($canLen && !isset($headers['Content-Length'])) {
                $len = strlen((string) $r->getBody());
                $headers['Content-Length'] = [(string)$len];
            }

            foreach ($headers as $name => $values) {
                foreach ($values as $v) {
                    header("$name: $v", false);
                }
            }
        }

        if ($noBody) {
            return; // rien à émettre
        }

        $body = $r->getBody();
        if (is_iterable($body)) {
            foreach ($body as $chunk) {
                echo $chunk;
                if (function_exists('fastcgi_finish_request')) {
                    // Pas de flush agressif sous FPM, laisse FPM pousser
                } else {
                    flush();
                }
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }

            return;
        }

        echo (string)$body;
    }
    /** @param array<string,list<string>> $headers */
    private function assertSafeHeaders(array $headers): void
    {
        foreach ($headers as $name => $values) {
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\-]*$/', $name)) {
                throw new \InvalidArgumentException("Invalid header name: $name");
            }
            foreach ($values as $v) {
                if (str_contains($v, "\r") || str_contains($v, "\n")) {
                    throw new \InvalidArgumentException("Invalid header value for $name");
                }
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Capsule\View;

use Capsule\Contracts\TemplateLocatorInterface;

final class MiniMustache
{
    /** @var array<string,string> */
    private array $cache = [];

    public function __construct(private TemplateLocatorInterface $locator)
    {
    }

    /** @param array<string,mixed> $data */
    public function render(string $templatePath, array $data = []): string
    {
        $tpl = $this->load($templatePath);

        return $this->compile($tpl, $data);
    }

    /** @param array<string,mixed> $data */
    private function compile(string $tpl, array $data): string
    {
        // -------- Partials (statiques & dynamiques) --------
        // Formats acceptés :
        //   {{> prefix:path }}         (logique)
        //   {{> @var }}                (nom logique fourni dans $data['var'])
        //   {{> prefix:@var }}         (prefix imposé + nom dynamique ex: component:@component)
        $tpl = preg_replace_callback(
            '/\{\{\>\s*([a-zA-Z0-9_\/\-.:\@]+)\s*\}\}/',
            function ($m) use ($data) {
                $ref = $m[1];

                // 1) {{> @var }} : nom logique complet dans $data['var']
                if ($ref[0] === '@') {
                    $key = substr($ref, 1);
                    $dyn = $this->get($data, $key);

                    return (is_string($dyn) && $dyn !== '') ? $this->render($dyn, $data) : '';
                }

                // 2) {{> prefix:@var }} : compose 'prefix:' . <valeur>
                if (preg_match('/^([a-zA-Z0-9_]+):\@([a-zA-Z0-9_.]+)$/', $ref, $mm) === 1) {
                    $prefix = $mm[1];
                    $varKey = $mm[2];
                    $suffix = $this->get($data, $varKey);

                    return (is_string($suffix) && $suffix !== '') ? $this->render($prefix . ':' . $suffix, $data) : '';
                }

                // 3) Nom logique statique (ex: 'partial:header', 'component:homepage/apropos')
                // (Optionnel) compat legacy: components/...  → component:...
                if (!str_contains($ref, ':')) {
                    if (str_starts_with($ref, 'components/')) {
                        $ref = 'component:' . substr($ref, 11);
                    } elseif (str_starts_with($ref, 'partials/')) {
                        $ref = 'partial:' . substr($ref, 9);
                    } elseif (str_starts_with($ref, 'pages/')) {
                        $ref = 'page:' . substr($ref, 6);
                    }
                }

                return $this->render($ref, $data);
            },
            $tpl
        ) ?? $tpl;

        // -------- Sections each --------
        $tpl = preg_replace_callback(
            '/\{\{\#each\s+([a-zA-Z0-9_.]+)\s*\}\}([\s\S]*?)\{\{\/each\}\}/',
            function ($m) use ($data) {
                $arr = $this->get($data, $m[1]);
                if (!is_iterable($arr)) {
                    return '';
                }
                $chunk = '';
                foreach ($arr as $item) {
                    $chunk .= $this->compile($m[2], $this->with($data, $item));
                }

                return $chunk;
            },
            $tpl
        ) ?? $tpl;

        // -------- Sections booléennes --------
        $tpl = preg_replace_callback(
            '/\{\{\#\s*([a-zA-Z0-9_.]+)\s*\}\}([\s\S]*?)\{\{\/\s*\1\s*\}\}/',
            function ($m) use ($data) {
                $v = $this->get($data, $m[1]);
                $truthy = false;
                if (is_array($v) || $v instanceof \Countable) {
                    $truthy = (count($v) > 0);
                } else {
                    $truthy = (bool)$v;
                }

                return $truthy ? $this->compile($m[2], $data) : '';
            },
            $tpl
        ) ?? $tpl;

        // -------- Sections inverses --------
        $tpl = preg_replace_callback(
            '/\{\{\^\s*([a-zA-Z0-9_.]+)\s*\}\}([\s\S]*?)\{\{\/\s*\1\s*\}\}/',
            function ($m) use ($data) {
                $v = $this->get($data, $m[1]);
                $falsy = false;
                if (is_array($v) || $v instanceof \Countable) {
                    $falsy = (count($v) === 0);
                } else {
                    $falsy = !$v;
                }

                return $falsy ? $this->compile($m[2], $data) : '';
            },
            $tpl
        ) ?? $tpl;

        // -------- Raw HTML (triple mustache) --------
        $tpl = preg_replace_callback(
            '/\{\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}\}/',
            fn ($m) => (string)($this->get($data, $m[1]) ?? ''),
            $tpl
        ) ?? $tpl;

        // -------- Variables échappées --------
        $tpl = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function ($m) use ($data) {
                $v = $this->get($data, $m[1]);

                return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
            $tpl
        ) ?? $tpl;

        return $tpl;
    }

    private function load(string $logical): string
    {
        // Cache mémoire simple (per-request)
        if (isset($this->cache[$logical])) {
            return $this->cache[$logical];
        }

        // Résolution nom logique → fichier absolu
        $file = $this->locator->locate($logical);

        $s = file_get_contents($file);
        if ($s === false) {
            throw new \RuntimeException("Cannot read template: {$file}");
        }

        // Stocker en cache
        return $this->cache[$logical] = $s;
    }

    /**
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    private function with(array $base, mixed $ctx): array
    {
        if (is_array($ctx)) {
            return $ctx + $base;
        }

        if (is_object($ctx)) {
            return get_object_vars($ctx) + $base;
        }

        return ['.' => $ctx] + $base; // accès {{ . }}
    }

    /** @param array<string,mixed> $data */
    private function get(array $data, string $key): mixed
    {
        $parts = explode('.', $key);
        $cur = $data;
        foreach ($parts as $p) {
            if (is_array($cur) && array_key_exists($p, $cur)) {
                $cur = $cur[$p];
                continue;
            }
            if (is_object($cur) && isset($cur->$p)) {
                $cur = $cur->$p;
                continue;
            }

            return null;
        }

        return $cur;
    }
}

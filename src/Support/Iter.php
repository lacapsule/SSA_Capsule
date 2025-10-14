<?php

declare(strict_types=1);

namespace Capsule\Support;

final class Iter
{
    /** @template TIn @template TOut
     *  @param iterable<TIn> $src
     *  @param callable(TIn):TOut $fn
     *  @return iterable<TOut>
     */
    public static function map(iterable $src, callable $fn): iterable
    {
        foreach ($src as $x) {
            yield $fn($x);
        }
    }

    /** @template T
     *  @param iterable<T> $src
     *  @param callable(T):bool $pred
     *  @return iterable<T>
     */
    public static function filter(iterable $src, callable $pred): iterable
    {
        foreach ($src as $x) {
            if ($pred($x)) {
                yield $x;
            }
        }
    }

    /** @template T
     *  @param iterable<T> $src
     *  @return \Generator<array<int,T>>
     */
    public static function chunk(iterable $src, int $size): \Generator
    {
        $buf = [];
        $n = 0;
        foreach ($src as $x) {
            $buf[] = $x;
            $n++;
            if ($n === $size) {
                yield $buf;
                $buf = [];
                $n = 0;
            }
        }
        if ($n > 0) {
            yield $buf;
        }
    }


}

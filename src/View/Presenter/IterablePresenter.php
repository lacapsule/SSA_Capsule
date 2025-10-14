<?php

declare(strict_types=1);

namespace Capsule\View\Presenter;

final class IterablePresenter
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


    public static function toArray(iterable $it): array
    {
        $out = [];
        foreach ($it as $v) {
            $out[] = $v;
        }

        return $out;
    }
}

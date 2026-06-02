<?php

namespace App\Support\Search;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class SmartSearch
{
    public string $raw;
    public string $normalised;
    /** @var array<int,string> */
    public array $tokens;
    public string $digits;

    private function __construct(string $raw)
    {
        $this->raw = trim($raw);
        $this->normalised = self::normalise($this->raw);
        $this->tokens = self::tokens($this->normalised);
        $this->digits = preg_replace('/\D+/', '', $this->raw) ?? '';
    }

    public static function from(string $raw): self
    {
        return new self($raw);
    }

    public static function apply(Builder $query, string $raw, Closure $callback): Builder
    {
        $search = self::from($raw);

        if (! $search->hasSearch()) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($callback, $search) {
            $callback($inner, $search);
        });
    }

    public function hasSearch(): bool
    {
        return $this->normalised !== '' || $this->digits !== '';
    }

    public function phraseLike(): string
    {
        return self::like($this->normalised);
    }

    public function rawLike(): string
    {
        return self::like($this->raw);
    }

    public function digitsLike(): string
    {
        return self::like($this->digits);
    }

    public static function like(string $value): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($value));
        return '%' . $escaped . '%';
    }

    /**
     * Adds an OR branch where every search token must match at least one of the supplied columns.
     * This lets "Peter Gural" match first_name=Peter + last_name=Gural.
     *
     * @param array<int,string> $columns
     */
    public function orWhereAllTokensAcross(Builder $query, array $columns): Builder
    {
        if (count($this->tokens) < 2) {
            return $query;
        }

        return $query->orWhere(function (Builder $allTokens) use ($columns) {
            foreach ($this->tokens as $token) {
                $like = self::like($token);
                $allTokens->where(function (Builder $oneToken) use ($columns, $like) {
                    foreach ($columns as $column) {
                        $oneToken->orWhere($column, 'like', $like);
                    }
                });
            }
        });
    }

    /**
     * Adds an OR branch where every search token must match at least one raw expression.
     *
     * @param array<int,string> $expressions
     */
    public function orWhereAllTokensAcrossRaw(Builder $query, array $expressions): Builder
    {
        if (count($this->tokens) < 2) {
            return $query;
        }

        return $query->orWhere(function (Builder $allTokens) use ($expressions) {
            foreach ($this->tokens as $token) {
                $like = self::like($token);
                $allTokens->where(function (Builder $oneToken) use ($expressions, $like) {
                    foreach ($expressions as $expression) {
                        $oneToken->orWhereRaw($expression . ' like ?', [$like]);
                    }
                });
            }
        });
    }

    private static function normalise(string $value): string
    {
        $value = Str::of($value)->lower()->ascii()->toString();
        $value = preg_replace('/[^a-z0-9@.+\-\s]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return trim($value);
    }

    /** @return array<int,string> */
    private static function tokens(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $value), fn ($token) => strlen($token) >= 2));
    }
}

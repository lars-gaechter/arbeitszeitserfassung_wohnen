<?php

declare(strict_types=1);


final class RIOSplitString
{
    private array $string_parts;

    public function __construct(array $string_parts)
    {
        $this->string_parts = $string_parts;
    }

    public function removeEmptyParts(): RIOSplitString
    {
        return new RIOSplitString(
            array_filter($this->string_parts, function ($part) {
                return '' !== $part;
            })
        );
    }

    public function transformParts(callable $transformer): RIOSplitString
    {
        $transformed_parts = [];
        foreach ($this->string_parts as $string_part) {
            $transformed_parts[] = call_user_func($transformer, $string_part);
        }

        return new RIOSplitString($transformed_parts);
    }

    public function glueTogether(string $glue): string
    {
        return implode($glue, $this->string_parts);
    }

    public function glueAndPrefixTogether(string $glue): string
    {
        return $glue.implode($glue, $this->string_parts);
    }

    public function glueAndPostfixTogether(string $glue): string
    {
        return implode($glue, $this->string_parts).$glue;
    }

    public function glueAndPrePostfixTogether(string $glue): string
    {
        return $glue.implode($glue, $this->string_parts).$glue;
    }

    public function addParts(array $parts): RIOSplitString
    {
        return new RIOSplitString(array_merge($this->string_parts, $parts));
    }

    public function hasParts(): bool
    {
        return count($this->string_parts) !== 0;
    }

    public function getParts(): array
    {
        return array_values($this->string_parts);
    }
}

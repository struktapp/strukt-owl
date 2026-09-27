<?php
declare (strict_types = 1);
namespace Strukt\Owl\Nlp;

final class Text
{
    private const STOP = ['a', 'an', 'and', 'are', 'as', 'at', 'be', 'been', 'but', 'by', 'for', 'from', 'has', 'have', 'he', 'her', 'his', 'i', 'if', 'in', 'is', 'it', 'its', 'me', 'my', 'of', 'on', 'or', 'our', 'she', 'that', 'the', 'their', 'them', 'there', 'they', 'this', 'to', 'was', 'we', 'were', 'what', 'when', 'where', 'which', 'who', 'will', 'with', 'you', 'your', 'na', 'ni', 'ya', 'wa', 'kwa', 'katika', 'hii', 'hiyo', 'kama', 'au', 'mimi', 'sisi', 'yeye'];

    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s\'\-]+/u', ' ', $text) ?? $text;
        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    public static function words(string $text, bool $removeStop = false): array
    {
        preg_match_all('/[\p{L}\p{N}]+(?:[\'\-][\p{L}\p{N}]+)*/u', self::normalize($text), $m);
        $w = $m[0] ?? [];
        return $removeStop ? array_values(array_filter($w, fn($x) => ! in_array($x, self::STOP, true))) : $w;
    }

    public static function sentences(string $text): array
    {
        $p = preg_split('/(?<=[.!?])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_filter($p, fn($s) => self::words($s) !== []));
    }
}

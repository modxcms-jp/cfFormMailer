<?php

namespace CfFormMailer\Tests\Harness;

/**
 * 画面の HTML から、ブラウザがそのフォームを送ったときの POST を作る。
 *
 * 確認画面のように hidden で値を持ち回る画面で使う。集めるのは hidden と、
 * name の付いた送信ボタン（押したボタンとして扱う）。値の文字参照は戻す。
 */
class Browser
{
    /**
     * @param string $html      画面の HTML
     * @param int    $formIndex 何番目の <form> を送るか（0 始まり）
     */
    public static function submit(string $html, int $formIndex = 0): array
    {
        if (!preg_match_all('@<form\b[^>]*>(.*?)</form>@is', $html, $forms) || !isset($forms[1][$formIndex])) {
            throw new \InvalidArgumentException(sprintf('%d 番目の <form> がありません', $formIndex));
        }
        preg_match_all('@<input\b[^>]*>@is', $forms[1][$formIndex], $inputs);

        $pairs = [];
        $buttonPressed = false;
        foreach ($inputs[0] as $input) {
            $type = strtolower(self::attribute($input, 'type') ?? 'text');
            $name = self::attribute($input, 'name');
            if ($name === null) {
                continue;
            }
            if ($type === 'submit') {
                if ($buttonPressed) {
                    continue;
                }
                $buttonPressed = true;
            } elseif ($type !== 'hidden') {
                continue;
            }
            $pairs[] = rawurlencode($name) . '=' . rawurlencode(self::attribute($input, 'value') ?? '');
        }
        parse_str(implode('&', $pairs), $post);
        return $post;
    }

    private static function attribute(string $tag, string $name): ?string
    {
        if (!preg_match('/\s' . $name . '\s*=\s*(["\'])(.*?)\1/is', $tag, $m)) {
            return null;
        }
        return html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
    }
}

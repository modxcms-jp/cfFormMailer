<?php
/**
 * テスト用の PHx（evo()->filter）。修飾子の意味は再現せず、
 * どの値にどの修飾子で呼ばれたかがわかる文字列を返す。
 */
class FakePhx
{
    public function phxFilter($key, $value, $modifiers)
    {
        if (is_array($value)) {
            $value = 'Array(' . implode(',', $value) . ')';
        }
        return sprintf('PHX[%s|%s|%s]', $key, $value, $modifiers);
    }
}

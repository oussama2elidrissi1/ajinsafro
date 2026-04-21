<?php
$file = 'c:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\wp-plugin\ajinsafro-tour-bridge\templates\v1\single-st_tours.php';
$code = file_get_contents($file);
$tokens = token_get_all($code);
$stack = array();
$inPhp = false;
foreach ($tokens as $token) {
    if (is_array($token)) {
        $id = $token[0];
        $line = $token[2];
        if ($id === T_OPEN_TAG || $id === T_OPEN_TAG_WITH_ECHO) {
            $inPhp = true;
        } elseif ($id === T_CLOSE_TAG) {
            $inPhp = false;
        } elseif (in_array($id, array(T_IF, T_FOREACH, T_FOR, T_WHILE, T_SWITCH), true)) {
            $stack[] = array(
                'type' => token_name($id),
                'line' => $line,
                'mode' => 'waiting',
                'paren_depth' => 0,
            );
        } elseif ($id === T_ENDIF || $id === T_ENDFOREACH || $id === T_ENDFOR || $id === T_ENDWHILE || $id === T_ENDSWITCH) {
            array_pop($stack);
        }
    } else {
        if ($inPhp && !empty($stack)) {
            $topIndex = count($stack) - 1;
            if ($stack[$topIndex]['mode'] === 'waiting') {
                if ($token === '(') {
                    $stack[$topIndex]['paren_depth'] += 1;
                } elseif ($token === ')') {
                    $stack[$topIndex]['paren_depth'] -= 1;
                } elseif ($stack[$topIndex]['paren_depth'] <= 0) {
                    if ($token === ':') {
                        $stack[$topIndex]['mode'] = 'alt';
                    } elseif ($token === '{') {
                        $stack[$topIndex]['mode'] = 'brace';
                    } elseif ($token === ';') {
                        $stack[$topIndex]['mode'] = 'semicolon';
                    }
                }
            } elseif ($stack[$topIndex]['mode'] === 'brace' && $token === '}') {
                array_pop($stack);
            }
        }
    }
}
foreach ($stack as $item) {
    echo $item['type'] . ' opened at line ' . $item['line'] . ' not closed' . "\n";
}

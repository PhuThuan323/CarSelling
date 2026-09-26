<?php
require 'vendor/autoload.php';

$smarty = new Smarty();
$smarty->setTemplateDir(__DIR__ . '/../storage/_tpl_test');
$smarty->setCompileDir(__DIR__ . '/../storage/templates_c');

@mkdir(__DIR__ . '/../storage/_tpl_test');

$cases = [
    'pipe_or'    => "{if \$s eq 'a' || \$s eq 'b'}Y{else}X{/if}",
    'word_or'    => "{if \$s eq 'a' or \$s eq 'b'}Y{else}X{/if}",
    'paren_or'   => "{if (\$s eq 'a') or (\$s eq 'b')}Y{else}X{/if}",
    'in_array'   => "{if \$s|in_array:\$allowed}Y{else}X{/if}",
];

foreach ($cases as $name => $tpl) {
    $file = __DIR__ . '/../storage/_tpl_test/' . $name . '.tpl';
    file_put_contents($file, $tpl);
    $smarty->clearCompiledTemplate($name . '.tpl');
    try {
        $smarty->assign('s', 'b');
        $smarty->assign('allowed', ['a', 'b']);
        echo str_pad($name, 12), ' => ', $smarty->fetch($name . '.tpl'), "\n";
    } catch (\Throwable $e) {
        echo str_pad($name, 12), ' => FAIL: ', $e->getMessage(), "\n";
    }
}
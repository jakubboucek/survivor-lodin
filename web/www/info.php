<?php

/**
 * Protection by obscurity
 * Type the Mystery Word to show debug
 */

$mystery_word = 'romantická';

if (isset($_GET['susenka']) && $_GET['susenka'] === $mystery_word) {
    phpinfo();
} else {
    header('Content-Type: text/html; charset=utf-8', true, 403);
    echo '<pre>[SECURITY] Dej mi sušenku!</pre>' . PHP_EOL;
    echo '<form action="" method="get"><input type="text" name="susenka" autofocus></form>' . PHP_EOL;
}

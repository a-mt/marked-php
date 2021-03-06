<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

if(isset($_POST['input'])) {
    require __DIR__ . '/../marked.php';
    require __DIR__ . '/../marked_emoji.php';

    $marked = new Marked\Marked();
    $marked->setOptions([
        'gfm'       => true,
        'headerIds' => true,
        'renderer'  => new Marked\EmojiRenderer
    ]);

    $marked($_POST['input'], function($err, $data) {
        if($err) {
            throw new Exception($err);
        }
        if(isset($_POST['ajax'])) {
            echo $data;
        } else {
            require __DIR__ . '/tpl.html.php';
        }
    });
} else {
    $data = '';
    require __DIR__ . '/tpl.html.php';
}
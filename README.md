# Marked

Convert Markdown to HTML.  
PHP transpiling of [markedjs/marked@1.2.7](https://github.com/markedjs/marked)

``` php
<?php
require 'marked.php';

$marked = new Marked\Marked();
$marked->setOptions(['gfm' => true, 'headerIds' => true]);

$text = 'Hello **world**';

$marked($text, function($err, $html) {
    echo $html;
});
```
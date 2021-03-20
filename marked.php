<?php
// Based on https://cdnjs.cloudflare.com/ajax/libs/marked/1.2.7/marked.js
namespace Marked;

class Defaults {
  /**
   * @var array
   */
  public static $defaults;

  /**
   * @return array
   */
  public static function getDefaults() {
    return [
      'baseUrl' => null,
      'breaks' => false,
      'gfm' => true,
      'headerIds' => true,
      'headerPrefix' => '',
      'highlight' => null,
      'langPrefix' => 'language-',
      'mangle' => true,
      'pedantic' => false,
      'renderer' => null,
      'sanitize' => false,
      'sanitizer' => null,
      'silent' => false,
      'smartLists' => false,
      'smartypants' => false,
      'tokenizer' => null,
      'xhtml' => false
    ];
  }

  /**
   * @param array $newDefaults
   */
  public static function changeDefaults($newDefaults) {
    self::$defaults = $newDefaults;
  }

}
Defaults::$defaults = Defaults::getDefaults();

//+--------------------------------------------------------

/**
 * Allows us to call a property as a function
 * Define: $obj->replace = function() {}
 * Call: $obj->replace();
 */
class Obj {
  public function __call($name, $args) {
    if(property_exists($this, $name) && is_callable($this->$name)) {
      return call_user_func_array($this->$name, $args);
    }
    trigger_error('Call to undefined method '.__CLASS__.'::'.$name.'()', E_USER_ERROR);
  }
}

/**
 * Works like preg_replace_callback except the callback gets
 * (match, offset, str) as parameters
 *
 * @param string $pattern - Regex
 * @param callable $callback
 * @param string $subject
 * @return string
 */
function preg_replace_callback_offset($pattern, $callback, $subject) {
  $offset = 0;

  while(preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, $offset)) {
    list($match, $pos) = $matches[0];

    $replace = $callback($match, $pos, $subject);
    $subject = substr_replace($subject, $replace, $pos, strlen($match));

    $offset  = $pos + strlen($replace);
  }
  return $subject;
}

/**
 * @param string $pattern
 * @param string $subject
 * @return array | false
 */
function preg_match_get($pattern, $subject) {
  if(preg_match($pattern, $subject, $matches)) {
    return $matches;
  } else {
    return false;
  }
}

/**
 * @param string $pattern
 * @param string $subject
 * @return array
 */
function preg_match_all_get($pattern, $subject) {
  if(preg_match_all($pattern, $subject, $matches)) {
    return $matches[0];
  } else {
    return[];
  }
}

/**
 * Returns the first non falsy value
 * @param ...mixed
 * @return mixed | null
 */
function or_get() {
  for($i = 0; $i < func_num_args(); $i++) {
    $val = func_get_arg($i);
    if($val) {
      return $val;
    }
  }
}

/**
 * Returns a random float between 0 and 1
 * @return float
 */
function random_0_1() {
  return (float)rand() / (float)getrandmax();
}

/**
 * URL-encodes a string but doesn't encode
 * characters that have special meaning (reserved characters) for a URI
 * @param string $uri
 * @return string
 */
function encodeURI($uri) {
  return preg_replace_callback("{[^0-9a-z_.!~*'();,/?:@&=+$#-]}i", function ($m) {
    return sprintf('%%%02X', ord($m[0]));
  }, $uri);
}

//+--------------------------------------------------------

/**
 * Helpers
 */
class Helpers {

  static $escapeReplace = "/[&<>\"']/";
  static $escapeReplaceNoEncode = "/[<>\"']|&(?!#?\w+;)/";
  static $escapeReplacements = [
    '&' => '&amp;',
    '<' => '&lt;',
    '>' => '&gt;',
    '"' => '&quot;',
    "'" => '&#39;'
  ];

  public static function getEscapeReplacement($m) {
    $ch = $m[0];
    return self::$escapeReplacements[$ch];
  }

  /**
   * @param string $html
   * @param boolean $encode
   * @return string
   */
  public static function escape($html, $encode = false) {
    if ($encode) {
      if (preg_match(self::$escapeReplace, $html)) {
        return preg_replace_callback(self::$escapeReplace, "self::getEscapeReplacement", $html);
      }
    } else {
      if (preg_match(self::$escapeReplaceNoEncode, $html)) {
        return preg_replace_callback(self::$escapeReplaceNoEncode, "self::getEscapeReplacement", $html);
      }
    }

    return $html;
  }

  static $unescape = "/&(#(?:\d+)|(?:#x[0-9A-Fa-f]+)|(?:\w+));?/i";

  /**
   * @param string $html
   * @return string
   */
  public static function unescape($html) {
    // explicitly match decimal, hex, and named HTML entities
    return preg_replace_callback(self::$unescape, function ($m) {
      $n = strtolower($m[1]);

      if ($n === 'colon') return ':';

      if ($n[0] === '#') {
        return $n[1] === 'x' ? chr(intval(substr($n, 2), 16)) : chr(+substr($n, 1));
      }

      return '';
    }, $html);
  }

  static $caret = "/(^|[^\[])\^/";

  /**
   * @param string $regex
   * @param string[optional] $opt - Options to add to the regex
   * @return object
   */
  public static function edit($regex, $opt = '') {
      $obj = new Obj;
      $obj->replace = function($name, $val) use(&$regex, &$obj) {
          $val   = preg_replace(self::$caret, '$1', $val);
          $regex = str_replace($name, trim($val, '/'), $regex);
          return $obj;
      };
      $obj->getRegex = function() use(&$regex, &$opt) {
         return $regex . $opt;
      };
      return $obj;
  }

  static $nonWordAndColon = "/[^\w:]/";
  static $originIndependentUrl = "/^$|^[a-z][a-z0-9+.-]*:|^[?#]/i";

  /**
   * @param boolean $sanitize
   * @param string $base
   * @param string $href
   * @return string | null
   */
  public static function cleanUrl($sanitize, $base, $href) {
    if ($sanitize) {
      try {
        $url = urldecode(self::unescape($href));
        $url = preg_replace(self::$nonWordAndColon, '', $url);
        $url = strtolower($url);
      } catch (Exception $e) {
        return null;
      }

      $prot = explode(':', $url, 2);
      if($prot[0] == 'javascript' || $prot[0] == 'vbscript' || $prot[0] == 'data') {
        return null;
      }
    }

    if ($base && !preg_match(self::$originIndependentUrl, $href)) {
      $href = self::resolveUrl($base, $href);
    }

    try {
      $href = encodeURI($href);
      $href = str_replace("%25", '%', $href);
    } catch (Exception $e) {
      return null;
    }
    return $href;
  }

  static $baseUrls = [];
  static $justDomain = "/^[^:]+:\/*[^/]*$/";
  static $protocol = "/^([^:]+:)[\s\S]*$/";
  static $domain = "/^([^:]+:\/*[^/]*)[\s\S]*$/";

  /**
   * @param string $base
   * @param string $href
   * @return string
   */
  public static function resolveUrl($base, $href) {
    if (!isset(self::$baseUrls[' ' . $base])) {
      // we can ignore everything in base after the last slash of its path component,
      // but we might need to add _that_
      // https://tools.ietf.org/html/rfc3986#section-3
      if (preg_match(self::$justDomain, $base)) {
        self::$baseUrls[' ' . $base] = $base . '/';
      } else {
        $k = strrpos($base, '/');
        self::$baseUrls[' ' . $base] = $k ? substr($base, 0, $k+1) : $base;
      }
    }

    $base = self::$baseUrls[' ' . $base];
    $relativeBase = strpos($base, ':') === false;

    if (substr($href, 0, 2) === '//') {
      return $relativeBase ? $href : preg_replace(self::$protocol, '$1', $base) . $href;

    } else if ($href[0] === '/') {
      return $relativeBase ? $href : preg_replace(self::$domain, '$1', $base) . $href;

    } else {
      return $base . $href;
    }
  }

  static $noopTest = "/^\x1a/";

  /**
   * @param string $tableRow
   * @param integer $count - Cut or expand the list of cells to the given length
   * @return array
   */
  public static function splitCells($tableRow, $count = 0) {
    // ensure that every cell-delimiting pipe has a space
    // before it to distinguish it from an escaped pipe
    $row = preg_replace_callback_offset("/\|/", function($match, $offset, $str) {
      
      $escaped = false;
      while (--$offset >= 0 && $str[$offset] === '\\') {
        $escaped = !$escaped;
      }

      if ($escaped) {
        // odd number of slashes means | is escaped
        // so we leave it alone
        return '|';
      } else {
        // add space before unescaped |
        return ' |';
      }
    }, $tableRow);

    $cells = explode(" |", $row);

    if($count) {
      if (sizeof($cells) > $count) {
        $cells = array_slice($cells, 0, $count);
      } else {
        while (sizeof($cells) < $count) {
          $cells[] = '';
        }
      }
    }

    for ($i = 0; $i < sizeof($cells); $i++) {
      // leading or trailing whitespace is ignored per the gfm spec
      $cells[$i] = str_replace("\|", "|", trim($cells[$i]));
    }

    return $cells;
  }

  /**
   * @param string $str
   * @param string|array $b
   * @return integer - Returns -1 if the closing bracket isn't found
   */
  public static function findClosingBracket($str, $b) {
    $openingBracket = $b[0];
    $closingBracket = $b[1];

    if (strpos($str, $closingBracket) === false) {
      return -1;
    }

    $level = 0;

    for ($i = 0; $i < strlen($str); $i++) {
      if ($str[$i] === '\\') {
        $i++;
      } else if ($str[$i] === $openingBracket) {
        $level++;
      } else if ($str[$i] === $closingBracket) {
        $level--;

        if ($level < 0) {
          return $i;
        }
      }
    }

    return -1;
  }

  /**
   * @param array $opt
   */
  public static function checkSanitizeDeprecation($opt) {
    if ($opt && @$opt['sanitize'] && !@$opt['silent']) {
      trigger_error('marked(): sanitize and sanitizer parameters are deprecated since version 0.7.0, should not be used and will be removed in the future. Read more here: https://marked.js.org/#/USING_ADVANCED.md#options', E_USER_WARNING);
    }
  }

  /**
   * @param array $cap
   * @param array $link
   * @param string $raw
   * @return array
   */
  public static function outputLink($cap, $link, $raw) {
    $href  = $link['href'];
    $title = $link['title'] ? Helpers::escape($link['title']) : null;
    $text  = preg_replace("/\\\\([\\[\\]])/", "$1", $cap[1]);

    if ($cap[0][0] !== '!') {
      return [
        'type' => 'link',
        'raw' => $raw,
        'href' => $href,
        'title' => $title,
        'text' => $text
      ];
    } else {
      return [
        'type' => 'image',
        'raw' => $raw,
        'href' => $href,
        'title' => $title,
        'text' => Helpers::escape($text)
      ];
    }
  }
}

function indentCodeCompensation($raw, $text) {
  if(!preg_match('/^(\s+)(?:```)/', $raw, $matchIndentToCode)) {
    return $text;
  }

  $indentToCode = $matchIndentToCode[1];
  $indentToCodeLen = strlen($indentToCode);

  return join("\n", array_map(function($node) use($indentToCodeLen){
    if(!preg_match('/^\s+/', $node, $matchIndentInNode)) {
      return $node;
    }
    $indentInNode = $matchIndentInNode[0];

    if(strlen($indentInNode) >= $indentToCodeLen) {
      return substr($node, $indentToCodeLen);
    }
    return $node;

  }, explode("\n", $text)));
}

//+--------------------------------------------------------

/**
 * Tokenizer
 */
class Tokenizer {
  
  /**
   * @var array
   */
  public $options, $rules;

  /**
   * @param array[optional] $options
   */
  public function __construct($options = []) {
    $this->options = $options ? $options : Defaults::$defaults;
  }
  
  /**
   * @param string $src
   * @return array | null
   */
  public function space($src) {
    $cap = preg_match_get($this->rules['block']['newline'], $src);

    if ($cap) {
      if (strlen($cap[0]) > 1) {
        return [
          'type' => 'space',
          'raw'  => $cap[0]
        ];
      }
      return [
        'raw' => "\n"
      ];
    }
  }

  /**
   * @param string $src
   * @param array $tokens
   * @return array | null
   */
  public function code($src, $tokens) {
    $cap = preg_match_get($this->rules['block']['code'], $src);

    if ($cap) {
      // An indented code block cannot interrupt a paragraph.
      if ($tokens && $tokens[sizeof($tokens) - 1]['type'] === 'paragraph') {
        return [
          'type' => '',
          'raw'  => $cap[0],
          'text' => rtrim($cap[0])
        ];
      } else {
        $text = preg_replace('/^ {4}/m', '', $cap[0]);
        return [
          'type' => 'code',
          'raw' => $cap[0],
          'codeBlockStyle' => 'indented',
          'text' => !$this->options['pedantic'] ? rtrim($text, "\n") : $text
        ];
      }
    }
  }
  
  /**
   * @param string $src
   * @return array | null
   */
  public function fences($src) {
    $cap = preg_match_get($this->rules['block']['fences'], $src);

    if ($cap) {
      $raw  = $cap[0];
      $text = indentCodeCompensation($raw, $cap[3] ? $cap[3] : '');

      return [
        'type' => 'code',
        'raw'  => $raw,
        'lang' => $cap[2] ? trim($cap[2]) : $cap[2],
        'text' => $text
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function heading($src) {
    $cap = preg_match_get($this->rules['block']['heading'], $src);

    if ($cap) {
      $text = trim($cap[2]);

      // remove trailing #s
      if(preg_match('/#$/', $text)) {
        $trimmed = rtrim($text, '#');

        // CommonMark requires space before trailing #s
        if($this->options['pedantic'] || !$trimmed || preg_match('/ $/', $trimmed)) {
          $text = trim($trimmed);
        }
      }

      return [
        'type' => 'heading',
        'raw' => $cap[0],
        'depth' => strlen($cap[1]),
        'text' => $text
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function nptable($src) {
    $cap = preg_match_get($this->rules['block']['nptable'], $src);

    if ($cap) {
      $item = [
        'type' => 'table',
        'header' => Helpers::splitCells(preg_replace('/^ *| *\| *$/', '', $cap[1])),
        'align' => preg_split('/ *\| */', preg_replace('/^ *|\| *$/', '', $cap[2])),
        'cells' => @$cap[3] ? explode("\n", preg_replace('/\n$/', '', $cap[3])) : [],
        'raw' => $cap[0]
      ];

      if (sizeof($item['header']) === sizeof($item['align'])) {
        $l = strlen($item['align']);

        for ($i = 0; $i < $l; $i++) {
          if (preg_match('/^ *-+: *$/', $item['align'][$i])) {
            $item['align'][$i] = 'right';

          } else if (preg_match('/^ *:-+: *$/', $item['align'][$i])) {
            $item['align'][$i] = 'center';

          } else if (preg_match('/^ *:-+ *$/', $item['align'][$i])) {
            $item['align'][$i] = 'left';

          } else {
            $item['align'][$i] = null;
          }
        }

        $l = sizeof($item['cells']);
        $count = sizeof($item['header']);

        for ($i = 0; $i < $l; $i++) {
          $item['cells'][$i] = Helpers::splitCells($item['cells'][$i], $count);
        }

        return $item;
      }
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function hr($src) {
    $cap = preg_match_get($this->rules['block']['hr'], $src);

    if ($cap) {
      return [
        'type' => 'hr',
        'raw' => $cap[0]
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function blockquote($src) {
    $cap = preg_match_get($this->rules['block']['blockquote'], $src);

    if ($cap) {
      $text = preg_replace('/^ *> ?/m', '', $cap[0]);
      return [
        'type' => 'blockquote',
        'raw' => $cap[0],
        'text' => $text
      ];
    }
  }
  
  /**
   * @param string $src
   * @return array | null
   */
  public function list($src) {
    $cap = preg_match_get($this->rules['block']['list'], $src);

    if ($cap) {
      $raw = $cap[0];
      $bull = $cap[2];
      $isordered = strlen($bull) > 1;

      $list = [
        'type' => 'list',
        'raw' => $raw,
        'ordered' => $isordered,
        'start' => $isordered ? intval(substr($bull, 0, -1)) : '',
        'loose' => false,
        'items' => []
      ]; // Get each top-level item.

      $itemMatch = preg_match_all_get($this->rules['block']['item'], $cap[0]);
      $bcurr     = preg_match_get($this->rules['block']['listItemStart'], $itemMatch[0]);

      $l = sizeof($itemMatch);

      for ($i = 0; $i < $l; $i++) {
        $item = $itemMatch[$i];
        $raw = $item;

        // Determine whether the next list item belongs here.
        // Backpedal if it does not belong in this list.
        if ($i !== $l - 1) {
          $bnext = preg_match_get($this->rules['block']['listItemStart'], $itemMatch[$i + 1]);

          // nested list
          if(strlen($bnext[1]) > strlen($bcurr[0]) || strlen($bnext[1]) > 3) {
            array_splice($itemMatch, $i, 2, array($itemMatch[$i] . "\n" . $itemMatch[$i + 1]));
            $i--;
            $l--;
            continue;

          // different bullet style
          } else if(!$this->options['pedantic'] || $this->options['smartLists']
            ? $bnext[2][strlen($bnext[2]) - 1] != $bull[strlen($bull) - 1]
            : $isordered ===  (strlen($bnext[2]) === 1)
          ) {
            $addBack     = implode("\n", array_slice($itemMatch, $i + 1));
            $list['raw'] = substr($list['raw'], 0, strlen($list['raw']) - strlen($addBack));
            $i = $l - 1;
          }

          $bcurr = $bnext;
        }

        // Remove the list item's bullet
        // so it is seen as the next token.
        $space = strlen($item);
        $item = preg_replace('/^ *([*+-]|\d+[.)]) ?/', '', $item);

        // Outdent whatever the
        // list item contains. Hacky.
        if (strpos($item, "\n ") !== false) {
          $space -= strlen($item);
          $item = !$this->options['pedantic'] ? preg_replace('/^ {1,' . $space . '}/m', '', $item) : preg_replace('/^ {1,4}/m', '', $item);
        }

        // Determine whether item is loose or not.
        // Use: /(^|\n)(?! )[^\n]+\n\n(?!\s*$)/
        // for discount behavior.
        $loose = preg_match('/\n\n(?!\s*$)/', $item);

        if (!$loose && $i !== $l - 1) {
          $loose = $item[strlen($item) - 1] === "\n";
        }
        if ($loose) {
          $list['loose'] = true;
        }

        // Check for task list items
        if($this->options['gfm']) {
          $istask = preg_match('/^\[[ xX]\] /', $item);
          $ischecked = false;

          if ($istask) {
            $ischecked = $item[1] !== ' ';
            $item = preg_replace('/^\[[ xX]\] +/', '', $item);
          }
        }

        $list['items'][] = [
          'type' => 'list_item',
          'raw' => $raw,
          'task' => $istask,
          'checked' => $ischecked,
          'loose' => $loose,
          'text' => $item
        ];
      }

      return $list;
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function html($src) {
    $cap = preg_match_get($this->rules['block']['html'], $src);

    if ($cap) {
      return [
        'type' => $this->options['sanitize'] ? 'paragraph' : 'html',
        'raw' => $cap[0],
        'pre' => !$this->options['sanitizer'] && isset($cap[1]) && ($cap[1] === 'pre' || $cap[1] === 'script' || $cap[1] === 'style'),
        'text' => $this->options['sanitize'] ? $this->options['sanitizer'] ? $this->options['sanitizer']($cap[0]) : Helpers::escape($cap[0]) : $cap[0]
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function def($src) {
    $cap = preg_match_get($this->rules['block']['def'], $src);

    if ($cap) {
      if (isset($cap[3])) {
        $cap[3] = substr($cap[3], 1, strlen($cap[3]) - 1);
      } else {
        $cap[3] = '';
      }
      $tag = preg_replace('/\s+/', ' ', strtolower($cap[1]));
      return [
        'tag' => $tag,
        'raw' => $cap[0],
        'href' => $cap[2],
        'title' => $cap[3]
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function table($src) {
    $cap = preg_match_get($this->rules['block']['table'], $src);

    if ($cap) {
      $item = [
        'type' => 'table',
        'header' => Helpers::splitCells(preg_replace('/^ *| *\| *$/', '', $cap[1])),
        'align' => preg_split('/ *\| */', preg_replace('/^ *|\| *$/', '', $cap[2])),
        'cells' => $cap[3] ? explode("\n", preg_replace('/\n$/', '', $cap[3])) : []
      ];

      if (sizeof($item['header']) === sizeof($item['align'])) {
        $item['raw'] = $cap[0];
        $l = sizeof($item['align']);

        for ($i = 0; $i < $l; $i++) {
          if (preg_match('/^ *-+: *$/', $item['align'][$i])) {
            $item['align'][$i] = 'right';

          } else if (preg_match('/^ *:-+: *$/', $item['align'][$i])) {
            $item['align'][$i] = 'center';

          } else if (preg_match('/^ *:-+ *$/', $item['align'][$i])) {
            $item['align'][$i] = 'left';

          } else {
            $item['align'][$i] = null;
          }
        }

        $l = sizeof($item['cells']);
        $count = sizeof($item['header']);

        for ($i = 0; $i < $l; $i++) {
          $item['cells'][$i] = Helpers::splitCells(preg_replace('/^ *\| *| *\| *$/', '', $item['cells'][$i]), $count);
        }

        return $item;
      }
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function lheading($src) {
    $cap = preg_match_get($this->rules['block']['lheading'], $src);
  
    if ($cap) {
      return [
        'type' => 'heading',
        'raw' => $cap[0],
        'depth' => $cap[2][0] === '=' ? 1 : 2,
        'text' => $cap[1]
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function paragraph($src) {
   $cap = preg_match_get($this->rules['block']['paragraph'], $src);

    if ($cap) {
      return [
        'type' => 'paragraph',
        'raw' => $cap[0],
        'text' => $cap[1][strlen($cap[1]) - 1] === "\n" ? substr($cap[1], 0, -1) : $cap[1]
      ];
    }
  }

  /**
   * @param string $src
   * @param array $tokens
   * @return array | null
   */
  public function text($src, $tokens) {
    $cap = preg_match_get($this->rules['block']['text'], $src);

    if ($cap) {
      if ($tokens && $tokens[sizeof($tokens) - 1]['type'] === 'text') {
        return [
          'type' => '',
          'raw'  => $cap[0],
          'text' => $cap[0]
        ];
      }
      return [
        'type' => 'text',
        'raw' => $cap[0],
        'text' => $cap[0]
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function escape($src) {
    $cap = preg_match_get($this->rules['inline']['escape'], $src);

    if ($cap) {
      return [
        'type' => 'escape',
        'raw' => $cap[0],
        'text' => Helpers::escape($cap[1])
      ];
    }
  }

  /**
   * @param string $src
   * @param boolean inLink
   * @param boolean inRawBlock
   * @return array | null
   */
  public function tag($src, $inLink, $inRawBlock) {
    $cap = preg_match_get($this->rules['inline']['tag'], $src);

    if ($cap) {
      if (!$inLink && preg_match('/^<a /i', $cap[0])) {
        $inLink = true;
      } else if ($inLink && preg_match('/^<\/a>/i', $cap[0])) {
        $inLink = false;
      }

      if (!$inRawBlock && preg_match('/^<(pre|code|kbd|script)(\s|>)/i', $cap[0])) {
        $inRawBlock = true;
      } else if ($inRawBlock && preg_match('/^<\/(pre|code|kbd|script)(\s|>)/i', $cap[0])) {
        $inRawBlock = false;
      }

      return [
        'type' => $this->options['sanitize'] ? 'text' : 'html',
        'raw' => $cap[0],
        'inLink' => $inLink,
        'inRawBlock' => $inRawBlock,
        'text' => $this->options['sanitize'] ? $this->options['sanitizer'] ? $this->options['sanitizer']($cap[0]) : Helpers::escape($cap[0]) : $cap[0]
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function link($src) {
    $cap = preg_match_get($this->rules['inline']['link'], $src);

    if ($cap) {
      $trimmedUrl = trim($cap[2]);

      // commonmark requires matching angle brackets
      if(!$this->options['pedantic'] && preg_match('/^</', $trimmedUrl)) {

        if (!preg_match('/>$/', $trimmedUrl)) {
          return;
        }

        // ending angle bracket cannot be escaped
        $rtrimSlash = rtrim(substr($trimmedUrl, 0, -1), '\\');

        if(strlen($trimmedUrl) - strlen($rtrimSlash) % 2 === 0) {
          return;
        }

      } else {
        // find closing parenthesis
        $lastParenIndex = Helpers::findClosingBracket($cap[2], '()');

        if ($lastParenIndex > -1) {
          $start = strpos($cap[0], '!') === 0 ? 5 : 4;
          $linkLen = $start + strlen($cap[1]) + $lastParenIndex;
          $cap[2] = substr($cap[2], 0, $lastParenIndex);
          $cap[0] = trim(substr($cap[0], 0, $linkLen));
          $cap[3] = '';
        }
      }

      $href = $cap[2];
      $title = '';

      // split pedantic href and title
      if ($this->options['pedantic']) {
        $link = preg_match_get('/^([^\'"]*[^\s])\s+([\'"])(.*)\2/', $href);

        if ($link) {
          $href = $link[1];
          $title = $link[3];
        }
      } else {
        $title = isset($cap[3]) ? substr($cap[3], 1, -1) : '';
      }

      $href = trim($href);

      // pedantic allows starting angle bracket without ending angle bracket
      if (preg_match('/^</', $href)) {
        if ($this->options['pedantic'] && !preg_match('/>$/', $trimmedUrl)) {
          $href = substr($href, 1);
        } else {
          $href = substr($href, 1, -1);
        }
      }

      return Helpers::outputLink($cap, [
        'href' => $href ? preg_replace($this->rules['inline']['_escapes'], '$1', $href) : $href,
        'title' => $title ? preg_replace($this->rules['inline']['_escapes'], '$1', $title) : $title
      ], $cap[0]);
    }
  }

  /**
   * @param string $src
   * @param array $links
   * @return array | null
   */
  public function reflink($src, $links) {
    if (($cap = preg_match_get($this->rules['inline']['reflink'], $src))
      || ($cap = preg_match_get($this->rules['inline']['nolink'], $src))) {

      $link = preg_replace('/\s+/', ' ', @$cap[2] ? $cap[2] : $cap[1]);
      $link = @$links[strtolower($link)];

      if (!$link || !$link['href']) {
        $text = $cap[0][0];
        return [
          'type' => 'text',
          'raw' => $text,
          'text' => $text
        ];
      }

      return Helpers::outputLink($cap, $link, $cap[0]);
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function strong($src, $maskedSrc='', $prevChar='') {
    $match = preg_match_get($this->rules['inline']['strong']['start'], $src);

    if ($match && (!@$match[1] || $prevChar === '' || preg_match($this->rules['inline']['punctuation'], $prevChar))) {
      $maskedSrc = substr($maskedSrc, 0-strlen($src));

      $endReg    = $this->rules['inline']['strong'][$match[0] === '**' ? 'endAst' : 'endUnd'];
      $lastIndex = 0;

      while(preg_match($endReg, $maskedSrc, $matches, PREG_OFFSET_CAPTURE, $lastIndex)) {
        list($match, $index) = $matches[0];

        $lastIndex = $index + strlen($match);

        $cap = preg_match_get($this->rules['inline']['strong']['middle'], substr($maskedSrc, 0, $index+3));

        if($cap) {
          return [
            'type' => 'strong',
            'raw'  => substr($src, 0, strlen($cap[0])),
            'text' => substr($src, 2, strlen($cap[0])-4)
          ];
        }
      }
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function em($src, $maskedSrc='', $prevChar='') {
    $match = preg_match_get($this->rules['inline']['em']['start'], $src);

    if($match && (!@$match[1] || ($prevChar === '' || preg_match($this->rules['inline']['punctuation'], $prevChar)))) {
      $maskedSrc = substr($maskedSrc, 0-strlen($src));

      $endReg    = $this->rules['inline']['em'][$match[0] === '*' ? 'endAst' : 'endUnd'];
      $lastIndex = 0;

      while(preg_match($endReg, $maskedSrc, $matches, PREG_OFFSET_CAPTURE, $lastIndex)) {
        list($match, $index) = $matches[0];

        $lastIndex = $index + strlen($match);

        $cap = preg_match_get($this->rules['inline']['em']['middle'], substr($maskedSrc, 0, $index+2));

        if($cap) {
          return [
            'type' => 'em',
            'raw'  => substr($src, 0, strlen($cap[0])),
            'text' => substr($src, 1, strlen($cap[0])-2)
          ];
        }
      }
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function codespan($src) {
    $cap = preg_match_get($this->rules['inline']['code'], $src);

    if ($cap) {
      $text = str_replace("\n", ' ', $cap[2]);

      $hasNonSpaceChars = preg_match('/[^ ]/', $text);
      $hasSpaceCharsOnBothEnds = preg_match('/^ /', $text) && preg_match('/ $/', $text);

      if ($hasNonSpaceChars && $hasSpaceCharsOnBothEnds) {
        $text = substr($text, 1, strlen($text) - 1);
      }

      return [
        'type' => 'codespan',
        'raw' => $cap[0],
        'text' => Helpers::escape($text, true)
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function br($src) {
    $cap = preg_match_get($this->rules['inline']['br'], $src);

    if ($cap) {
      return [
        'type' => 'br',
        'raw' => $cap[0]
      ];
    }
  }

  /**
   * @param string $src
   * @return array | null
   */
  public function del($src) {
    $cap = preg_match_get($this->rules['inline']['del'], $src);

    if ($cap) {
      return [
        'type' => 'del',
        'raw' => $cap[0],
        'text' => $cap[2]
      ];
    }
  }

  /**
   * @param string $src
   * @param callable $mangle
   * @return array | null
   */
  public function autolink($src, $mangle) {
    $cap = preg_match_get($this->rules['inline']['autolink'], $src);

    if ($cap) {
      if ($cap[2] === '@') {
        $text = Helpers::escape($this->options['mangle'] ? $mangle($cap[1]) : $cap[1]);
        $href = 'mailto:' . $text;
      } else {
        $text = Helpers::escape($cap[1]);
        $href = $text;
      }

      return [
        'type' => 'link',
        'raw' => $cap[0],
        'text' => $text,
        'href' => $href,
        'tokens' => [[
          'type' => 'text',
          'raw' => $text,
          'text' => $text
        ]]
      ];
    }
  }

  /**
   * @param string $src
   * @param callable $mangle
   * @return array | null
   */
  public function url($src, $mangle) {
    $cap = preg_match_get($this->rules['inline']['url'], $src);

    if ($cap) {
      if (@$cap[2] === '@') {
        $text = Helpers::escape($this->options['mangle'] ? $mangle($cap[0]) : $cap[0]);
        $href = 'mailto:' . $text;

      } else {
        // do extended autolink path validation
        do {
          $prevCapZero = $cap[0];
          $cap[0] = preg_match_get($this->rules['inline']['_backpedal'], $cap[0])[0];
        } while ($prevCapZero !== $cap[0]);

        $text = Helpers::escape($cap[0]);
        $href = ($cap[1] === 'www.' ? 'http://' . $text : $text);
      }

      return [
        'type' => 'link',
        'raw' => $cap[0],
        'text' => $text,
        'href' => $href,
        'tokens' => [[
          'type' => 'text',
          'raw' => $text,
          'text' => $text
        ]]
      ];
    }
  }

  /**
   * @param string $src
   * @param boolean $inRawBlock
   * @param callable $smartypants
   * @return array | null
   */
  public function inlineText($src, $inRawBlock, $smartypants) {
    $cap = preg_match_get($this->rules['inline']['text'], $src);

    if ($cap) {
      if ($inRawBlock) {
        $text = $this->options['sanitize'] ? $this->options['sanitizer'] ? $this->options['sanitizer']($cap[0]) : Helpers::escape($cap[0]) : $cap[0];
      } else {
        $text = Helpers::escape($this->options['smartypants'] ? $smartypants($cap[0]) : $cap[0]);
      }

      return [
        'type' => 'text',
        'raw' => $cap[0],
        'text' => $text
      ];
    }
  }
}

//+--------------------------------------------------------

class Rules {
  
  /**
   * @var array
   */
   public static $block, $inline;

  public static function init() {

    /**
     * Block-Level Grammar
     */
    $block = [];
    $block['newline']    = "/^\\n+/";
    $block['code']       = "/^( {4}[^\\n]+\\n*)+/";
    $block['fences']     = "/^ {0,3}(`{3,}(?=[^`\\n]*\\n)|~{3,})([^\\n]*)\\n(?:|([\\s\\S]*?)\\n)(?: {0,3}\\1[~`]* *(?:\\n+|$)|$)/";
    $block['hr']         = "/^ {0,3}((?:- *){3,}|(?:_ *){3,}|(?:\\* *){3,})(?:\\n+|$)/";
    $block['heading']    = "/^ {0,3}(#{1,6})(?=\s|$)(.*)(?:\\n+|$)/";
    $block['lheading']   = "/^([^\\n]+)\\n {0,3}(=+|-+) *(?:\\n+|$)/";
    $block['blockquote'] = "/^( {0,3}> ?(paragraph|[^\\n]*)(?:\\n|$))+/";
    $block['def']        = "/^ {0,3}\\[(label)\\]: *\\n? *<?([^\\s>]+)>?(?:(?: +\\n? *| *\\n *)(title))? *(?:\\n+|$)/";
    
    $block['list']       = "/^( {0,3})(bull) [\\s\\S]+?(?:hr|def|\\n{2,}(?! )(?! {0,3}bull )\\n*|\\s*$)/";
    $block['bullet']     = "/(?:[*+-]|\\d{1,9}[.)])/";
    $block['item']       = "/^( *)(bull) ?[^\\n]*(?:\\n(?! *bull ?)[^\\n]*)*/";

    $block['html']       = "/^ {0,3}(?:" // optional indentation
                            . "<(script|pre|style)[\\s>][\\s\\S]*?(?:<\\/\\1>[^\\n]*\\n+|$)" // (1)
                            . "|comment[^\\n]*(\\n+|$)" // (2)
                            . "|<\\?[\\s\\S]*?(?:\\?>\\n*|$)" // (3)
                            . "|<![A-Z][\\s\\S]*?(?:>\\n*|$)" // (4)
                            . "|<!\\[CDATA\\[[\\s\\S]*?(?:\\]\\]>\\n*|$)" // (5)
                            . "|<\\/?(tag)(?: +|\\n|\\/?>)[\\s\\S]*?(?:\\n{2,}|$)" // (6)
                            . "|<(?!script|pre|style)([a-z][\\w-]*)(?:attribute)*? *\\/?>(?=[ \\t]*(?:\\n|$))[\\s\\S]*?(?:\\n{2,}|$)" // (7) open tag
                            . "|<\\/(?!script|pre|style)[a-z][\\w-]*\\s*>(?=[ \\t]*(?:\\n|$))[\\s\\S]*?(?:\\n{2,}|$)" // (7) closing tag
                            . ")/";

    $block['nptable']    = Helpers::$noopTest;
    $block['nptable']    = Helpers::$noopTest;
    $block['text']       = "/^[^\\n]+/";
    
    // regex template, placeholders will be replaced according to different paragraph
    // interruption rules of commonmark and the original markdown spec:
    $block['_paragraph'] = "/^([^\\n]+(?:\\n(?!hr|heading|lheading|blockquote|fences|list|html)[^\\n]+)*)/";
    $block['_comment']   = "/<!--(?!-?>)[\\s\\S]*?(?:-->|$)/";
    $block['_label']     = "/(?!\\s*\\])(?:\\\\[\\[\\]]|[^\\[\\]])+/";
    $block['_title']     = "/(?:\"(?:\\\\\"?|[^\"\\\\])*\"|'[^'\\n]*(?:\\n[^'\\n]+)*\\n?'|\\([^()]*\\))/";

    $block['_tag']       = "/address|article|aside|base|basefont|blockquote|body|caption"
                            . "|center|col|colgroup|dd|details|dialog|dir|div|dl|dt|fieldset|figcaption"
                            . "|figure|footer|form|frame|frameset|h[1-6]|head|header|hr|html|iframe"
                            . "|legend|li|link|main|menu|menuitem|meta|nav|noframes|ol|optgroup|option"
                            . "|p|param|section|source|summary|table|tbody|td|tfoot|th|thead|title|tr"
                            . "|track|ul/";

    $block['def'] = Helpers::edit($block['def'])
                    ->replace('label', $block['_label'])
                    ->replace('title', $block['_title'])
                    ->getRegex();
    
    $block['item'] = Helpers::edit($block['item'], 'm')
                    ->replace('bull', $block['bullet'])
                    ->getRegex();

    $block['listItemStart'] = Helpers::edit('/^( *)(bull)/')
                     ->replace('bull', $block['bullet'])
                     ->getRegex();

    $block['list'] = Helpers::edit($block['list'])
                     ->replace('bull', $block['bullet'])
                     ->replace('hr', "\\n+(?=\\1?(?:(?:- *){3,}|(?:_ *){3,}|(?:\\* *){3,})(?:\\n+|$))")
                     ->replace('def', "\\n+(?=" . trim($block['def'], '/') . ")")
                     ->getRegex();
    
    $block['html'] = Helpers::edit($block['html'], 'i')
                    ->replace('comment', $block['_comment'])
                    ->replace('tag', $block['_tag'])
                    ->replace('attribute', " +[a-zA-Z:_][\\w.:-]*(?: *= *\"[^\"\\n]*\"| *= *'[^'\\n]*'| *= *[^\\s\"'=<>`]+)?")
                    ->getRegex();
    
    $block['paragraph'] = Helpers::edit($block['_paragraph'])
                          ->replace('hr', $block['hr'])
                          ->replace('heading', " {0,3}#{1,6} ")
                          ->replace('|lheading', '') // setex headings don't interrupt commonmark paragraphs
                          ->replace('blockquote', " {0,3}>")
                          ->replace('fences', " {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n")
                          ->replace('list', " {0,3}(?:[*+-]|1[.)]) ") // only lists starting from 1 can interrupt
                          ->replace('html', "<\\/?(?:tag)(?: +|\\n|\\/?>)|<(?:script|pre|style|!--)")
                          ->replace('tag', $block['_tag']) // pars can be interrupted by type (6) html blocks
                          ->getRegex();
    
    $block['blockquote'] = Helpers::edit($block['blockquote'])
                          ->replace('paragraph', $block['paragraph'])
                          ->getRegex();
    
    /**
     * Normal Block Grammar
     */
    
    $block['normal'] = array_merge([], $block);
    
    /**
     * GFM Block Grammar
     */
    
    $block['gfm'] = array_merge([], $block['normal']);
    
    $block['gfm']['nptable'] = "/^ *([^|\\n ].*\\|.*)\\n" // header
                                . " {0,3}([-:]+ *\\|[-| :]*)" // align
                                . "(?:\\n((?:(?!\\n|hr|heading|blockquote|code|fences|list|html).*(?:\\n|$))*)\\n*|$)/"; // cells

    // Cells
    $block['gfm']['table'] = "/^ *\\|(.+)\\n" // header
                              . " {0,3}\\|?( *[-:]+[-| :]*)" // align
                              . "(?:\\n *((?:(?!\\n|hr|heading|blockquote|code|fences|list|html).*(?:\\n|$))*)\\n*|$)/"; // cells

    $block['gfm']['nptable'] = Helpers::edit($block['gfm']['nptable'])
                                ->replace('hr', $block['hr'])
                                ->replace('heading', " {0,3}#{1,6} ")
                                ->replace('blockquote', " {0,3}>")
                                ->replace('code', " {4}[^\\n]")
                                ->replace('fences', " {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n")
                                ->replace('list', " {0,3}(?:[*+-]|1[.)]) ") // only lists starting from 1 can interrupt
                                ->replace('html', "<\\/?(?:tag)(?: +|\\n|\\/?>)|<(?:script|pre|style|!--)")
                                ->replace('tag', $block['_tag']) // tables can be interrupted by type (6) html blocks
                                ->getRegex();
    
    $block['gfm']['table'] = Helpers::edit($block['gfm']['table'])
                              ->replace('hr', $block['hr'])
                              ->replace('heading', " {0,3}#{1,6} ")
                              ->replace('blockquote', " {0,3}>")
                              ->replace('code', " {4}[^\\n]")
                              ->replace('fences', " {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n")
                              ->replace('list', " {0,3}(?:[*+-]|1[.)]) ") // only lists starting from 1 can interrupt
                              ->replace('html', "<\\/?(?:tag)(?: +|\\n|\\/?>)|<(?:script|pre|style|!--)")
                              ->replace('tag', $block['_tag']) // tables can be interrupted by type (6) html blocks
                              ->getRegex();
    
    /**
     * Pedantic grammar (original John Gruber's loose markdown specification)
     */
    
    $block['pedantic'] = array_merge([], $block['normal']);
    
    $block['pedantic']['html'] = "/^ *(?:comment *(?:\\n|\\s*$)"
                                  . "|<(tag)[\\s\\S]+?<\\/\\1> *(?:\\n{2,}|\\s*$)" // closed tag
                                  . "|<tag(?:\"[^\"]*\"|'[^']*'|\\s[^'\"\\/>\\s]*)*?\\/?> *(?:\\n{2,}|\\s*$))/";

    $block['pedantic']['html'] = Helpers::edit($block['pedantic']['html'])
                                  ->replace('comment', $block['_comment'])
                                  ->replace('tag', "(?!"
                                                    . "(?:a|em|strong|small|s|cite|q|dfn|abbr|data|time|code|var|samp|kbd|"
                                                    . "sub|sup|i|b|u|mark|ruby|rt|rp|bdi|bdo|span|br|wbr|ins|del|img)"
                                                    . "\\b)\\w+(?!:|[^\\w\\s@]*@)\\b")
                                  ->getRegex();
    
    $block['pedantic']['def'] = "/^ *\\[([^\\]]+)\\]: *<?([^\\s>]+)>?(?: +([\"(][^\\n]+[\")]))? *(?:\\n+|$)/";
    $block['pedantic']['heading'] = "/^(#{1,6})(.*)(?:\\n+|$)/";
    
    $block['pedantic']['fences'] = Helpers::$noopTest; // fences not supported
    $block['pedantic']['paragraph'] = Helpers::edit($block['pedantic']['_paragraph'])
                                      ->replace('hr', $block['hr'])
                                      ->replace('heading', " *#{1,6} *[^\\n]")
                                      ->replace('lheading', $block['lheading'])
                                      ->replace('blockquote', " {0,3}>")
                                      ->replace('|fences', '')
                                      ->replace('|list', '')
                                      ->replace('|html', '')
                                      ->getRegex();

    self::$block = $block;

    /**
     * Inline-Level Grammar
     */
    $inline = [];
    
    $inline['escape']   = "/^\\\\([!\"#$%&'()*+,\\-\\.\\/:;<=>?@\\[\\]\\\\^_`{|}~])/";
    $inline['autolink'] = "/^<(scheme:[^\\s\\x00-\\x1f<>]*|email)>/";
    $inline['tag']      = "/^comment|^<\\/[a-zA-Z][\\w:-]*\\s*>" // self-closing tag
                          . "|^<[a-zA-Z][\\w-]*(?:attribute)*?\\s*\\/?>" // open tag
                          . "|^<\\?[\\s\\S]*?\\?>" // processing instruction, e.g. <?php
                          . "|^<![a-zA-Z]+\\s[\\s\\S]*?>" // declaration, e.g. <!DOCTYPE html>
                          . "|^<!\\[CDATA\\[[\\s\\S]*?\\]\\]>/"; // CDATA section

    $inline['url']      = Helpers::$noopTest;
    $inline['link']     = "/^!?\\[(label)\\]\\(\\s*(href)(?:\\s+(title))?\\s*\\)/";
    $inline['reflink']  = "/^!?\\[(label)\\]\\[(?!\\s*\\])((?:\\\\[\\[\\]]?|[^\\[\\]\\\\])+)\\]/";
    $inline['nolink']   = "/^!?\\[(?!\\s*\\])((?:\\[[^\\[\\]]*\\]|\\\\[\\[\\]]|[^\\[\\]])*)\\](?:\\[\\])?/";
    $inline['reflinkSearch'] = "/reflink|nolink(?!\\()/";

    $inline['strong'] = [
      'start'  => "/^(?:(\\*\\*(?=[*punctuation]))|\\*\\*)(?![\\s])|__/",
      'middle' => "/^\\*\\*(?:"
                          . "(?:(?!overlapSkip)(?:[^*]|\\\\\\*)|overlapSkip)"
                      . "|\\*(?:(?!overlapSkip)(?:[^*]|\\\\\\*)|overlapSkip)*?\\*"
                    . ")+?\\*\\*$"
                    . "|^__(?![\\s])((?:"
                          . "(?:(?!overlapSkip)(?:[^_]|\\_)|overlapSkip)"
                        . "|_(?:(?!overlapSkip)(?:[^_]|\\_)|overlapSkip)*?_"
                      . ")+?)__$/",

      'endAst' => "/[^punctuation\\s]\\*\\*(?!\\*)|[punctuation]\\*\\*(?!\\*)(?:(?=[punctuation_\\s]|$))/",
      'endUnd' => "/[^\\s]__(?!_)(?:(?=[punctuation*\\s])|$)/"
    ];

    $inline['em'] = [
      'start'  => "/^(?:(\\*(?=[punctuation]))|\\*)(?![*\\s])|_/",
      'middle' => "/^\\*(?:"
                         . "(?:(?!overlapSkip)(?:[^*]|\\\\\\*)|overlapSkip)"
                      . "|\*(?:(?!overlapSkip)(?:[^*]|\\\\\\*)|overlapSkip)*?\\*"
                    . ")+?\\*$"
                    . "|^_(?![_\\s])(?:"
                        . "(?:(?!overlapSkip)(?:[^_]|\\\\_)|overlapSkip)"
                      . "|_(?:(?!overlapSkip)(?:[^_]|\\\\_)|overlapSkip)*?_"
                    . ")+?_$/",

      'endAst' => "/[^punctuation\s]\\*(?!\\*)|[punctuation]\\*(?!\\*)(?:(?=[punctuation_\\s]|$))/",
      'endUnd' => "/[^\\s]_(?!_)(?:(?=[punctuation*\\s])|$)/"
    ];

    $inline['code']     = "/^(`+)([^`]|[^`][\\s\\S]*?[^`])\\1(?!`)/";
    $inline['br']       = "/^( {2,}|\\\\)\\n(?!\\s*$)/";
    $inline['del']      = Helpers::$noopTest;
    $inline['text']     = "/^(`+|[^`])(?:(?= {2,}\\n)|[\\s\\S]*?(?:(?=[\\\\<!\\[`*]|\\b_|$)|[^ ](?= {2,}\\n))|(?= {2,}\\n)))/";
    $inline['punctuation'] = '/^([\\s*punctuation])/';

    // list of punctuation marks from common mark spec
    // without * and _ to workaround cases with double emphasis
    $inline['_punctuation'] = "/!\"#$%&'()+\\-.,\\/:;<=>?@\\[\\]`^{|}~/";

    // sequences em should skip over [title](link), `code`, <html>
    $inline['punctuation'] = Helpers::edit($inline['punctuation'])
                          ->replace('punctuation', $inline['_punctuation'])
                          ->getRegex();

    $inline['_blockSkip']   = "/\\[[^\\]]*?\\]\\([^\\)]*?\\)|`[^`]*?`|<[^>]*?>/";
    $inline['_overlapSkip'] = "/__[^_]*?__|\\*\\*\\[^\\*\\]*?\\*\\*/";

    $inline['_comment'] = Helpers::edit($block['_comment'])
                          ->replace('(?:-->|$)', '-->')
                          ->getRegex();

    $inline['em']['start']  = Helpers::edit($inline['em']['start'])->replace('punctuation', $inline['_punctuation'])->getRegex();
    $inline['em']['middle'] = Helpers::edit($inline['em']['middle'])->replace('punctuation', $inline['_punctuation'])->replace('overlapSkip', $inline['_overlapSkip'])->getRegex();
    $inline['em']['endAst'] = Helpers::edit($inline['em']['endAst'])->replace('punctuation', $inline['_punctuation'])->getRegex();
    $inline['em']['endUnd'] = Helpers::edit($inline['em']['endUnd'])->replace('punctuation', $inline['_punctuation'])->getRegex();

    $inline['strong']['start']  = Helpers::edit($inline['strong']['start'])->replace('punctuation', $inline['_punctuation'])->getRegex();
    $inline['strong']['middle'] = Helpers::edit($inline['strong']['middle'])->replace('punctuation', $inline['_punctuation'])->replace('overlapSkip', $inline['_overlapSkip'])->getRegex();
    $inline['strong']['endAst'] = Helpers::edit($inline['strong']['endAst'])->replace('punctuation', $inline['_punctuation'])->getRegex();
    $inline['strong']['endUnd'] = Helpers::edit($inline['strong']['endUnd'])->replace('punctuation', $inline['_punctuation'])->getRegex();

    $inline['blockSkip']   = Helpers::edit($inline['_blockSkip'])->getRegex();
    $inline['overlapSkip'] = Helpers::edit($inline['_overlapSkip'])->getRegex();

    $inline['_escapes'] = "/\\\\([!\"#$%&'()*+,\\-.\\/:;<=>?@\\[\\]\\\\^_`{|}~])/";
    $inline['_scheme']  = "/[a-zA-Z][a-zA-Z0-9+.-]{1,31}/";
    $inline['_email']   = "/[a-zA-Z0-9.!#$%&'*+\\/=?^_`{|}~-]+"
                            . "(@)[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?"
                            . "(?:\\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+(?![-_])/";
    $inline['autolink'] = Helpers::edit($inline['autolink'])
                          ->replace('scheme', $inline['_scheme'])
                          ->replace('email', $inline['_email'])
                          ->getRegex();
    
    $inline['_attribute'] = "/\\s+[a-zA-Z:_][\\w.:-]*(?:\\s*=\\s*\"[^\"]*\"|\\s*=\\s*'[^']*'|\\s*=\\s*[^\\s\"'=<>`]+)?/";
    
    $inline['tag']      = Helpers::edit($inline['tag'])
                          ->replace('comment', $inline['_comment'])
                          ->replace('attribute', $inline['_attribute'])
                          ->getRegex();
    
    $inline['_label']   = "/(?:\\[(?:\\\\.|[^\\[\\]\\\\])*\\]|\\\\.|`[^`]*`|[^\\[\\]\\\\`])*?/";
    $inline['_href']    = "/<(?:\\\\.|[^\\n<>\\\\])+>|[^\\s\\x00-\\x1f]*/";
    $inline['_title']   = "/\"(?:\\\\\"?|[^\"\\\\])*\"|'(?:\\\\'?|[^'\\\\])*'|\\((?:\\\\\\)?|[^)\\\\])*\\)/";
    
    $inline['link']     = Helpers::edit($inline['link'])
                          ->replace('label', $inline['_label'])
                          ->replace('href', $inline['_href'])
                          ->replace('title', $inline['_title'])
                          ->getRegex();
    
    $inline['reflink']  = Helpers::edit($inline['reflink'])
                          ->replace('label', $inline['_label'])
                          ->getRegex();

    $inline['reflinkSearch'] = Helpers::edit($inline['reflinkSearch'])
                          ->replace('reflink', $inline['reflink'])
                          ->replace('nolink', $inline['nolink'])
                          ->getRegex();

    /**
     * Normal Inline Grammar
     */
    
    $inline['normal'] = array_merge([], $inline);

    /**
     * Pedantic Inline Grammar
     */

    $inline['pedantic'] = array_merge([], $inline['normal']);

    $inline['pedantic']['strong'] = [
      'start' => "/^__|\\*\\*/",
      'middle' => "/^__(?=\\S)([\\s\\S]*?\\S)__(?!_)|^\\*\\*(?=\\S)([\\s\\S]*?\\S)\\*\\*(?!\\*)/",
      'endAst' => "/\\*\\*(?!\\*)/",
      'endUnd' => "/__(?!_)/"
    ];

    $inline['pedantic']['em'] = [
      'start' => "/^_|\\*/",
      'middle' => "/^()\\*(?=\\S)([\\s\\S]*?\\S)\*(?!\\*)|^_(?=\\S)([\\s\\S]*?\\S)_(?!_)/",
      'endAst' => "/\\*(?!\\*)/",
      'endUnd' => "/_(?!_)/"
    ];

    $inline['pedantic']['link']   = Helpers::edit("/^!?\\[(label)\\]\\((.*?)\\)/")
                                    ->replace('label', $inline['_label'])
                                    ->getRegex();

    $inline['pedantic']['reflink'] = Helpers::edit("/^!?\\[(label)\\]\\s*\\[([^\\]]*)\\]/")
                                      ->replace('label', $inline['_label'])
                                      ->getRegex();

    /**
     * GFM Inline Grammar
     */
    
    $inline['gfm'] = array_merge([], $inline['normal']);
    
    $inline['gfm']['escape'] = Helpers::edit($inline['escape'])
                                ->replace('])', '~|])')
                                ->getRegex();

    $inline['gfm']['_extended_email'] = "/[A-Za-z0-9._+-]+"
                                          . "(@)[a-zA-Z0-9-_]+"
                                          . "(?:\\.[a-zA-Z0-9-_]*[a-zA-Z0-9])+(?![-_])/";

    $inline['gfm']['url']        = "/^((?:ftp|https?):\\/\\/|www\\.)(?:[a-zA-Z0-9\\-]+\\.?)+[^\\s<]*|^email/";
    $inline['gfm']['_backpedal'] = "/(?:[^?!.,:;*_~()&]+|\\([^)]*\\)|&(?![a-zA-Z0-9]+;$)|[?!.,:;*_~)]+(?!$))+/";
    $inline['gfm']['del']       = "/^(~~?)(?=[^\\s~])([\\s\\S]*?[^\\s~])\\1(?=[^~]|$)/";
    $inline['gfm']['text']       = "/^([`~]+|[^`~])"
                                      . "(?:(?= {2,}\\n)|[\\s\\S]*?"
                                      . "(?:"
                                        . "(?=[\\\\<!\\[`*~]|\\b_|https?:\\/\\/|ftp:\\/\\/|www\\.|$)"
                                        . "|[^ ](?= {2,}\\n)"
                                        . "|[^a-zA-Z0-9.!#$%&'*+\\/=?_`{\\|}~-](?=[a-zA-Z0-9.!#$%&'*+\\/=?_`{\\|}~-]+@)"
                                      . ")"
                                      . "|(?=[a-zA-Z0-9.!#$%&'*+\\/=?_`{\\|}~-]+@))/";

    $inline['gfm']['url'] = Helpers::edit($inline['gfm']['url'], 'i')
                            ->replace('email', $inline['gfm']['_extended_email'])
                            ->getRegex();
    
    /**
     * GFM + Line Breaks Inline Grammar
     */
    $inline['breaks'] = array_merge([], $inline['gfm']);
    
    $inline['breaks']['br']   = Helpers::edit($inline['br'])
                                ->replace('{2,}', '*')
                                ->getRegex();
    
    $inline['breaks']['text'] = Helpers::edit($inline['gfm']['text'])
                                ->replace('\\b_', '\\b_| {2,}\\n')
                                ->replace('{2,}', '*')
                                ->getRegex();

    self::$inline = $inline;
  }
}
Rules::init();

//+--------------------------------------------------------

/**
 * smartypants text replacement
 */

function smartypants($text) {
  // em-dashes
  $text = str_replace('---', "\u2014", $text);

  // en-dashes
  $text = str_replace('--', "\u2013", $text);

  // opening singles
  $text = preg_replace('/(^|[-\u2014/(\[{"\s])\'/', "$1\u2018", $text);

  // closing singles & apostrophes
  $text = str_replace("'", "\u2019", $text);

  // opening doubles
  $text = preg_replace('/(^|[-\u2014/(\[{\u2018\s])"/', "$1\u201C", $text);

  // closing doubles
  $text = str_replace('"', "\u201D", $text);

  // ellipses
  $text = str_replace('...', "\u2026", $text);

  return $text;
}

/**
 * mangle email addresses
 */
function mangle($text) {
  $out = '';
  $l = strlen($text);

  for ($i = 0; $i < $l; $i++) {
    $ch = ord($text[$i]);

    if (random_0_1() > 0.5) {
      $ch = 'x' . dechex($ch);
    }
    $out .= '&#' . $ch . ';';
  }

  return $out;
}

/**
 * Block Lexer
 */
 class Lexer {

   /**
    * @var array
    */
  public $options, $tokens, $links;

  /**
   * @var Tokenizer
   */
  public $tokenizer;

  /**
   * @param array[optional] $options
   */
  public function __construct($options = []) {
  
    // Token
    $this->tokens  = [];
    $this->links   = [];
    
    // Options
    $this->options = $options ? $options : Defaults::$defaults;
    
    if(!$this->options['tokenizer']) {
     $this->options['tokenizer'] = new Tokenizer();
    }
    
    // Tokenizer
    $this->tokenizer = $this->options['tokenizer'];
    $this->tokenizer->options = $this->options;
    
    if($this->options['pedantic']) {
     $rules = [
      'block'  => Rules::$block['pedantic'],
      'inline' => Rules::$inline['pedantic']
     ];
    
    } else if($this->options['gfm']) {
     $rules = [
       'block'  => Rules::$block['gfm'],
       'inline' => $this->options['breaks'] ? Rules::$inline['breaks'] : Rules::$inline['gfm']
     ];
    
    } else {
     $rules = [
       'block'  => Rules::$block['normal'],
       'inline' => Rules::$inline['normal']
     ];
    }
    $this->tokenizer->rules = $rules;
  }

  /**
   * Static Lex Method
   * @param string $src
   * @param array $options
   */
  public static function lex($src, $options) {
    $lexer = new Lexer($options);
    return $lexer->_lex($src);
  }

  /**
   * Static Lex Inline Method
   */
  public static function lexInline($src, $options) {
    $lexer = new Lexer($options);
    return $lexer->inlineTokens($src);
  }

  /**
   * Preprocessing
   * @param string $src
   * @return array
   */
  public function _lex($src) {
    $src = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", "    "], $src);

    $this->tokens = $this->blockTokens($src, [], true);
    $this->tokens = $this->inline($this->tokens);
    return $this->tokens;
  }

  /**
   * Lexing
   * @param string $src
   * @param array $tokens
   * @param boolean $top
   * @throws Exception
   * @return array
   */
 public function blockTokens($src, $tokens = [], $top = true) {
    $src = preg_replace('/^ +$/m', '', $src);
    while($src !== '') {

      // newline
      if($token = $this->tokenizer->space($src)) {
        $src = substr($src, strlen($token['raw']));

        if(isset($token['type'])) {
          $tokens[] = $token;
        }
        continue;
      }
      
      // code
      if ($token = $this->tokenizer->code($src, $tokens)) {
        $src = substr($src, strlen($token['raw']));

        if($token['type']) {
          $tokens[] = $token;
        } else {
          $lastToken = &$tokens[sizeof($tokens)-1];

          $lastToken['raw']  .= "\n" . $token['raw'];
          $lastToken['text'] .= "\n" . $token['text'];
        }
        continue;
      }

      // fences
      if ($token = $this->tokenizer->fences($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }
      
      // heading
      if ($token = $this->tokenizer->heading($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }
      
      // table no leading pipe (gfm)
      if ($token = $this->tokenizer->nptable($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }
      
      // hr
      if ($token = $this->tokenizer->hr($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }
      
      // blockquote
      if ($token = $this->tokenizer->blockquote($src)) {
        $src = substr($src, strlen($token['raw']));
      
        $token['tokens'] = $this->blockTokens($token['text'], [], $top);
        $tokens[] = $token;
        continue;
      }
      
      // list
      if ($token = $this->tokenizer->list($src)) {
        $src = substr($src, strlen($token['raw']));
        $l = sizeof($token['items']);

        for ($i = 0; $i < $l; $i++) {
          $token['items'][$i]['tokens'] = $this->blockTokens($token['items'][$i]['text'], [], false);
        }
        $tokens[] = $token;
        continue;
      }
      
      // html
      if ($token = $this->tokenizer->html($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }
      
      // def
      if ($top && ($token = $this->tokenizer->def($src))) {
        $src = substr($src, strlen($token['raw']));
      
        if (!@$this->links[$token['tag']]) {
          $this->links[$token['tag']] = [
            'href'  => $token['href'],
            'title' => $token['title']
          ];
        }
        continue;
      }
      
      // table (gfm)
      if ($token = $this->tokenizer->table($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }
      
      // lheading
      if ($token = $this->tokenizer->lheading($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }
      
      // top-level paragraph
      if ($top && ($token = $this->tokenizer->paragraph($src))) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }
      
      // text
      if ($token = $this->tokenizer->text($src, $tokens)) {
        $src = substr($src, strlen($token['raw']));

        if($token['type']) {
          $tokens[] = $token;
        } else {
          $lastToken = &$tokens[sizeof($tokens)-1];

          $lastToken['raw']  .= "\n" . $token['raw'];
          $lastToken['text'] .= "\n" . $token['text'];
        }
        continue;
      }
      
      // unknown
      if ($src) {
        $errMsg = 'Infinite loop on string: ' . substr($src, 0, 10) . '...';
      
        if ($this->options['silent']) {
          trigger_error($errMsg);
          break;
        } else {
          throw new Exception($errMsg);
        }
      }
    } //  end while
    
    return $tokens;
  }

  /**
   * @param array $tokens
   * @return array
   */
  public function inline($tokens) {
    $l = sizeof($tokens);

    for ($i = 0; $i < $l; $i++) {
      $token = &$tokens[$i];
      
      switch($token['type']) {
        case 'paragraph':
        case 'text':
        case 'heading':
          $token['tokens'] = $this->inlineTokens($token['text'], []);
          break;

        case 'table':
          $token['tokens'] = [
            'header' => [],
            'cells' => []
          ];

          // header
          $l2 = sizeof($token['header']);

          for ($j = 0; $j < $l2; $j++) {
            $token['tokens']['header'][$j] = $this->inlineTokens($token['header'][$j], []);
          }

          // cells
          $l2 = sizeof($token['cells']);

          for ($j = 0; $j < $l2; $j++) {
            $row = $token['cells'][$j];
            $token['tokens']['cells'][$j] = [];

            for ($k = 0; $k < sizeof($row); $k++) {
              $token['tokens']['cells'][$j][$k] = $this->inlineTokens($row[$k], []);
            }
          }
          break;
        
        case 'blockquote':
          $token['tokens'] = $this->inline($token['tokens']);
          break;
        
        case 'list':
          $l2 = sizeof($token['items']);

          for ($j = 0; $j < $l2; $j++) {
            $token['items'][$j]['tokens'] = $this->inline($token['items'][$j]['tokens']);
          }
          break;

      } // end switch
    } // end for

    return $tokens;
  }

  /**
   * Lexing/Compiling
   * @param string $src
   * @param array $tokens
   * @param boolean $inLink
   * @return array
   */
  public function inlineTokens($src, $tokens = [], $inLink = false, $inRawBlock = false) {

    // String with links masked to avoid interference with em and strong
    $maskedSrc = $src;

    // Mask out reflinks
    if ($this->links) {
      $lastIndex = 0;

      while (preg_match($this->tokenizer->rules['inline']['reflinkSearch'], $maskedSrc, $matches, PREG_OFFSET_CAPTURE, $lastIndex)) {
        list($match, $index) = $matches[0];
        $lastIndex = $index + strlen($match);

        $tag = substr($match, strrpos($match, '[')+1, -1);

        if (isset($this->links[$tag])) {
          $maskedSrc = substr($maskedSrc, 0, $index)
                     . '[' . str_repeat('a', strlen($match)-2) . ']'
                     . substr($maskedSrc, $lastIndex);
        }
      }
    }

    // Mask out other blocks
    $lastIndex = 0;
    while (preg_match($this->tokenizer->rules['inline']['blockSkip'], $maskedSrc, $matches, PREG_OFFSET_CAPTURE, $lastIndex)) {
      list($match, $index) = $matches[0];
      $lastIndex = $index + strlen($match);

      $maskedSrc = substr($maskedSrc, 0, $index)
                 . '[' . str_repeat('a', strlen($match)-2) . ']'
                 . substr($maskedSrc, $lastIndex);
    }

    $keepPrevChar = true;
    $prevChar = '';

    while($src !== '') {
      if(!$keepPrevChar) {
        $prevChar = '';
      }
      $keepPrevChar = false;

      // escape
      if ($token = $this->tokenizer->escape($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }

      // tag
      if ($token = $this->tokenizer->tag($src, $inLink, $inRawBlock)) {
        $src = substr($src, strlen($token['raw']));
        $inLink = $token['inLink'];
        $inRawBlock = $token['inRawBlock'];
        $tokens[] = $token;
        continue;
      }

      // link
      if ($token = $this->tokenizer->link($src)) {
        $src = substr($src, strlen($token['raw']));

        if ($token['type'] === 'link') {
          $token['tokens'] = $this->inlineTokens($token['text'], [], true, $inRawBlock);
        }

        $tokens[] = $token;
        continue;
      }

      // reflink, nolink
      if ($token = $this->tokenizer->reflink($src, $this->links)) {
        $src = substr($src, strlen($token['raw']));

        if ($token['type'] === 'link') {
          $token['tokens'] = $this->inlineTokens($token['text'], [], true, $inRawBlock);
        }

        $tokens[] = $token;
        continue;
      }

      // strong
      if ($token = $this->tokenizer->strong($src, $maskedSrc, $prevChar)) {
        $src = substr($src, strlen($token['raw']));
        $token['tokens'] = $this->inlineTokens($token['text'], [], $inLink, $inRawBlock);
        $tokens[] = $token;
        continue;
      }

      // em
      if ($token = $this->tokenizer->em($src, $maskedSrc, $prevChar)) {
        $src = substr($src, strlen($token['raw']));
        $token['tokens'] = $this->inlineTokens($token['text'], [], $inLink, $inRawBlock);
        $tokens[] = $token;
        continue;
      }

      // code
      if ($token = $this->tokenizer->codespan($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }

      // br
      if ($token = $this->tokenizer->br($src)) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }

      // del (gfm)
      if ($token = $this->tokenizer->del($src)) {
        $src = substr($src, strlen($token['raw']));
        $token['tokens'] = $this->inlineTokens($token['text'], [], $inLink, $inRawBlock);
        $tokens[] = $token;
        continue;
      }

      // autolink
      if ($token = $this->tokenizer->autolink($src, __NAMESPACE__ . "\mangle")) {
        $src = substr($src, strlen($token['raw']));
        $tokens[] = $token;
        continue;
      }

      // url (gfm)
      if (!$inLink && ($token = $this->tokenizer->url($src, __NAMESPACE__ . "\mangle"))) {
        $tokens[] = $token;
        $src = substr($src, strlen($token['raw']));
        continue;
      }

      // text
      if ($token = $this->tokenizer->inlineText($src, $inRawBlock, __NAMESPACE__ . "\smartypants")) {
        $src = substr($src, strlen($token['raw']));

        $prevChar = substr($token['raw'], -1);
        $keepPrevChar = true;

        $tokens[] = $token;
        continue;
      }

      // unknown
      if ($src) {
        $errMsg = 'Infinite loop on string: ' . substr($src, 0, 10) . '...';
      
        if ($this->options['silent']) {
          trigger_error($errMsg);
          break;
        } else {
          throw new Exception($errMsg);
        }
      }
    } // end while

    return $tokens;
  }
}

//+--------------------------------------------------------

/**
 * Renderer
 */
class Renderer {

  /**
   * @var array
   */
  public $options;

  /**
   * @param array $options
   */
  public function __construct($options = []) {
    $this->options = $options ? $options : Defaults::$defaults;
  }

  // Block-level renderer

  /**
   * @param string $src
   * @param string $infostring
   * @param boolean $escaped
   * @return string
   */
  public function code($src, $infostring = "", $escaped = false) {
    $lang = preg_match_get('/\S*/', $infostring)[0];

    if ($this->options['highlight']) {
      $out = $this->options['highlight']($src, $lang);

      if ($out != null && $out !== $src) {
        $escaped = true;
        $src = $out;
      }
    }

    $attrs = '';
    if($lang) {
      $attrs = ' class="' . $this->options['langPrefix'] . Helpers::escape($lang, true) . '"';
    }
    return '<pre><code' . $attrs . '>'
              . ($escaped ? $src : Helpers::escape($src, true))
              . "</code></pre>\n";
  }

  /**
   * @param string $src
   * @return string
   */
  public function blockquote($src) {
    return "<blockquote>\n" . $src . "</blockquote>\n";
  }

  /**
   * @param string $src
   * @return string
   */
  public function html($src) {
    return $src;
  }

  /**
   * @param string $src
   * @param integer $level
   * @param string $raw
   * @param object $slugger
   * @return string
   */
  public function heading($src, $level, $raw, $slugger) {
    $attrs = '';
    if($this->options['headerIds']) {
      $attrs = ' id="' . $this->options['headerPrefix'] . $slugger->slug($raw) . '"';
    }
    return '<h' . $level . $attrs . '>' . $src . '</h' . $level . '>' . "\n";
  }

  /**
   * @return string
   */
  public function hr() {
    return $this->options['xhtml'] ? "<hr/>\n" : "<hr>\n";
  }

  /**
   * @param string $src
   * @param boolean $ordered
   * @param integer $start
   * @return string
   */
  public function list($src, $ordered, $start = 1) {
    if(!$ordered) {
      return "<ul>\n" . $src . "</ul>\n";
    }

    $attrs = '';
    if($start != 1) {
      $attrs = ' start="' . $start . '"';
    }
    return '<ol' . $attrs . ">\n" . $src . "</ol>\n";
  }

  /**
   * @param string $src
   * @param boolean $isTask
   * @param boolean $isChecked
   * @return string
   */
  public function listitem($src, $isTask, $isChecked) {
    $attrs = '';
    if($isTask) {
      $attrs = ' class="task' . ($isChecked ? ' checked' : '') . '"';
    }
    return '<li' . $attrs . '>' . $src . "</li>\n";
  }

  /**
   * @param boolean $boolean
   * @return string
   */
  public function checkbox($checked) {
    return '<input ' . ($checked ? 'checked="" ' : '') . 'disabled="" type="checkbox"' . ($this->options['xhtml'] ? ' /' : '') . '> ';
  }

  /**
   * @param string $src
   * @return string
   */
  public function paragraph($src) {
    return '<p>' . $src . "</p>\n";
  }

  /**
   * @param string $header
   * @param string $body
   * @return string
   */
  public function table($header, $body) {
    return "<table>\n"
      . "<thead>\n" . $header . "</thead>\n"
      . ($body ? "<tbody>\n" . $body . "</tbody>\n" : '')
      . "</table>\n";
  }

  /**
   * @param string $src
   * @return string
   */
  public function tablerow($src) {
    return '<tr>' . $src . "</tr>\n";
  }

  /**
   * @param string $src
   * @param array $flags
   * @return string
   */
  public function tablecell($src, $flags) {

    $attrs = ' align="' . $flags['align'] . '"';

    if($flags['header']) {
      return '<th' . $attrs . '>' . $src . "</th>\n";
    } else {
      return '<td' . $attrs . '>' . $src . "</td>\n";
    }
  }

  // Span level renderer

  /**
   * @param string $src
   * @return string
   */
  public function strong($src) {
    return '<strong>' . $src . '</strong>';
  }

  /**
   * @param string $src
   * @return string
   */
  public function em($src) {
    return '<em>' . $src . '</em>';
  }

  /**
   * @param string $src
   * @return string
   */
  public function codespan($src) {
    return '<code>' . $src . '</code>';
  }

  /**
   * @return string
   */
  public function br() {
    return $this->options['xhtml'] ? '<br/>' : '<br>';
  }

  /**
   * @param string $src
   * @return string
   */
  public function del($src) {
    return '<del>' . $src . '</del>';
  }

  /**
   * @param string $href
   * @param string $title
   * @param string $text
   * @return string
   */
  public function link($href, $title, $text) {
    $href = Helpers::cleanUrl($this->options['sanitize'], $this->options['baseUrl'], $href);

    if ($href === null) {
      return $text;
    }

    $out = '<a href="' . Helpers::escape($href) . '"';
    if ($title) {
      $out .= ' title="' . $title . '"';
    }
    $out .= '>' . $text . '</a>';
    return $out;
  }

  /**
   * @param string $href
   * @param string $title
   * @param string $text
   * @return string
   */
  public function image($href, $title, $text) {
    $href = Helpers::cleanUrl($this->options['sanitize'], $this->options['baseUrl'], $href);

    if ($href === null) {
      return $text;
    }

    $out = '<img src="' . $href . '" alt="' . $text . '"';
    if ($title) {
      $out .= ' title="' . $title . '"';
    }
    $out .= $this->options['xhtml'] ? '/>' : '>';
    return $out;
  }

  /**
   * @param string $src
   * @return string
   */
  public function text($src) {
    return $src;
  }
}

/**
 * TextRenderer
 * returns only the textual part of the token
 */
class TextRenderer {

  // no need for block level renderers
   public function strong($src) {
    return $src;
  }

  public function em($src) {
    return $src;
  }

  public function codespan($src) {
    return $src;
  }

  public function del($src) {
    return $src;
  }

  public function html($src) {
    return $src;
  }

  public function text($src) {
    return $src;
  }

  public function link($href, $title, $text) {
    return '' . $text;
  }

  public function image($href, $title, $text) {
    return '' . $text;
  }

  public function br() {
    return '';
  }
}

/**
 * Slugger generates header id
 */
class Slugger {
  
  /**
   * @var array
   */
  public $seen;

  public function __construct() {
    $this->seen = [];
  }

  /**
   * Convert string to id
   * @param string $value
   * @return string
   */
  public function serialize($value) {
    $slug = strtolower(trim($value));
    $slug = preg_replace('/<[!\/a-z].*?>/i', '', $slug); // remove html tags
    $slug = preg_replace('/[\x{2000}-\x{206F}\x{2E00}-\x{2E7F}\\\'!"#$%&()*+,.\/:;<=>?@[\]^`{|}~]/u', '', $slug); // remove unwanted chars
    $slug = preg_replace('/\s/', '-', $slug);
    return $slug;
  }

  /**
   * Finds the next safe (unique) slug to use
   * @param string $originalSlug
   * @param boolean $isDryRun
   */
   public function getNextSafeSlug($originalSlug, $isDryRun) {
     $slug = $originalSlug;
     $occurenceAccumulator = 0;

    if (isset($this->seen[$slug])) {
      $occurenceAccumulator = $this->seen[$originalSlug];

      do {
        $occurenceAccumulator += 1;
        $slug = $originalSlug . '-' . $occurenceAccumulator;
      } while (isset($this->seen[$slug]));
    }

    if(!$isDryRun) {
      $this->seen[$originalSlug] = $occurenceAccumulator;
      $this->seen[$slug] = 0;
    }
    return $slug;
  }

  /**
   * Convert string to unique id
   * If dryrun: Generates the next unique slug without updating the internal accumulator.
   *
   * @param string $value
   * @param array $options - {dryrun: boolean}
   * @return string
   */
  public function slug($value, $options=[]) {
    $slug = $this->serialize($value);
    return $this->getNextSafeSlug($slug, @$options['dryrun']);
  }

}

/**
 * Parsing & Compiling
 */
class Parser {

  /**
   * @var array
   */
  public $options;

  /**
   * @var Renderer
   */
  public $renderer;

  /**
   * @var TextRenderer
   */
  public $textRenderer;

  /**
   * @var Slugger
   */
  public $slugger;

  /**
   * @param array $options
   */
  public function __construct($options = []) {

    // Options
    $this->options = $options ? $options : Defaults::$defaults;
    
    if(!$this->options['renderer']) {
      $this->options['renderer'] = new Renderer();
    }

    // Renderer
    $this->renderer = $this->options['renderer'];
    $this->renderer->options = $this->options;

    $this->textRenderer = new TextRenderer();
    $this->slugger      = new Slugger();
  }

  /**
   * Static Parse Method
   * @param array $tokens
   * @param array $options
   */
  public static function parse($tokens, $options = []) {
    $parser = new Parser($options);
    return $parser->_parse($tokens);
  }

  /**
   * Static Parse Inline Method
   */
  public static function parseInline($tokens, $options = []) {
    $parser = new Parser($options);
    return $parser->_parseInline($tokens);
  }

  /**
   * Parse Loop
   * @param array $tokens
   * @param boolean $top
   * @return string
   */
  public function _parse($tokens, $top = true) {
    $out = '';
    $l = sizeof($tokens);

    for ($i = 0; $i < $l; $i++) {
      $token = $tokens[$i];

      switch($token['type']) {
        case 'space':
          continue;

        case 'hr':
          $out .= $this->renderer->hr();
          continue;

        case 'heading':
          $out .= $this->renderer->heading(
                    $this->_parseInline($token['tokens']),
                    $token['depth'],
                    Helpers::unescape($this->_parseInline($token['tokens'], $this->textRenderer)),
                    $this->slugger
                  );
          continue;

        case 'code':
          $out .= $this->renderer->code($token['text'], @$token['lang'], @$token['escaped']);
          continue;

        case 'table':

          // Header
          $l2    = sizeof($token['header']);
          $cells = '';

          for ($j = 0; $j < $l2; $j++) {
            $cells .= $this->renderer->tablecell($this->_parseInline($token['tokens']['header'][$j]), [
              'header' => true,
              'align'  => $token['align'][$j]
            ]);
          }
          $header = $this->renderer->tablerow($cells);

          // Body
          $l2   = sizeof($token['cells']);
          $body = '';

          for ($j = 0; $j < $l2; $j++) {
            $row   = $token['tokens']['cells'][$j];
            $l3     = sizeof($row);
            $cells = '';

            for ($k = 0; $k < $l3; $k++) {
              $cells .= $this->renderer->tablecell($this->_parseInline($row[$k]), [
                'header' => false,
                'align'  => $token['align'][$k]
              ]);
            }
            $body .= $this->renderer->tablerow($cells);
          }

          // Table
          $out .= $this->renderer->table($header, $body);
          continue;

        case 'blockquote':
          $body = $this->_parse($token['tokens']);
          $out .= $this->renderer->blockquote($body);
          continue;

        case 'list':
          $ordered = $token['ordered'];
          $start   = $token['start'];
          $loose   = $token['loose'];

          $l2   = sizeof($token['items']);
          $body = '';

          for ($j = 0; $j < $l2; $j++) {
            $item    = $token['items'][$j];
            $checked = $item['checked'];
            $task    = $item['task'];

            $itemBody = '';

            if($item['task']) {
              $checkbox = $this->renderer->checkbox($checked);

              if($loose) {
                if($item['tokens'] && $item['tokens'][0]['type'] == 'text') {
                  $firstToken = $item['tokens'][0];

                  $firstToken['text'] = $checkbox . ' ' . $firstToken['text'];
  
                  if (isset($firstToken['tokens']) && sizeof($firstToken['tokens']) > 0 && $firstToken['tokens'][0]['type'] === 'text') {
                    $firstToken['tokens'][0]['text'] = $checkbox . ' ' . $firstToken['tokens'][0]['text'];
                  }

                } else {
                  array_unshift($item['tokens'], [
                    'type' => 'text',
                    'text' => $checkbox
                  ]);
                }
              } else {
                $itemBody .= $checkbox;
              } // end else $loose
            } // end if $item['task']

            $itemBody .= $this->_parse($item['tokens'], $loose);
            $body .= $this->renderer->listitem($itemBody, $task, $checked);
          } // end for

          $out .= $this->renderer->list($body, $ordered, $start);
          continue;

        case 'html':
          $out .= $this->renderer->html($token['text']);
          continue;

        case 'paragraph':
          $out .= $this->renderer->paragraph(isset($token['tokens']) ? $this->_parseInline($token['tokens']) : $token['text']);
          continue;

        case 'text':
          $body = @$token['tokens'] ? $this->_parseInline($token['tokens']) : $token['text'];

          while ($i + 1 < $l && $tokens[$i + 1]['type'] === 'text') {
            $token = $tokens[++$i];
            $body .= "\n" . (isset($token['tokens']) ? $this->_parseInline($token['tokens']) : $token['text']);
          }
  
          $out .= $top ? $this->renderer->paragraph($body) : $body;
          continue;

        default:
          $errMsg = 'Token with "' . $token['type'] . '" type was not found.';

          if ($this->options['silent']) {
            trigger_error($errMsg);
            break;
          } else {
            throw new Exception($errMsg);
          }

      } // end switch
    } // end for

    return $out;
  }

  /**
   * Parse Inline Tokens
   */
  public function _parseInline($tokens, $renderer = null) {
    if($renderer === null) {
      $renderer = $this->renderer;
    }

    $out = '';
    $l = sizeof($tokens);

    for ($i = 0; $i < $l; $i++) {
      $token = $tokens[$i];

      switch($token['type']) {
        case 'escape':
          $out .= $renderer->text($token['text']);
          break;

        case 'html':
          $out .= $renderer->html($token['text']);
          break;

        case 'link':
          $out .= $renderer->link($token['href'], @$token['title'], $this->_parseInline($token['tokens'], $renderer));
          break;

        case 'image':
          $out .= $renderer->image($token['href'], $token['title'], $token['text']);
          break;

        case 'strong':
          $out .= $renderer->strong($this->_parseInline($token['tokens'], $renderer));
          break;

        case 'em':
          $out .= $renderer->em($this->_parseInline($token['tokens'], $renderer));
          break;

        case 'codespan':
          $out .= $renderer->codespan($token['text']);
          break;

        case 'br':
          $out .= $renderer->br();
          break;

        case 'del':
          $out .= $renderer->del($this->_parseInline($token['tokens'], $renderer));
          break;

        case 'text':
          $out .= $renderer->text($token['text']);
          break;

        default:
          $errMsg = 'Token with "' . $token['type'] . '" type was not found.';

          if ($this->options['silent']) {
            trigger_error($errMsg);
            break;
          } else {
            throw new Exception($errMsg);
          }
      } // end switch
    } // end for

    return $out;
  }
}

//+--------------------------------------------------------

/**
 * Marked
 */
class Marked {

  /**
   * We're calling the object as a function
   * @param string $src
   * @param array $opt
   * @param callable $callback
   */
  public function __invoke($src, $opt = null, $callback = null) {
    return $this->_marked($src, $opt, $callback);
  }

  /**
   * @param string $src
   * @param array $opt
   * @param callable $callback
   */
  public function _marked($src, $opt = null, $callback = null) {
  
    // Check input
    if ($src === null) {
      throw new Exception('marked(): input parameter is undefined or null');
    }
    if (gettype($src) !== 'string') {
      throw new Exception('marked(): input parameter is of type ' . gettype($src) . ', string expected');
    }
    if(!$src) {
      return $src;
    }
  
    // Retrieve options / callback
    if(is_callable($opt) && !$callback) {
      $callback = $opt;
      $opt = [];
  
    } else if(!$opt) {
      $opt = [];
    }
    $opt = array_merge([], Defaults::$defaults, $opt);
    Helpers::checkSanitizeDeprecation($opt);
  
    $highlight = $opt['highlight'];
  
    if(!is_callable($callback)) {
      $callback = function($err, $out) {
        return [$err, $out];
      };
      $highlight = false;
    }
  
    // Get tokens
    try {
      $tokens = Lexer::lex($src, $opt);
    } catch(Exception $e) {
      return $callback($e, null);
    }
  
    // Convert to html
    $pending = sizeof($tokens);
  
    $done = function($err = null) use(&$tokens, $callback, $opt) {
      if($err) {
        return $callback($err, null);
      }
      try {
        $out = Parser::parse($tokens, $opt);
      } catch(Exception $e) {
        return $callback($e, null);
      }
      return $callback(null, $out);
    };

    if(!$highlight || !$pending) {
      return $done();
    }
  
    // Highlight
    foreach($tokens as &$token) {
      if($token['type'] == 'code') {
  
        $highlight($token['text'], $token['lang'], function($err, $code) use(&$token, &$pending, $done) {
          if ($err) {
            return $done($err);
          }
          if ($code != null && $code !== $token['text']) {
            $token['text']    = $code;
            $token['escaped'] = true;
          }
          if(!--$pending) {
            $done(null, $out);
          }
        });
  
      } else if(!--$pending) {
        return $done(null, $out);
      }
    } // end foreach
  }

  /**
   * Options
   */
  public function setOptions($opt) {
    $arr = array_merge([], Defaults::$defaults, $opt);
    Defaults::changeDefaults($arr);
    return $this;
  }

  public function getDefaults() {
    return Defaults::getDefaults();
  }

  /**
   * Handle $marked->defaults
   */
  public function __get($name) {
    if($name == 'defaults') {
      return Defaults::$defaults;
    }
    trigger_error('Undefined property: ' . __CLASS__ . '::$' . $name, E_USER_NOTICE);
  }
}

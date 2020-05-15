<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>PHP Marked</title>
        <link rel="stylesheet" href="style.css">
        <script>
            function preview(e) {
                e.preventDefault();

                var data = new FormData();
                data.append("input", this.elements.input.value);
                data.append("ajax", 1);

                window.fetch(window.location.pathname, {
                    method: "POST",
                    body: data
                })
                .then(res => res.text())
                .then(data => {
                    document.getElementById('output').innerHTML = data;
                })
            }
        </script>
    </head>
    <body>
        <form method="POST" onSubmit="return preview.call(this, event)">
            <div style="display: flex; max-height: 90vh;">
                <textarea name="input" id="input" style="width: 100%; resize: vertical;" rows="30">## Quick check

*ok* **strong**  
a b — space  
a	b — tab  
a&zwj;b — zero-width joiner  
a&#003;b — eof  
a&#000;b — null  
a&#x0818;b — samaritan mark occlusian

## About

Markdown est un langage de balisage (*markup langage* en anglais).
Un peu à la manière du HTML, il permet de formatter le texte via un système d'annotations.
Le Markdown se veut facile à lire, y compris dans sa version texte, et il est donc plus instinctif à apprendre.
Il est souvent supporté par les systèmes de commentaires, chats et forums.

<ins>Exemple de syntaxe Markdown</ins> :

    # Titre

    Du *texte en italique*, **en gras**.

    * Une liste
    * d'items

    ``` js
    function hello() {
      alert("Hello");
    }
    ```

## HTML

The content of HTML tags isn't parsed

<table style="table-layout:fixed">
  <thead><tr>
    <th>Raw</th>
    <th>HTML</th>
  </tr></thead>
  <tbody>
    <tr><td>**This is bold text**</td><td><strong>This is bold text</strong></td></tr>
    <tr><td>__This is bold text__</td><td><strong>This is bold text</strong></td></tr>
    <tr><td>*This is italic text*</td><td><em>This is italic text</em></td></tr>
    <tr><td>_This is italic text_</td><td><em>This is italic text</em></td></tr>
    <tr><td>~~Strikethrough~~</td><td><del>Strikethrough</del></td></tr>
    <tr><td>\*Literal asterisks\*</td><td>*Literal asterisks*</td></tr>
  </tbody>
</table>

# Blockquotes

    > Blockquote
    Still blockquote
    >> sub-blockquote
    > > > sub-sub blockquote.

> Blockquote
Still blockquote  
Again
>> sub-blockquote
> > > sub-sub blockquote

# Inline code

    Inline `code`

    Inline <code>code</code>

Inline `code`  

Inline <code>code</code>

# Block code

    ```
    Non interpreted <i>block code</i>
    ```

        Non interpreted <i>block code</i> (4 spaces)

    <pre>
    Interpreted <i>block code</i>
    </pre>

```
Non interpreted <i>block code</i>
```

    Non interpreted <i>block code</i> (4 spaces)

<pre>
Interpreted <i>block code</i>
</pre>

# Highlighted block code

    ``` js
    var foo = function (bar) {
      return bar++;
    };
    ```

``` js
var foo = function (bar) {
  return bar++;
};
```

    ``` diff
    diff --git a/filea.extension b/fileb.extension
    index d28nd309d..b3nu834uj 111111
    --- a/filea.extension
    +++ b/fileb.extension
    @@ -1,6 +1,6 @@
    -oldLine
    +newLine
    ```

``` diff
diff --git a/filea.extension b/fileb.extension
index d28nd309d..b3nu834uj 111111
--- a/filea.extension
+++ b/fileb.extension
@@ -1,6 +1,6 @@
-oldLine
+newLine
```

# Keyboard input

    <kbd>Ctrl</kbd> + <kbd>S</kbd>

<kbd>Ctrl</kbd> + <kbd>S</kbd>

# Emojis

    :sparkles: :camel: :boom:

:sparkles: :camel: :boom: (:atom:)

Full list : http://www.emoji-cheat-sheet.com/

# Formatting

## Not interpreted in a pre

<pre>**This is bold text**

__This is bold text__

*This is italic text*

_This is italic text_

~~Strikethrough~~

\*Literal asterisks\*</pre>

**This is bold text**

__This is bold text__

*This is italic text*

_This is italic text_

~~Strikethrough~~

\*Literal asterisks\*

## Interpreted in a pre

<pre>&lt;del>Strikethrough&lt;/del>

&lt;s>Strikethrough&lt;/s>

&lt;ins>Underline&lt;/ins>

Indice &lt;sub>sub&lt;/sub>

Exposant &lt;sup>sup&lt;/sup>

&amp;copy;

&amp;#10148;</pre>

<pre>
<del>Strikethrough</del>

<s>Strikethrough</s>

<ins>Underline</ins>

Indice <sub>sub</sub>

Exposant <sup>sup</sup>

&copy;

&#10148;</pre>

# Newlines

Return carriage at the end of the line
Is ignored

<!-- -->

Append two spaces at the end  
The preserve the newline

<!-- -->

Or you separate the lines

By a blank line

# Headers

<pre># h1 Heading
## h2 Heading
### h3 Heading
#### h4 Heading
##### h5 Heading
###### h6 Heading</pre>

# h1 Heading
## h2 Heading
### h3 Heading
#### h4 Heading
##### h5 Heading
###### h6 Heading

<pre>This is an H1
=============

This is an H2
-------------</pre>

This is an H1
=============

This is an H2
-------------

# Horizontal rule

<pre>___

---

***</pre>

___

---

***

# Images

## Interpreted in a pre

    ![Alt](http://placehold.it/50x50)
    ![Alt](http://placehold.it/50x50 "title")
    ![Alt][id_img]

    [id_img]: http://placehold.it/50x50

![Alt](http://placehold.it/50x50)
![Alt](http://placehold.it/50x50 "title")
![Alt][id_img]

[id_img]: http://placehold.it/50x50

# Links

## Interpreted in a pre

    http://google.com
    [Text](http://google.com)
    [Text](http://google.com "title")
    [Text][id_link]

    [id_link]: http://google.com

http://google.com  
[Text](http://google.com)  
[Text](http://google.com "title")  
[Text][id_link]  

[id_link]: http://google.com "optional title"

# Bulleted list

    * Item 1
      With content
    * Item 2  
      With content and preserving line break ("Item 2" is followed by 2 spaces)
    + Item 3
    + Item 4
    - Item 5
    - Item 6

* Item 1
  With content
* Item 2  
  With content and preserving line break ("Item 2" is followed by 2 spaces)
+ Item 3
+ Item 4
- Item 5
- Item 6

# Numbered list

    1. Item 1
    2. Item 2
    3. Item 3
       * Item 3a
       * Item 3b
    1. Item 4
       The number doesn't really matters
    1. Item 5
    2. Item 6
    2. Item 7

1. Item 1
2. Item 2
3. Item 3
   * Item 3a
   * Item 3b
1. Item 4
   The number doesn't really matters
1. Item 5
2. Item 6
2. Item 7

# Separate two list

    1. Item 1
   
    1. Item 2

    <!-- -->
   
    1. Begin another list !
   
1. Item 1

1. Item 2

<!-- -->

1. Begin another list !

# Comportement block

du texte avant
1. Item 1
du texte après

du texte avant
* Item 1
du texte après

# Table

<pre>| Default is left | Left-aligned | Center-aligned | Right-aligned |
| --- | :---         |     :---:      |          ---: |
| A | B   | C     | E    |
| F \| G | H     | I       | J      |</pre>

| Default is left | Left-aligned | Center-aligned | Right-aligned |
| --- | :---         |     :---:      |          ---: |
| A | B   | C     | E    |
| F \| G | H     | I       | J      |

# Todos

<pre>- [x] This is a complete item
- [ ] This is an incomplete item</pre>

- [x] This is a complete item
- [ ] This is an incomplete item

## Definition list

<dl>
  <dt>MO<br></dt>
  <dd><p>Oxyde basique</p></dd>

  <dt>M'O<br></dt>
  <dd><p>Oxyde acide</p></dd>

  <dt>HMO<br></dt>
  <dd><p>Hydroxyde métallique</p></dd>
  <dd><p>Hydroxyde</p></dd>

  <dt>HM'O<br></dt>
  <dd><p>Oxacide</p></dd>

  <dt>HM'<br></dt>
  <dd><p>Hydracide</p></dd>

  <dt>MM'<br></dt>
  <dd><p>Sel d'hydracide</p></dd>
  <dd><p>Sel</p></dd>

  <dt>MM'O<br></dt>
  <dd><p>Sel d'oxacide</p></dd>

  <dt>HMM'O<br></dt>
  <dd><p>Hydrogénosel</p></dd>
</dl></textarea>
                <div id="output" class="markdown-body" style="width: 100%; overflow: auto;"><?= $data ?></div>
            </div>
            <input type="submit" value="Preview">
        </form>
    </body>
</html>
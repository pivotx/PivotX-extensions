Syntax Highlighter Extension
============================

Syntax Highlighter is an PivotX extension, to show blocks of code on your
website, with proper highlighting. It is powered by 
<a href="http://alexgorbatchev.com/wiki/SyntaxHighlighter" target="_blank">SyntaxHighlighter</a>
by Alex Gorbatchev. 

Usage is pretty simple. Inside your entries, pages or templates, insert a
`<pre>` block with your code sample.

Example 1:

    <pre class="brush:css">
      body, td, pre {color:#000; font-family:Tahoma, Arial, Helvetica, sans-serif; }
      body {background:#FFF;}
      body.mceForceColors {background:#FFF; color:#000;}
      h1 {font-size: 2em}
      h2 {font-size: 1.5em}
      h3 {font-size: 1.17em}
    </pre>

Example 2:

    <pre class="brush:xml">
      <DisplayString ElementID="Subscription1223f83d_335be21470e9">
        <Name>Send critical alerts as email to our Backoffice group</Name>
      </DisplayString>
    </pre>

The formatting is determined by so called 'brushes'. The higlighter is
triggered by the `class="brush:xxx"` attribute, where 'xxx' determines the
brush to use. Only a few of them are enabled by default, to minimize the
overhead for each page. Edit the file
`extensions/syntaxhighligher/snippet_highlighter.php` to change the list of
'brushes' that are loaded for your site - 
<a href="http://alexgorbatchev.com/wiki/SyntaxHighlighter:Brushes"
target="_blank">available brushes</a>.

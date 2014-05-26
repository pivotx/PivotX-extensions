Syntax Highlighter Extension
============================

Syntax Highlighter is an PivotX extension, to show blocks of code on your
website, with proper highlighting. It is powered by 
<a href="http://alexgorbatchev.com/SyntaxHighlighter" target="_blank">SyntaxHighlighter</a>
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

Example 2 (including a title):

    <pre class="brush:xml" title="This is a title, check it out!">
      <DisplayString ElementID="Subscription1223f83d_335be21470e9">
        <Name>Send critical alerts as email to our Backoffice group</Name>
      </DisplayString>
    </pre>

Example 3 (collapsed):

    <pre class="brush: php; collapse: true; first-line: 5;" title="Collapsed source!">
      ......
    </pre>

The formatting is determined by so called 'brushes'.  
The highlighter is triggered by the `class="brush:xxx"` attribute, where 'xxx' determines the
brush to use.  
All brushes can be used due to the autoloader feature.  
See the site for 
<a href="http://alexgorbatchev.com/SyntaxHighlighter/manual/brushes/"
target="_blank">available brushes</a> and their effects.

This code also comes with several different themes. Theme default is enabled in the snippet.  
Edit the file `extensions/syntax-highlighter/snippet_highlighter.php` to change it.  
See <a href="http://alexgorbatchev.com/SyntaxHighlighter/manual/themes/"
target="_blank">available themes</a> for their effects.

You can also change some of the configuration defaults (or set them in your `<pre>` code). See <a href="http://alexgorbatchev.com/SyntaxHighlighter/manual/configuration/"
target="_blank">configuration</a>.

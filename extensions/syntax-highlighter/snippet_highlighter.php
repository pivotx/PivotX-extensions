<?php
// - Extension: Syntax Highlighter
// - Version: 0.2
// - Author: PivotX Team / Alex Gorbatchev
// - Email: admin@pivotx.net 
// - Site: http://www.pivotx.net
// - Description: Use SyntaxHighlighter in PivotX   
// - Date: 2014-05-12
// - Identifier: syntax-highlighter 
// - Required PivotX version: 2.0.2

global $PIVOTX;


$html = '<script type="text/javascript" src="%path%scripts/shCore.js"></script>' . "\n";
$html .= '<script type="text/javascript" src="%path%scripts/shAutoloader.js"></script>' . "\n";

$html .= '<link type="text/css" rel="stylesheet" href="%path%styles/shCore.css"/>' . "\n";
$html .= '<link type="text/css" rel="stylesheet" href="%path%styles/shThemeDefault.css"/>' . "\n";
$html .= '<script type="text/javascript">' . "\n";
$html .= 'jQuery(document).ready(function(){' . "\n";
$html .= "        SyntaxHighlighter.autoloader(" . "\n";
$html .= "        'as3 actionscript3 %path%scripts/shBrushAS3.js'," . "\n";
$html .= "        'bash shell %path%scripts/shBrushBash.js'," . "\n";
$html .= "        'cf coldfusion %path%scripts/shBrushColdFusion.js'," . "\n";
$html .= "        'c-sharp csharp %path%scripts/shBrushCSharp.js'," . "\n";
$html .= "        'cpp c %path%scripts/shBrushCpp.js'," . "\n";
$html .= "        'css %path%scripts/shBrushCss.js'," . "\n";
$html .= "        'delphi pas pascal %path%scripts/shBrushDelphi.js'," . "\n";
$html .= "        'diff patch %path%scripts/shBrushDiff.js'," . "\n";
$html .= "        'erl erlang %path%scripts/shBrushErlang.js'," . "\n";
$html .= "        'groovy %path%scripts/shBrushGroovy.js'," . "\n";
$html .= "        'js jscript javascript %path%scripts/shBrushJScript.js'," . "\n";
$html .= "        'java %path%scripts/shBrushJava.js'," . "\n";
$html .= "        'jfx javafx %path%scripts/shBrushJavaFX.js'," . "\n";
$html .= "        'perl pl %path%scripts/shBrushPerl.js'," . "\n";
$html .= "        'php %path%scripts/shBrushPhp.js'," . "\n";
$html .= "        'plain text %path%scripts/shBrushPlain.js'," . "\n";
$html .= "        'ps powershell %path%scripts/shBrushPowerShell.js'," . "\n";
$html .= "        'py python %path%scripts/shBrushPython.js'," . "\n";
$html .= "        'rails ror ruby %path%scripts/shBrushRuby.js'," . "\n";
$html .= "        'scala %path%scripts/shBrushScala.js'," . "\n";
$html .= "        'sql %path%scripts/shBrushPhp.js'," . "\n";
$html .= "        'vb vbnet %path%scripts/shBrushVb.js'," . "\n";
$html .= "        'xml xhtml xslt html xhtml %path%scripts/shBrushXml.js'" . "\n";
$html .= '        );' . "\n";
$html .= '        SyntaxHighlighter.all();' . "\n";
$html .= '});';
$html .= '</script>' . "\n";



$path = $PIVOTX['paths']['extensions_url']."syntax-highlighter/";
$html = str_replace('%path%', $path, $html);

// Add a hook to insert the generator meta tag and possibly a favicon link
$this->addHook(
    'after_parse',
    'insert_before_close_head',
    $html
    );

?>
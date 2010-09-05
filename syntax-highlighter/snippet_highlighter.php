<?php
// - Extension: Syntax Highlighter
// - Version: 0.1
// - Author: PivotX Team /
// - Email: admin@pivotx.net 
// - Site: http://www.pivotx.net
// - Description: Use SyntaxHighlighter in PivotX   
// - Date: 2009-09-25
// - Identifier: syntax-highlighter 
// - Required PivotX version: 2.0.2

global $PIVOTX;


$html = '<script type="text/javascript" src="%path%scripts/shCore.js"></script>' . "\n";

// Comment or uncomment the lines with brushes you do (or don't) need.
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushBash.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushCpp.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushCSharp.js"></script>' . "\n";
$html .= '<script type="text/javascript" src="%path%scripts/shBrushCss.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushDelphi.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushDiff.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushGroovy.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushJava.js"></script>' . "\n";
$html .= '<script type="text/javascript" src="%path%scripts/shBrushJScript.js"></script>' . "\n";
$html .= '<script type="text/javascript" src="%path%scripts/shBrushPhp.js"></script>' . "\n";
$html .= '<script type="text/javascript" src="%path%scripts/shBrushPlain.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushPython.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushRuby.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushScala.js"></script>' . "\n";
$html .= '<script type="text/javascript" src="%path%scripts/shBrushSql.js"></script>' . "\n";
//$html .= '<script type="text/javascript" src="%path%scripts/shBrushVb.js"></script>' . "\n";
$html .= '<script type="text/javascript" src="%path%scripts/shBrushXml.js"></script>' . "\n";


$html .= '<link type="text/css" rel="stylesheet" href="%path%styles/shCore.css"/>' . "\n";
$html .= '<link type="text/css" rel="stylesheet" href="%path%styles/shThemeDefault.css"/>' . "\n";
$html .= '<script type="text/javascript">' . "\n";
$html .= '        SyntaxHighlighter.config.clipboardSwf = "%path%scripts/clipboard.swf";' . "\n";
$html .= '        SyntaxHighlighter.all();' . "\n";
$html .= '</script>' . "\n";



$path = $PIVOTX['paths']['extensions_url']."syntaxhighlighter/";
$html = str_replace('%path%', $path, $html);

// Add a hook to insert the generator meta tag and possibly a favicon link
$this->addHook(
    'after_parse',
    'insert_before_close_head',
    $html
    );

	


?>

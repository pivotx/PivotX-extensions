<?php
// - Extension: Hello World widget
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A simple example to demonstrate 'Hello world' as a Widget Extension.
// - Date: 2007-05-20
// - Identifier: hello-world-widget

/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 */
$this->addHook(
    'widget',
    'hello_world',
    'widget_hello_world'
);

/**
 * Output a "Hello, World!" as a widget
 *
 * @return string
 */
function widget_hello_world() {

    // Just select some random widget style. 
    $widget_styles = array_keys(getDefaultWidgetStyles());
    $widget_style = $widget_styles[0];

    // Then the output
    $text = "<p><strong>Hello, World!</strong></p>";
    $output = "\n<div class='$widget_style'>$text</div>\n";
    return $output;

}


?>

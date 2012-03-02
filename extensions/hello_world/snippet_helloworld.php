<?php
// - Extension: Hello world snippet
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A small 'Hello World' extension snippet (this one has the regular Identifier).
// - Date: 2007-05-20
// - Identifier: hello-world


/**
 * A small snippet extension that makes sure the [[ hello ]] tag works in your templates.
 *
 */


// Register 'hello' as a smarty tag.
$PIVOTX['template']->register_function('hello', 'smarty_hello');

/**
 * Output 'Hello, world'
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_hello($params, &$smarty) {

    return "<strong>* Hello, World! *</strong>";

}

?>

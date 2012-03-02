<?php
// - Extension: Hello World hook
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A simple example to demonstrate 'Hello world' as a Hook Extension. This will insert small bits of text or code in various places of the output HTML.
// - Date: 2007-05-20
// - Identifier: hello-world-hook


/**
 * Two examples for simple hooks. We'll have these hooks insert a small message
 * in the HTML of all outputted pages. One in <head>, and one after </body>.
 *
 * Since the initialisation is called from the scope of the Extension object,
 * we can use $this.
 *
 *
 */

$this->addHook(
    'after_parse',
    'insert_before_close_head',
    "<!-- hello, world! (in the header) -->"
    );


$this->addHook(
    'after_parse',
    'insert_before_close_body',
    "<p>At the bottom of the page!</p>"
    );


$this->addHook(
    'after_parse',
    'insert_after_open_body',
    "<small>This is inserted at the beginning of the body.</small>"
    );

$this->addHook(
    'after_parse',
    'insert_at_end',
    "<!-- at the very end.. -->"
    );

$this->addHook(
    'after_parse',
    'insert_at_end',
    "<!-- another one that hooks in at the same action.. -->"
    );


?>

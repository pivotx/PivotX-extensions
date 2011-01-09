<?php
// - Extension: SEO - Search Engine Optimization
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: This extension allows you to easily add meta-tags to your entries/pages, to optimize your site for search engines.
// - Date: 2011-01-09
// - Identifier: seo
// - Required PivotX version: 2.2.0

$this->addHook(
    'in_pivotx_template',
    'entry-keywords-before',
    array('callback' => 'seoExtrafieldsHook' )
    );

$this->addHook(
    'in_pivotx_template',
    'page-keywords-before',
    array('callback' => 'seoExtrafieldsHook' )
    );


$this->addHook('after_parse', 'callback', 'seoCallback');

/**
 * Callback function for our hook..
 */
function seoExtrafieldsHook($content) {
    global $PIVOTX;

    $output = <<< EOM
    <table class="formclass" border="0" cellspacing="0" width="650">
        <tbody>

            <tr>
                <td colspan="3"><strong>SEO options for this %pagetype%:</strong> <span class='seotoggle'>(<a href="#">Show</a>)</span></td>
            </tr>

            <tr class="seo-tr">
                <td>
                    <label><strong>Description:</strong></label>
                </td>
                <td>
                    <input id="extrafield-seodescription" name="extrafields[seodescription]" value="%seodescription%" type="text" />
                </td>
            </tr>

            <tr class="seo-tr">
                <td>
                    <label><strong>Keywords:</strong></label>
                </td>
                <td>
                    <input id="extrafield-seokeywords" name="extrafields[seokeywords]" value="%seokeywords%" type="text" />
                </td>
            </tr>


            <tr class="seo-tr">
                <td>
                    <label><strong>Title:</strong></label>
                </td>
                <td>
                    <input id="extrafield-seotitle" name="extrafields[seotitle]" value="%seotitle%" type="text" /><br />
                    <p style="font-size: 10px; color: #999; margin: 0;">By default the SEO extension fills the meta-tags and <tt>&lt;title&gt;</tt>
                with appropriate content<br /> from the %pagetype%, but you can override them here.</p>
                </td>
            </tr>

            <tr class="seo-tr">
                <td colspan="2"><hr noshade="noshade" size="1" /></td>
            </tr>


        </tbody>
    </table>

    <script type="text/javascript">
    jQuery(function($) {

        $('.seo-tr').hide();

        $('.seotoggle').bind('click', function(e){
            e.preventDefault();
            $('.seo-tr').slideDown('slow');
            $('.seotoggle').html('');

        });

    });
    </script>

EOM;

    if (!isset($content['category'])) {
        $pagetype = "page";
    } else {
        $pagetype = "entry";
    }

    // Substitute some labels..
    $output = str_replace("%pagetype%", $pagetype, $output);

    // For ease of use, just try to replace everything in $entry here:
    foreach($content as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    foreach($content['extrafields'] as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    // Don't keep any %whatever%'s hanging around..
    $output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);

    return $output;

}


/**
 * "Main" routine.. if we're rendering a page or entry, or other type of page,
 * output the appropriate meta tags.
 */

function seoCallback(&$html) {
    global $PIVOTX;

    $modifier = $PIVOTX['parser']->modifier;

    $users = $PIVOTX['users']->getUsers();

    // We either get a page or entry, or output some general tags.
    if ($modifier['pagetype']=="entry") {
        $content = $PIVOTX['db']->read_entry($modifier['uid']);
    }

    if ($modifier['pagetype']=="page") {
        $content = $PIVOTX['pages']->getPage($modifier['uid']);
    }

    if ($modifier['pagetype']!="entry" && $modifier['pagetype']!="page") {
        $content = array();

        $title = array( $PIVOTX['weblogs']->get('', 'payoff'), $PIVOTX['weblogs']->get('', 'name'), NULL,  $PIVOTX['config']->get('sitename') );
        foreach($title as $key => $value) {
            $title[$key] = trim($value);
            if(empty($value)) { unset($title[$key]); }
        }
        $content['title'] = implode(" - ", $title);

        $tags = getTagCosmos(30);
        if (!empty($tags['tags'])) {
            $content['keywords'] = implode(" ", array_keys($tags['tags']));
        }

    }

    // Use the specific values from the extrafields, or the default values..
    $title = getDefault($content['extrafields']['seotitle'], "");
    $description = getDefault($content['extrafields']['seodescription'], $content['introduction']);
    $keywords = getDefault($content['extrafields']['seokeywords'], $content['keywords']);
    $revised = getDefault($content['edit_date'], "");
    $author = getDefault($content['user'], $users[0]['username']);



    // cleaning up.
    $title = preg_replace("/[\r\n]+/i", " ", strip_tags($title));
    $description = preg_replace("/[\r\n]+/i", " ", trimText($description, 250, false, ".."));
    $keywords = trimtext($keywords, 500, false, "");
    $author = $PIVOTX['users']->getUser($author);

    // Add description meta tag to output
    if (!empty($description)) {
        OutputSystem::instance()->addCode('seo-description', OutputSystem::LOC_HEADSTART, 'meta',
           array('name'=>"description", 'content'=>$description, '_priority'=>OutputSystem::PRI_HIGH)
        );
    }

    // Add author meta tag to output
    if (!empty($author)) {
        OutputSystem::instance()->addCode('seo-author', OutputSystem::LOC_HEADSTART, 'meta',
           array('name'=>"author", 'content'=> $author['nickname'] , '_priority'=>OutputSystem::PRI_HIGH)
        );
    }

    // Add revised meta tag to output
    if (!empty($author) && !empty($revised)) {
        $revised = sprintf("%s on %s", $author['nickname'], formatDate($revised, "%year%-%month%-%day%") );
        OutputSystem::instance()->addCode('seo-revised', OutputSystem::LOC_HEADSTART, 'meta',
           array('name'=>"revised", 'content'=> $revised , '_priority'=>OutputSystem::PRI_HIGH)
        );
    }

    // Add keywords meta tag to output
    if (!empty($keywords)) {
        OutputSystem::instance()->addCode('seo-keywords', OutputSystem::LOC_HEADSTART, 'meta',
           array('name'=>"keywords", 'content'=>$keywords, '_priority'=>OutputSystem::PRI_HIGH)
        );
    }

    // replace title tag in output
    if (!empty($title)) {
        $html = preg_replace("/<title>(.*)<\/title>/msi", "<title>$title</title>", $html);
    }

}





?>

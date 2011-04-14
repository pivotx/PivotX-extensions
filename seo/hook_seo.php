<?php
// - Extension: SEO - Search Engine Optimization
// - Version: 0.4
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: This extension allows you to easily add meta-tags to your entries/pages, to optimize your site for search engines.
// - Date: 2011-04-14
// - Identifier: seo
// - Required PivotX version: 2.2.4


global $seo_config;

$seo_config = array(
    'seo_description_length' => 250,
    'seo_keywords_length' => 500,
    'seo_default_keywords' => "",
    'seo_extra_keywords' => ""
);

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

$this->addHook(
    'configuration_add',
    'seo',
    array("seoAdmin", "SEO options")
);



$this->addHook('after_parse', 'callback', 'seoCallback');

/**
 * Callback function for our hook..
 */
function seoExtrafieldsHook($content) {
    global $PIVOTX, $seo_config;


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
    global $PIVOTX, $seo_config;

    // Sets the variables from the default $seo_config and/or the PivotX Configuration
    $configdata = $PIVOTX['config']->getConfigArray();
    foreach ($seo_config as $key => $value) {
        if (isset($configdata[$key])) {
            $$key = $configdata[$key];
        } else {
            $$key = $value;
        }
    }

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

        $content['introduction'] = $PIVOTX['config']->get('sitedescription');

        // Get keywords from the default seo keywords in config, or from the global tags/keywords
        if (!empty($seo_default_keywords)) {
            $seo_default_keywords = preg_split("/[ ,\n]+/i", $seo_default_keywords);
            $seo_default_keywords = array_map("trim", $seo_default_keywords);    
            $content['keywords'] = implode(" ", $seo_default_keywords);
        } else {
            $tags = getTagCosmos(30);
            if (!empty($tags['tags'])) {
                $content['keywords'] = implode(" ", array_keys($tags['tags']));
            }            
        }
    }

    // Use the specific values from the extrafields, or the default values..
    $title = getDefault($content['extrafields']['seotitle'], "");
    $description = getDefault($content['extrafields']['seodescription'], $content['introduction']);
    $keywords = getDefault($content['extrafields']['seokeywords'], $content['keywords']);
    $revised = getDefault($content['edit_date'], "");
    $author = getDefault($content['user'], $users[0]['username']);


    // Perhaps add extra keywords, from config.
    if (!empty($seo_extra_keywords)) {
        $seo_extra_keywords = preg_split("/[ ,\n]+/i", $seo_extra_keywords);
        $seo_extra_keywords = array_map("trim", $seo_extra_keywords);    
        $keywords .= " " . implode(" ", $seo_extra_keywords);
    }

    // cleaning up.
    $title = preg_replace("/[\r\n]+/i", " ", strip_tags($title));
    $description = preg_replace("/[\r\n]+/i", " ", trimText(parse_intro_or_body($description), $seo_description_length, false, ".."));
    $keywords = trimtext($keywords, $seo_keywords_length, false, "");
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



/**
 * The configuration screen for SEO Extension
 *
 * @param unknown_type $form_html
 */
function seoAdmin(&$form_html) {
    global $PIVOTX, $seo_config;

    $form = $PIVOTX['extensions']->getAdminForm('seo');

    $form->add( array(
        'type' => 'text',
        'name' => 'seo_description_length',
        'size' => 10,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=2000',
        'label' => "Maximum description length",
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'seo_keywords_length',
        'size' => 10,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=2000',
        'label' => "Maximum keywords length",
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'seo_default_keywords',
        'label' => "Default keywords",
        'text' => makeJtip("Default keywords", "Optional default keywords, that will be used on pages, that are not single Entries or Pages. Use a comma or space to separate keywords.")
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'seo_extra_keywords',
        'label' => "Extra keywords",
        'text' => makeJtip("Extra keywords", "Optional extra keywords, that will be added to every page after the page-specific keywords. Use a comma or space to separate keywords.")
    ));

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['seo'] = $PIVOTX['extensions']->getAdminFormHtml($form, $seo_config);

}



?>

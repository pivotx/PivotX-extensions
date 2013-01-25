<?php
// - Extension: SEO - Search Engine Optimization
// - Version: 0.6
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: This extension allows you to easily add meta-tags to your entries/pages, to optimize your site for search engines.
// - Date: 2013-01-25
// - Identifier: seo
// - Required PivotX version: 2.3.6


global $seo_config;

$seo_config = array(
    'seo_description_length' => 250,
    'seo_keywords_length' => 500,
    'seo_default_keywords' => "",
    'seo_extra_keywords' => "",
    'seo_fixed_author' => "",
    'seo_copyright' => "",
    'seo_use_dc_tags' => 0,
    'seo_extra_tags' => ""
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
    array("seoAdmin", "SEO")
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
    $conttitle = getDefault($content['title'], "");
    $description = getDefault($content['extrafields']['seodescription'], str_replace("&nbsp;", " ", $content['introduction']));
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
    if (!empty($seo_fixed_author)) {
        $authortag = $seo_fixed_author;
    } else {
        $authortag = $author['nickname'];
    }

    // Add description meta tag to output
    if (!empty($description)) {
        OutputSystem::instance()->addCode('seo-description', OutputSystem::LOC_HEADSTART, 'meta',
            array('name'=>"description", 'content'=>$description, '_priority'=>OutputSystem::PRI_HIGH)
        );
        if ($seo_use_dc_tags == 1) {
            OutputSystem::instance()->addCode('seo-dcdescription', OutputSystem::LOC_HEADSTART, 'meta',
                array('name'=>"dc.description", 'content'=>$description, '_priority'=>OutputSystem::PRI_HIGH)
            );
        }
    }

    // Add author meta tag to output
    if (!empty($authortag)) {
        OutputSystem::instance()->addCode('seo-author', OutputSystem::LOC_HEADSTART, 'meta',
           array('name'=>"author", 'content'=> $authortag, '_priority'=>OutputSystem::PRI_HIGH)
        );
    }
    // Add copyright meta tag to output
    if (!empty($seo_copyright)) {
        OutputSystem::instance()->addCode('seo-copyright', OutputSystem::LOC_HEADSTART, 'meta',
           array('name'=>"copyright", 'content'=>$seo_copyright, '_priority'=>OutputSystem::PRI_HIGH)
        );
    }

    // Add revised meta tag to output
    if (!empty($revised)) {
        $revised = formatDate($revised, "%year%-%month%-%day%");
        OutputSystem::instance()->addCode('seo-revised', OutputSystem::LOC_HEADSTART, 'meta',
           array('name'=>"revised", 'content'=> $revised , '_priority'=>OutputSystem::PRI_HIGH)
        );
    }

    // Add keywords meta tag to output
    if (!empty($keywords)) {
        OutputSystem::instance()->addCode('seo-keywords', OutputSystem::LOC_HEADSTART, 'meta',
           array('name'=>"keywords", 'content'=>$keywords, '_priority'=>OutputSystem::PRI_HIGH)
        );
        if ($seo_use_dc_tags == 1) {
            OutputSystem::instance()->addCode('seo-dckeywords', OutputSystem::LOC_HEADSTART, 'meta',
                array('name'=>"dc.keywords", 'content'=>$keywords, '_priority'=>OutputSystem::PRI_HIGH)
            );
        }
    }
    // Add extra tags to output
    if (!empty($seo_extra_tags)) {
        // $html = preg_replace("/<head([^>]*?)>/si", "<head$1>\n" . $seo_extra_tags, $html);
        $extratags = explode('/>', $seo_extra_tags);  
        foreach ($extratags as $key => $extratag) {
            $extratag = str_replace("<","",$extratag);
            $extratag = str_replace("meta","",$extratag);
            $extratyp = "";
            $extratyp = str_replace("http-equiv=","",$extratag);
            if ($extratyp != $extratag) {
                $extratag = $extratyp;
                $extratyp = 'http-equiv';
            } else {
                $extratyp = str_replace("name=","",$extratag);
                if ($extratyp != $extratag) {
                    $extratag = $extratyp;
                    $extratyp = 'name';
                } else {
                    continue;
                }
            }
            $extratag = str_replace("content=","",$extratag);
            $extratag = trim($extratag);
            $extraparts = explode('" "', $extratag);
            $extrapart1 = ""; $extrapart2 = "";
            foreach ($extraparts as $keypart => $extrapart) {
                if ($keypart == 0) { 
                    $extrapart1 = trim($extrapart); 
                    $extrapart1 = str_replace('"',"",$extrapart1);
                }
                if ($keypart == 1) { 
                    $extrapart2 = trim($extrapart); 
                    $extrapart2 = str_replace('"',"",$extrapart2);
                }
            }
            if ($extratyp == 'name') {
                OutputSystem::instance()->addCode('seo-extratags-' . $key, OutputSystem::LOC_HEADSTART, 'meta',
                    array('name'=>$extrapart1, 'content'=>$extrapart2, '_priority'=>OutputSystem::PRI_HIGH)
                );
            }
            if ($extratyp == 'http-equiv') {
                OutputSystem::instance()->addCode('seo-extratags-' . $key, OutputSystem::LOC_HEADSTART, 'meta',
                    array('http-equiv'=>$extrapart1, 'content'=>$extrapart2, '_priority'=>OutputSystem::PRI_HIGH)
                );
            }
        }
    }

    // replace title tag in output
    if (!empty($title)) {
        $htmlsave = $html;
        $html = preg_replace("/<title>(.*)<\/title>/msi", "<title>$title</title>", $html);
        // Sometimes the result of using the /s parm is that the whole html is gone after the preg_replace
        // The reason for this is still illusive. For now a less elegant way of replacing the title is implemented.
        if ($html == '') {
            //debug('seo-html empty after /msi?');
            $html = $htmlsave;
            $begpos = strpos($html, '<title>');
            if ($begpos > 0){
                $endpos = strpos($html, '</title>', $begpos);
                $htmlnew = substr($html, 1, $begpos-1);
                $endpos = $endpos + 8;
                $htmlnew .= '<title>' . $title . '</title>';
                $htmlnew .= substr($html, $endpos);
                $html = $htmlnew;
            }
        }
        if ($seo_use_dc_tags == 1) {
            OutputSystem::instance()->addCode('seo-dctitle', OutputSystem::LOC_HEADSTART, 'meta',
                array('name'=>"dc.title", 'content'=>$title, '_priority'=>OutputSystem::PRI_HIGH)
            );
        }
    } else {
        if ($seo_use_dc_tags == 1) {
            OutputSystem::instance()->addCode('seo-dctitle', OutputSystem::LOC_HEADSTART, 'meta',
                array('name'=>"dc.title", 'content'=>$conttitle, '_priority'=>OutputSystem::PRI_HIGH)
            );
        }
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

    $form->add( array(
        'type' => 'text',
        'name' => 'seo_fixed_author',
        'label' => "Fixed author",
        'text' => makeJtip("Fixed author", "Specify a fixed string to be used as author.")
    ));
    
    $form->add( array(
        'type' => 'text',
        'name' => 'seo_copyright',
        'label' => "Copyright",
        'text' => makeJtip("Copyright", "Specify the copyright string.")
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'seo_use_dc_tags',
        'label' => "Use DC tags",
        'text' => makeJtip("Use DC tags", 
        "Generate DC tags as well (currently title, description and keywords are supported)."),
        'options' => array(
               0 => "No",
               1 => "Yes"
               )
    ));
    
    $form->add( array(
        'type' => 'textarea',
        'name' => 'seo_extra_tags',
        'label' => "Extra tags",
        'text' => makeJtip("Extra tags", 
        "Enter all other tags (in full syntax) that should be added to each page 
        (e.g. google-site-verification).")
    ));

    $form->use_javascript(true);

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['seo'] = $PIVOTX['extensions']->getAdminFormHtml($form, $seo_config);

}
?>
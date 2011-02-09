<?php
// - Extension: Picnik
// - Version: 1.1.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to allow editing images through picnik.com.
// - Date: 2011-02-09
// - Identifier: picnik
// - Required PivotX version: 2.0.2

global $picnik_config;

$picnik_config = array(
    'picnik_keeporiginal' => 1,
    'picnik_replacebutton' => 1,
    'picnik_apikey' => "c156c207b4e7a920b6660c3629c62047", // PivotX's precious official Picnik API key!
);


/**
 * Adds the hook for picnikHookMediaImage()
 *
 * @see picnikHookMediaImage()
 */
$this->addHook(
    'in_pivotx_template',
    'media-line-image',
    array('callback' => "picnikHookMediaImage")
);


/**
 * Adds the hook for picnikHookMediaBefore()
 *
 * @see picnikHookMediaBefore()
 */
$this->addHook(
    'in_pivotx_template',
    'media-before',
    array('callback' => "picnikHookMediaBefore")
);


/**
 * Adds the hook for picnikAdmin()
 *
 * @see picnikAdmin()
 */
$this->addHook(
    'configuration_add',
    'Picnik',
    array("picnikAdmin", "Picnik")
);


/**
 * Inserts a bit of javascript at the top of the 'manage media' screen,
 * so that we can edit images in picnik
 *
 * @return string
 */
function picnikHookMediaBefore() {
    global $picnik_config, $PIVOTX;

    // Hide the original 'edit' button, perhaps..
    $hide = getDefault($PIVOTX['config']->get('picnik_replacebutton'), $picnik_config['picnik_replacebutton'], true);
    if ($hide==true) {
        $PIVOTX['extensions']->hide('medialineimage');
    }

    $apikey = getDefault($PIVOTX['config']->get('picnik_apikey'), $picnik_config['picnik_apikey']);
    $session = $_SESSION['pivotxsession'];

    $inurl = $PIVOTX['paths']['host'];
    $outurl = $PIVOTX['paths']['host'].$PIVOTX['paths']['extensions_url']."picnik/post.php";
    $export_title = rawurlencode("Export to your PivotX");

    $output = <<< EOM

<script language="JavaScript" type="text/javascript">
//<![CDATA[

    function openPicnik(path, url) {
        var picnikurl = "http://www.picnik.com/service/?_apikey={$apikey}";
        picnikurl += "&_import={$inurl}" + encodeURIComponent(url);
        picnikurl += "&_export={$outurl}";
        picnikurl += "&_export_agent=browser";
        picnikurl += "&_export_method=POST";
        picnikurl += "&_export_title={$export_title}";
        picnikurl += "&_replace=yes";
        picnikurl += "&_imageid={$session}|" + encodeURIComponent(path);

        var picnik = window.open(picnikurl,'picnik', 'width=950,height=680,scrollbars=yes,toolbar=no,location=yes,menubar=no,directories=no,bookmarks=no');


    }

//]]>
</script>

EOM;

    return $output;


}


/**
 * The 'edit in Picnik' button that is added next to each image.
 *
 * @param array $value
 * @return string
 */
function picnikHookMediaImage($value) {

    $output = sprintf('<a href="#" onclick="return openPicnik(\'%s\', \'%s\');">
        <img src="extensions/picnik/picnik.png" alt="" />%s</a>',
          base64_encode(str_replace(".original.", ".", $value['fullpath'])),
          $value['url'], __('Edit in Picnik')
        );

    return $output;

}



/**
 * The configuration screen for Picnik
 *
 * @param unknown_type $form_html
 */
function picnikAdmin(&$form_html) {
    global $picnik_config, $PIVOTX;


    $form = $PIVOTX['extensions']->getAdminForm('picnik', 'Picnik');


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'picnik_keeporiginal',
        'label' => __('Keep original'),
        'text' => __('Yes, keep the original image after editing it in Picnik')
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'picnik_replacebutton',
        'label' => __('Hide buttons'),
        'text' => __("Yes, replace the original 'edit' button for images")
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'picnik_apikey',
        'label' => __('Picnik API key'),
        'value' => '',
        'error' => __('That\'s not a proper API key!'),
        'text' => __('If you have your own picnik API key, you may enter it here. <br /> ' . 
            "Leave this blank if you're not sure."),
        'size' => 36,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=32|maxlen=32'
    ));

    $host = $_SERVER['HTTP_HOST'];
    if ( $host=="localhost" || (substr($host,0,5)=="10.0.") || (substr($host,0,6)=="127.0.")  || (substr($host,0,8)=="192.168.") ) {

        $form->add( array(
            'type' => 'info',
            'text' => "<div class='warning'><p><strong>" . __('Note') . ':</strong> ' .
                __("It seems you're running PivotX from a local network. The Picnik extension<br /> " .
                "needs to be able to fetch the images over the internet, so it might not work on<br /> " .
                "your current setup.") . "</p></div>"
        ));

    }


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['picnik'] = $PIVOTX['extensions']->getAdminFormHtml($form, $picnik_config);


}


?>

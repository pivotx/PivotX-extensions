<?php
// - Extension: Media Extension
// - Version: 0.3.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet extension to add degradable audio, video and youtube movies to your pages.
// - Date: 2014-01-25
// - Identifier: media
// - Required PivotX version: 2.0.2

global $media_config;

$media_config = array();

$media_config['infobox'] = <<< EOM
<div class="pivotx-media-%mediatype%-infobox">
    <p>%description%</p>
</div>
EOM;

$media_config['audio'] = <<< EOM
%infobox%
<div id="audioplayerholder%counter%" class="pivotx-media">
    <p><a href="%filename%">Download <tt>%basename%</tt>.</a></p>
    <p>%description%</p>
</div>
<script type="text/javascript">
    var flashvars = { playerID: "%counter%", soundFile: "%filename%"  };
    var params = { wmode: "transparent" }
    var attributes = { id: "audioplayer%counter%" };
    swfobject.embedSWF("%mediaplayerpath%audioplayer.swf", "audioplayerholder%counter%", "290", "24", "9.0.0", "%mediaplayerpath%expressInstall.swf", flashvars, params, attributes);
</script>
EOM;

$media_config['youtube'] = <<< EOM
%infobox%
<div id="youtubeholder%counter%" class="pivotx-media">
    <p><a href="%filename%">Visit this page on YouTube.</a></p>
    <p>%description%</p>
</div>
<script type="text/javascript">
    var flashvars = { };
    var params = { wmode: "transparent", allowfullscreen: "true" }
    var attributes = { };
    swfobject.embedSWF("%filename%", "youtubeholder%counter%", "%width%", "%height%", "9.0.0", "%mediaplayerpath%expressInstall.swf", flashvars, params, attributes);
</script>
EOM;

$media_config['vimeo'] = <<< EOM
%infobox%
<div id="vimeoholder%counter%" class="pivotx-media">
    <p><a href="%filename%">Visit this page on Vimeo.</a></p>
    <p>%description%</p>
</div>
<script type="text/javascript">
    var flashvars = { };
    var params = { wmode: "transparent", allowfullscreen: "true" }
    var attributes = { };
    swfobject.embedSWF("%filename%", "vimeoholder%counter%", "%width%", "%height%", "9.0.0", "%mediaplayerpath%expressInstall.swf", flashvars, params, attributes);
</script>
EOM;

$media_config['video'] = <<< EOM
<!-- PivotX video: %filename% -->
%infobox%
<div id="videoholder%counter%" class="pivotx-media">
    <p><a href="%filename%">Download this video.</a></p>
    <p>%description%</p>
</div>
<script type="text/javascript">
    var flashvars = { file: "%filename%", image: "%image%" };
    var params = { wmode: "transparent", allowfullscreen: "true" }
    var attributes = {  };
    swfobject.embedSWF("%mediaplayerpath%videoplayer.swf", "videoholder%counter%", "%width%", "%height%", "9.0.0", "%mediaplayerpath%expressInstall.swf", flashvars, params, attributes);
</script>
EOM;



// Register 'media' as a smarty tag.
$PIVOTX['template']->register_function('audio', 'smarty_media_audio');
$PIVOTX['template']->register_function('video', 'smarty_media_video');
$PIVOTX['template']->register_function('youtube', 'smarty_media_youtube');
$PIVOTX['template']->register_function('vimeo', 'smarty_media_vimeo');
    



/**
 * Output the media audio markup, that's transformed into a nice degradable player..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_media_audio($params, &$smarty) {
    global $PIVOTX, $media_config; 

    static $count = 0;
    $count++;

    $values = array();
    $values['count'] = $count;
    $values['mediaplayerpath'] = $mediaplayerpath = $PIVOTX['paths']['extensions_url']."media/";

    // If the hooks for the media player includes in the header have not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head',
            "\t<script type=\"text/javascript\" src=\"{$mediaplayerpath}swfobject.js\"></script>");
    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head',
            "\t<script type=\"text/javascript\" src=\"{$mediaplayerpath}audioplayer.js\"></script>");

    $params = cleanParams($params);

    $values['description'] = getDefault($params['description'], __("Playing \"%nicefilename%\".") );
    $values['useinfobox'] = $params['useinfobox'];
      
    if (!empty($params['url'])) {
        $filename = $params['url'];
    } else if (!empty($params['file']) && file_exists( $PIVOTX['paths']['site_path']. $params['file'])) {
        $filename = fixPath($PIVOTX['paths']['site_url'].$params['file'], true);
    } else if (!empty($params['file']) && file_exists( $PIVOTX['paths']['upload_base_path']. $params['file'])) {
        $filename = fixPath($PIVOTX['paths']['upload_base_url'].$params['file'], true);
    } else if (!empty($params['file']) ) {
        $filename = fixPath($params['file'], true);
    } else {
        return "<p>Error: No file or URL specified!</p>";
    }
    $values['filename'] = $filename;
    
    $output = _media_output('audio', $values);
    
    return $output;

}




/**
 * Output the media youtube markup, that's transformed into a nice degradable player..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_media_youtube($params, &$smarty) {
    global $PIVOTX, $media_config; 

    static $count = 0;
    $count++;

    $values = array();
    $values['count'] = $count;
    $values['mediaplayerpath'] = $mediaplayerpath = $PIVOTX['paths']['extensions_url']."media/";

    // If the hooks for the media player includes in the header have not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head',
            "\t<script type=\"text/javascript\" src=\"{$mediaplayerpath}swfobject.js\"></script>");

    $params = cleanParams($params);

    $values['description'] = getDefault($params['description'], __("Youtube video \"%basename%\".") );
    $values['width'] = getDefault($params['width'], 425 );
    $values['height'] = getDefault($params['height'], 355 );
    $values['useinfobox'] = $params['useinfobox'];
    
    if (!empty($params['url'])) {
        $filename = $params['url'];
    } else if (!empty($params['file']) && file_exists( $PIVOTX['paths']['site_path']. $params['file'])) {
        $filename = fixPath($PIVOTX['paths']['site_url'].$params['file'], true);
    } else if (!empty($params['file']) ) {
        $filename = fixPath($params['file'], true);
    } else {
        return "<p>Error: No file or URL specified!</p>";
    }
    $values['filename'] = $filename;
     
    $output = _media_output('youtube', $values);

    return $output;

}


/**
 * Output the media vimeo markup, that's transformed into a nice degradable player..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_media_vimeo($params, &$smarty) {
    global $PIVOTX, $media_config; 

    static $count = 0;
    $count++;

    $values = array();
    $values['count'] = $count;
    $values['mediaplayerpath'] = $mediaplayerpath = $PIVOTX['paths']['extensions_url']."media/";

    // If the hooks for the media player includes in the header have not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head',
            "\t<script type=\"text/javascript\" src=\"{$mediaplayerpath}swfobject.js\"></script>");

    $params = cleanParams($params);

    $values['description'] = getDefault($params['description'], __("Vimeo video \"%basename%\".") );
    $values['width'] = getDefault($params['width'], 400 );
    $values['height'] = getDefault($params['height'], 300 );
    $values['useinfobox'] = $params['useinfobox'];
    
    if (!empty($params['url'])) {
        $filename = $params['url'];
    } else if (!empty($params['file']) && file_exists( $PIVOTX['paths']['site_path']. $params['file'])) {
        $filename = fixPath($PIVOTX['paths']['site_url'].$params['file'], true);
    } else if (!empty($params['file']) ) {
        $filename = fixPath($params['file'], true);
    } else {
        return "<p>Error: No file or URL specified!</p>";
    }
    $values['filename'] = $filename;
    
    $output = _media_output('vimeo', $values);

    return $output;

}


/**
 * Output the media video markup, that's transformed into a nice degradable player..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_media_video($params, &$smarty) {
    global $PIVOTX, $media_config; 

    static $count = 0;
    $count++;

    $values = array();
    $values['count'] = $count;
    $values['mediaplayerpath'] = $mediaplayerpath = $PIVOTX['paths']['extensions_url']."media/";

    // If the hooks for the media player includes in the header have not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head',
            "\t<script type=\"text/javascript\" src=\"{$mediaplayerpath}swfobject.js\"></script>");

    $params = cleanParams($params);

    $values['description'] = getDefault($params['description'], __("Video \"%nicefilename%\"") );
    $values['image'] = getDefault($params['image'], $mediaplayerpath."screen.jpg");
    $values['width'] = getDefault($params['width'], 400 );
    $values['height'] = getDefault($params['height'], 300 );
    $values['useinfobox'] = $params['useinfobox'];
    
    if (!empty($params['file'])) {
        $filename = fixPath($PIVOTX['paths']['site_url'].$params['file'], true);
    } else if (!empty($params['url'])) {
        $filename = $params['url'];
    } else {
        return "<p>Error: No file or URL specified!</p>";
    }
    
    if (file_exists( $PIVOTX['paths']['site_path']. $filename)) {
        $filename = fixPath($PIVOTX['paths']['site_url'].$filename, true);
    } 
    $values['filename'] = $filename;
    
    $output = _media_output('video', $values);

    // return nl2br(htmlentities($output));
    return $output;

}

function _media_output($mediatype, $values) {
    global $media_config;

    $values['basename'] = basename($values['filename']);
    $values['nicefilename'] = formatFilename($values['filename']);

    $output = $media_config[$mediatype];
    if ($values['useinfobox']) {
        $output = str_replace('%infobox%', $media_config['infobox'], $output);
    } else {
        $output = str_replace('%infobox%', '', $output);
    }
    $output = str_replace('%mediatype%', $mediatype, $output);
    $output = str_replace('%description%', $values['description'], $output);
    $output = str_replace('%basename%', $values['basename'], $output);
    $output = str_replace('%filename%', $values['filename'], $output);
    $output = str_replace('%nicefilename%', $values['nicefilename'], $output);
    $output = str_replace('%mediaplayerpath%', $values['mediaplayerpath'], $output);
    $output = str_replace('%counter%', $values['count'], $output);
    $output = str_replace('%width%', $values['width'], $output);
    $output = str_replace('%height%', $values['height'], $output);
    $output = str_replace('%image%', $values['image'], $output);

    return $output;
}
 

?>

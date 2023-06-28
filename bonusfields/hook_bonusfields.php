<?php
// - Extension: Bonus Fields
// - Version: 1.33
// - Author: Two Kings // Marcel Wouters // Harm Kramer did the last version
// - Email: marcel@twokings.nl
// - Site: http://www.twokings.nl/
// - Description: Adding custom extrafields to entries and pages
// - Date: 2013-11-30
// - Identifier: bonusfields

$this->addHook(
    'configuration_add',
    'bonusfields',
    array('functionalCallBonusfieldsConfigurationAdd', 'Bonus Fields')
);

$this->addHook(
    'modify_pivotx_menu',
    'callback',
    'functionalCallBonusfieldsMPM'
);

$this->addHook(
    'entry_edit_addsearchtext',
    'callback',
    'functionalCallBonusfieldsEntryAddSearchText'
);

$this->addHook(
    'page_edit_addsearchtext',
    'callback',
    'functionalCallBonusfieldsPageAddSearchText'
);

$this->addHook(
    'page_edit_aftersave',
    'callback',
    'functionalCallBonusfieldsPageAfterSave'
);

$this->addHook(
    'entry_edit_aftersave',
    'callback',
    'functionalCallBonusfieldsEntryAfterSave'
);

bonusfieldsConfig::instance()->addAllHooks($this);

bonusfieldsConfig::instance()->addSmartyTags();


/**
 * functional style hook for configuration_add
 */
function functionalCallBonusfieldsConfigurationAdd(&$form_html)
{
    if (($_SERVER['REQUEST_METHOD'] == 'POST') && ($_POST['original_fieldkey'] != '')) {
        pivotxBonusfieldsInterface::postBonusfield();

        Header('Location: ?page=configuration#section-bonusfields');
        exit();
    }
    if (isset($_GET['action']) && ($_GET['action'] != '')) {
        pivotxBonusfieldsInterface::actionBonusfield($_GET['action']);

        Header('Location: ?page=configuration#section-bonusfields');
        exit();
    }

    return pivotxBonusfieldsInterface::adminTab($form_html);
}

/**
 * functional style hook for modify_pivotx_menu
 */
function functionalCallBonusfieldsMPM($_menu)
{
    if (bonusfieldsConfig::instance()->getParentFieldkey() != false) {
        $menu = &$_menu[0];

        $new_items = array(
                'menu' => array(
                    array(
                        'sortorder' => 4500,
                        'uri' => 'hierarchy',
                        'name' => 'Hierarchy',
                        'description' => '',
                    )
                )
        );

        modifyMenu($menu,'entries',$new_items);
    }
}

/**
 * Grmpfl. Need something functional here.
 */
function functionalCallBonusfieldsHook($subject, $arguments=false) {
    if (version_compare(PHP_VERSION, '5.2.5') >= 0) {
        $trace = @debug_backtrace(false);
    }
    else {
        $trace = @debug_backtrace();
    }

    $position = $trace[1]['args'][0];

    //echo "RENDER HOOK: ".$position."<br/>\n";

    bonusfieldsConfig::instance()->renderHooks($position,$subject,$arguments);

}

/**
 */
function functionalCallBonusfieldsEntryAddSearchText($target)
{
    return bonusfieldsConfig::instance()->addSearchText($target,'entry');
}

/**
 */
function functionalCallBonusfieldsPageAddSearchText($target)
{
    return bonusfieldsConfig::instance()->addSearchText($target,'page');
}

/**
 */
function functionalCallBonusfieldsPageAfterSave($target)
{
    return bonusfieldsConfig::instance()->taxonomyAfterSave($target,'page');
}

/**
 */
function functionalCallBonusfieldsEntryAfterSave($target)
{
    return bonusfieldsConfig::instance()->taxonomyAfterSave($target,'entry');
}


/**
 * The PivotX interface class
 */
class pivotxBonusfieldsInterface {
    protected static $config = false;

    /**
     * Save the changed bonusfield definitions from a POST
     */
    public static function postBonusfield()
    {
        $instance = bonusfieldsConfig::instance();

        $instance->readConfiguration();

        $field = new bonusfieldsDefinition();

        $fieldkey = $_POST['original_fieldkey'];
        if ($fieldkey == 'newnewnew') {
            $fieldkey = '';
        }
        unset($_POST['original_fieldkey']);
        foreach($_POST as $key => $value) {
            $field->$key = $value;
        }

        list($ct,$x) = explode('-',$field->location);
        $field->contenttype = $ct;

        $instance->saveField($fieldkey,$field->contenttype,$field);

        $instance->writeConfiguration();
    }

    /**
     * Perform various actions on bonusfields
     *
     * @param string $action   action to perform
     */
    public static function actionBonusfield($action)
    {
        $instance = bonusfieldsConfig::instance();

        $instance->readConfiguration();

        switch ($action) {
            case 'smartadd':
                    $def = new bonusfieldsDefinition();
                    $a = explode(';',$_GET['field']);
                    foreach($a as $b) {
                        list($k,$v) = explode('=',$b);
                        $def->$k = $v;
                    }
                    list($ct,$x) = explode('-',$def->location);
                    $def->contenttype = $ct;

                    if ($def->type == 'select') {
                        global $PIVOTX;
                        $db = new sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'), $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
                        $sql = 'select distinct(value) from '.$PIVOTX['config']->get('db_prefix').'extrafields where contenttype='.$db->quote($ct).' and fieldkey='.$db->quote($def->fieldkey).' order by value';
                        $def->data = "\n";
                        $db->query($sql);
                        while ($row = $db->fetch_row()) {
                            $line = preg_replace('|[\r\n]|s',' ',$row['value']);
                            if ($line != '') {
                                $def->data .= $line . "\n";
                            }
                        }
                    }
                    $instance->saveField($def->fieldkey,$def->contenttype,$def);
                    break;

            case 'delete':
                    $instance->deleteField($_GET['fieldkey'],$_GET['contenttype']);
                    break;

            case 'duplicate':
                    $instance->duplicateField($_GET['fieldkey'],$_GET['contenttype']);
                    break;

            case 'setorder':
                    $instance->setFieldOrder($_GET['order']);
                    $instance->writeConfiguration();
                    exit();
                    break;
        }

        $instance->writeConfiguration();
    }

    /**
     * Build the PivotX admin tab
     *
     * @param mixed &$form_html
     */
    public static function adminTab(&$form_html) {
        global $PIVOTX;

        $form = $PIVOTX['extensions']->getAdminForm('bonusfields');

        $instance = bonusfieldsConfig::instance();

        $instance->readConfiguration();

        $help_text = '';
        foreach($instance->help_types as $type => $help) {
            if (strip_tags($help) == $help) {
                $help = '<p>' . $help . '</p>';
            }
            $help_text .= "\t\t\t\t".'case \'' . $type . '\':'."\n";
            $help_text .= "\t\t\t\t\tbfSetHelp('".$help."');\n";
            $help_text .= "\t\t\t\t\tbreak;\n";
        }

        $langcontext_positions    = '<ul>';
        $nonlangcontext_positions = '<ul>';
        foreach($instance->positions as $position => $description) {
            if ($description != '') {
                list($content,$subposition) = explode('-',$position,2);
                if ($instance->isMultilingualPosition($subposition)) {
                    $langcontext_positions .= '<li><p style="margin: 0; padding: 0">'.$description.'</p></li>';
                }
                else {
                    $nonlangcontext_positions .= '<li><p style="margin: 0; padding: 0">'.$description.'</p></li>';
                }
            }
        }
        $langcontext_positions    .= '</ul>';
        $nonlangcontext_positions .= '</ul>';


        $output = '';

        //$output .= '<pre>' . var_export($instance,true) . '</pre>';

        $output .= $instance->getConfigurationTableHtml();

        $output .= '<a name="bonusfieldfocus"></a>';

        $output .= $instance->getEditFormHtml();

        $output .= <<<THEEND

<style type="text/css">

.bonusfields_editform .formclass input[type="text"],
.bonusfields_editform .formclass select,
.bonusfields_editform .formclass textarea {
    width: 380px;
}

</style>

<script type="text/javascript">

function bfSetHelpText(text)
{
    $('.actualhelptext').html('<p>'+text+'</p>');
}

function bfSetHelp(html)
{
    $('.actualhelptext').html(html);
}

function bfSetFocus()
{
    var loc = new String(document.location);

    var pos = loc.indexOf('#');
    if (pos > 0) {
        loc = loc.substring(0,pos);
        document.location = loc + '#bonusfieldfocus';
    }
}

function bfRebindInputs()
{
    $('form#bonusfields input').unbind('change').unbind('blur');
    $('form#bonusfields textarea').unbind('change').unbind('blur');
    $('form#bonusfields select').unbind('change').unbind('blur');
}

function bfSaveEditForm(target_el)
{
    var fieldkey = '';

    var form_el = $(target_el).closest('.bonusfields_editform');
    var cs = new String($(form_el).attr('class'));
    var idx = cs.indexOf('bffieldkey_');

    if (idx > 0) {
        fieldkey = cs.substring(idx + 11);
    }
    else {
        return;
    }

    var values = {};

    $('input, textarea, select',form_el).each(function(){
        var key = $(this).attr('name');
        var value = $(this).val();

        values[key] = value;
    });

    $(form_el).submit();
}

function bfEditButton(fieldkey,contenttype)
{
    $('#smartfields').hide();
    $('.bonusfields_editform').hide();

    $('.bffieldkey_' + fieldkey + '_' + contenttype).show();

    var div_el = $('.bffieldkey_' + fieldkey + '_' + contenttype);
    $('div.bonusfields_help',div_el).height($('table',div_el).height());

    if (fieldkey == '') {
        bfSetHelpText('Add a new bonusfield.');
    }
    else {
        bfSetHelpText('Edit bonusfield "' + fieldkey + '".');
    }
    $('#name',div_el).bind('focus',function(e){ bfSetHelpText('Enter name of the bonusfield. Visible to the PivotX user.'); });
    $('#fieldkey',div_el).bind('focus',function(e){ bfSetHelpText('Enter the extrafields key value. Only use characters a-z, 0-9 or _.'); });
    $('#type',div_el).bind('focus',function(e){
        bfSetHelp(
            '<p>Here you can set the type of bonusfield. Some special types:</p>'+
            '<p>'+
                '<strong>wysiwyg editor</strong>: textarea with the default pivotx-wysiwyg editor on top<br/>'+
                '<strong>choose page</strong>: link to another pivotx-page<br/>'+
                '<strong>choose entry</strong>: link to another pivotx-entry<br/>'+
                '<br/>'+
                '<strong>view separator (line)</strong>: add a separator line between input\'s<br/>'+
                '<strong>view extrafield as text</strong>: view the text contents of the extrafield, handy for statistics inside an extrafield<br/>'+
                '<strong>view extrafield as html</strong>: view the html contents of the extrafield<br/>'+
            '</p>'
        );
    });
    $('#location',div_el).bind('focus',function(e){ bfSetHelpText(
            '<p>Location of the extrafield in the pivotx editor.<br/></p>'+
            '<p>These fields have values for each language:</p>'+
            '$langcontext_positions'+
            '<p><br/>These fields have only one value:</p>'+
            '$nonlangcontext_positions'
    ); });
    $('#showif',div_el).bind('focus',function(e){ bfSetHelpText('Show field only if certain conditions are right.'); });
    $('#data',div_el).bind('focus',function(e){
        var val = $('#type',div_el).val();

        if ((val == 'select') || (val == 'radio') || (val == 'select_multiple') || (val == 'checkbox_multiple')) {
            bfSetHelp(
                '<p>Here you can enter all the options for this extrafield.</p>'+
                '<p>Different options are entered as follows:</p>'+
                '<pre style="color: #000">'+
                    '[value] :: [description]<br/>'+
                    '[value] :: [description]'+
                '</pre>'+
                '<p>Alternatively, if you\'re using an SQL backend:</p>'+
                '<pre style="color: #000">'+
                    'SQL:<br/>'+
                    'select uid as value, title as label, user as optgroup from pivotx_entries order by user,publish_date desc<br/>'+
                    '<br/>'+
                    '-- "optgroup" is optional, if you use it, make sure the ordering orders the "optgroup" first<br/>'+
                    '-- you can enter an extra option for empty by entering on a seperate line the value "empty"<br/>'+
                    '&#160;&#160; followed by "::" and a text'+
                '</pre>'+
                '<p>Or you can specify a callback</p>'+
                '<pre style="color: #000">'+
                    'php: callback-function<br/>'+
                    '<br/>'+
                    'the callback should be passed, exactly like call_user_func_array() wants it too.<br/>'+
                    'the callback is passed an object of type bonusfieldsDefinition which contains the field-definition.<br/>'+
                    'the callbacks should return an indexed array, which contains associative arrays with '+
                    'keys "optgroup", "value" and "label" (optgroup optional).<br/>'+
                    '<br/>'+
                    'example setup:<br/>'+
                    'php: countryOptionsCallback<br/>'+
                    '<br/>'+
                    'corresponding php-code:<br/>'+
                    'function countryOptionsCallback($'+'definition)<br/>'+
                    '{<br/>'+
                    '    $'+'options = array();<br/>'+
                    '    $'+'options'+'[] = array( "label" => "(choose country)", "value" => "" );<br/>'+
                    '    $'+'options'+'[] = array( "label" => "Netherlands", "value" => "nl" );<br/>'+
                    '    $'+'options'+'[] = array( "label" => "United Kingdom", "value" => "uk" );<br/>'+
                    '    return $'+'options;<br/>'+
                    '}<br/>'+
                '</pre>'
            );
        }
        else {
            switch (val) {
$help_text
                default:
                    bfSetHelpText('This field has no purpose with this type of extrafield.');
                break;
            }
        }
    });
    $('#description',div_el).bind('focus',function(e){ bfSetHelpText('You can add an additional help text that appears near the input box.'); });
    $('#taxonomy',div_el).bind('focus',function(e){ bfSetHelpText('Enter the name of the taxonomy here'); });

    bfSetFocus();
}

function bfDuplicateButton(fieldkey,contenttype)
{
    var loc = '?page=configuration';

    var pos = loc.indexOf('#');
    if (pos > 0) {
        loc = loc.substring(0,pos);
    }

    if (loc.indexOf('?') >= 0) {
        loc += '&';
    }
    else {
        loc += '?';
    }
    loc += 'action=duplicate';
    loc += '&fieldkey=' + escape(fieldkey);
    loc += '&contenttype=' + escape(contenttype);

    loc += '#section-bonusfields';

    document.location = loc;
}


function bfAddButton()
{
    bfEditButton('','');
}

function bfSmartAddButton()
{
    if ($('#smartfields').is(':visible')) {
        $('#smartfields').hide();
    }
    else {
        $('.bonusfields_editform').hide();
        $('#smartfields').show();

        bfSetFocus();
    }
}

function bfSaveOrder()
{
    var order = [];

    $('.bonusfieldrows td span.fieldid').each(function(){
        var fieldkey = $(this).text();

        order[order.length] = fieldkey;
    });

    var loc = new String(document.location);

    var pos = loc.indexOf('#');
    if (pos) {
        loc = loc.substring(0,pos);
    }

    if (loc.indexOf('?') >= 0) {
        loc += '&';
    }
    else {
        loc += '?';
    }

    loc += 'page=configuration&action=setorder&order=';
    loc += order.join(',');
    loc += '#section-bonusfields';

    $.get(loc, function(data){
        var cnt = 1;

        $('.bonusfieldrows tr').each(function(){
            if ((cnt % 2) == 1) {
                $(this).removeClass().addClass('odd');
            }
            else {
                $(this).removeClass().addClass('even');
            }

            var txt = cnt;
            $('td.position span.v',this).text(txt);
            cnt++;
        });
    });
}

function bfDeleteButton(fieldkey,contenttype)
{
    if (!confirm('Are you sure you want to delete field "' + fieldkey + '"?')) {
        return false;
    }

    //var loc = new String(document.location);
    var loc = '?page=configuration';

    var pos = loc.indexOf('#');
    if (pos > 0) {
        loc = loc.substring(0,pos);
    }

    if (loc.indexOf('?') >= 0) {
        loc += '&';
    }
    else {
        loc += '?';
    }
    loc += 'action=delete';
    loc += '&fieldkey=' + escape(fieldkey);
    loc += '&contenttype=' + escape(contenttype);

    loc += '#section-bonusfields';

    document.location = loc;
}

var sanity_count = 0;
function updateExtraSearchTextsAgain(data)
{
    sanity_count++;
    if (sanity_count > 50) {
        return;
    }

    var txt = 'Update searchindex';
    if (data != 'done') {
        jQuery.ajax({
            type: 'POST',
            url: 'ajaxhelper.php',
            data: {
                "function": 'updateExtraSearchTexts',
                phase: data
            },
            success: function(newdata){
                if (newdata != data) {
                    updateExtraSearchTextsAgain(newdata);
                }
                else {
                    alert('Internal error');
                }
            }
        });


        txt  = '<strong>Updating..';
        for(var i=0; i < data; i++) {
            txt += '.';
        }
        txt += '</strong>';
    }
    else {
        sanity_count = 0;
    }
    jQuery('#bonusfield_updateindex').html(txt);
}

jQuery(function($){
    setTimeout('bfRebindInputs()',500);

    $('form#bonusfields').bind('submit',function(e){
        return true;
        //e.preventDefault();
    });
    $('form#bonusfields button').bind('click',function(e){
        bfSaveEditForm(this);

        //e.preventDefault();
    });
    $('#bonusfield_addbutton').bind('click',function(e){
        bfAddButton();
        
        e.preventDefault();
    });
    $('#bonusfield_smartbutton').bind('click',function(e){
        bfSmartAddButton();
        
        e.preventDefault();
    });
    $('#bonusfield_updateindex').bind('click',function(e){
        updateExtraSearchTextsAgain('0');
        
        e.preventDefault();
    });
    $('input.fieldkey').bind('change',function(e){
        var val = new String(jQuery(e.target).val());

        val = val.replace(/[^a-z0-9_]/g,'_');
        val = val.replace(/^[^a-z_]/,'_');
        
        jQuery(e.target).val(val);
    });

    $('.bonusfields_editform').hide();

    $('.bonusfieldrows').sortable({
        handle: '.position',
        update: function(event,ui){
            bfSaveOrder();
        }
    });

    jQuery('form.bonusfieldedit').bind('submit',function(e){
        if (jQuery('input[name="fieldkey"]',this).val() == '') {
            e.preventDefault();

            alert('You have to enter an internal name!');

            jQuery('input[name="fieldkey"]',this).focus();
        }
        if ((jQuery('select[name="type"]',this).val() == 'select_multiple') ||
            (jQuery('select[name="type"]',this).val() == 'checkbox_multiple')) {
            if (jQuery('input[name="taxonomy"]',this).val() == '') {
                e.preventDefault();
                alert('You have to enter a taxonomy!');
                jQuery('input[name="taxonomy"]',this).focus();
            }
        }
        else {
            if (jQuery('input[name="taxonomy"]',this).val() != '') {
                e.preventDefault();
                alert('Taxonomy is not applicable here, I cleared the value!');
                jQuery('input[name="taxonomy"]',this).focus().val('');
            }
        }
    });
});

</script>
THEEND;


        $instance->writeConfiguration();

        $form->add(array(
            'type' => 'custom',
            'text'=> $output
        ));

        $form_html['bonusfields'] = $PIVOTX['extensions']->getAdminFormHtml($form, self::$config);

        return $output;
    }
}

/**
 * Bonusfield definition
 */
class bonusfieldsDefinition {
    /**
     * Actual definition information
     */
    protected $data;


    /**
     * Get the definition value
     *
     * @param string $name  name to retrieve
     * @return mixed        value retrieved
     */
    public function __get($name) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return '';
    }

    /**
     * Set the definition value
     *
     * @param string $name   name to set
     * @param string $value  value to set
     */
    public function __set($name, $value) {
        $this->data[$name] = $value;
    }


    /**
     * Export the definition to an array (for storage)
     *
     * @return array
     */
    public function exportToArray() {
        return $this->data;
    }

    /**
     * Import the definition from an array (from storage)
     *
     * @param array $data
     */
    public function importFromArray($data) {
        $this->data = $data;

        if (!isset($data['contenttype']) || ($data['contenttype'] == '')) {
            // upgrade fix for storing contenttype
            list($ct,$x) = explode('-',$data['location'],2);
            $this->data['contenttype'] = $ct;
        }
    }

    /**
     * Output the html for this item in the configuration table
     *
     * @return string  html to output
     */
    public function getConfigurationRowHtml($position, &$config) {
        $html = '';

        $td_attr = ' class="even"';
        if (($position % 2) == 1) {
            $td_attr = ' class="odd"';
        }

        $src = 'extensions/bonusfields/images/streepjes.gif';

        $html .= '<tr'.$td_attr.'>' . "\n";
        $html .= "\t<td style=\"padding: 5px 0\" class=\"position\" style=\"cursor: pointer;\">&#160;<img src=\"".$src."\" alt=\"stripes\" style=\"vertical-align: middle; margin-right: 7px;\" />№&#160;<span class=\"v\">" . $position . "</span><span class=\"fieldid\" style=\"display: none;\">".$this->fieldkey.':'.$this->contenttype."</span></td>\n";
        $html .= "\t<td style=\"padding: 5px 0\"><a href=\"#\" onclick=\"bfEditButton('".$this->fieldkey."','".$this->contenttype."'); return false;\" style=\"font-weight: bold; color: #eb5e00; text-decoration: none;\">" . htmlspecialchars($this->name) . "</a></td>\n";
        $html .= "\t<td style=\"padding: 5px 0\"><span class=\"fieldkey\">" . htmlspecialchars($this->fieldkey) . "</span></td>\n";
        $html .= "\t<td style=\"padding: 5px 0\">" . $config->types[$this->type] . "</td>\n";
        $html .= "\t<td style=\"padding: 5px 0\">" . $config->positions[$this->location] . "</td>\n";
        $html .= "\t<td style=\"padding: 5px 0\">\n";
        $html .= "\t\t<nobr><a href=\"#\" onclick=\"bfEditButton('".$this->fieldkey."','".$this->contenttype."'); return false;\" title=\"".__("Edit")."\"><img height=\"16\" width=\"16\" alt=\"".__("Edit")."\" src=\"pics/page_edit.png\" style=\"border: 0; margin-right: 2px;\"></a>";
        $html .= "<a href=\"#\" onclick=\"bfDuplicateButton('".$this->fieldkey."','".$this->contenttype."'); return false;\" title=\"".__("Duplicate")."\"><img height=\"16\" width=\"16\" alt=\"".__("Duplicate")."\" src=\"pics/page_lightning.png\" style=\"border: 0; margin-right: 2px;\"></a>";
        $html .= "<a href=\"#\" onclick=\"bfDeleteButton('".$this->fieldkey."','".$this->contenttype."'); return false;\" title=\"".__("Delete")."\"><img height=\"16\" width=\"16\" alt=\"".__("Delete")."\" src=\"pics/page_delete.png\" style=\"border: 0\"></a></nobr>\n";
        $html .= "\t</td>\n";
        $html .= '</tr>' . "\n";

        return $html;
    }

    /**
     * Decode data field into options
     */
    protected function decodeOptions($data) {
        global $PIVOTX;

        $options = array();

        if (strtolower(substr($data,0,4)) == 'sql:') {
            $sql  = trim(substr($data,4));
            $eopt = false;
            if (preg_match('|empty[ \t]*::([^\r\n]*)|',$sql,$match)) {
                $eopt = array ( 'value' => '', 'label' => trim($match[1]) );
                $sql  = str_replace($match[0],'',$sql);
            }

            if ($eopt) {
                $options[] = $eopt;
            }

            $db = $PIVOTX['db']->db_lowlevel->sql;
            $db->query($sql);

            while ($row = $db->fetch_row()) {
                if (!isset($row['optgroup'])) {
                    $row['optgroup'] = false;
                }

                $options[] = $row;
            }
        }
        else if (strtolower(substr($data,0,4)) == 'php:') {
            $callback = false;
            if (preg_match('|php: *([^ ]+)|',$data,$match)) {
                $callback = $match[1];

                if (preg_match('|(.+)::(.+)|',$callback,$match)) {
                    $callback = array($match[1],$match[2]);
                }
            }

            $do_callback = false;
            if (($callback !== false) && is_scalar($callback) && (function_exists($callback))) {
                $do_callback = true;
            }
            else if (($callback !== false) && is_array($callback) && (method_exists($callback[0],$callback[1]))) {
                $do_callback = true;
            }
            $options = array();
            if (($callback !== false) && ($do_callback === true)) {
                $options = call_user_func_array($callback,array($this));
            }
        }
        else {
            $lines = preg_split("|[\r\n]+|",$data);

            $optgroup = false;
            foreach($lines as $line) {
                list($value,$description) = explode('::',trim($line));
                $value       = trim($value);
                $description = trim($description);

                if ($value == 'OPTGROUP') {
                    $optgroup = $description;
                }
                else if (($value != '') && ($description != '')) {
                    if ($value == '##EMPTY##') {
                        // hate myself for doing this actually
                        // this is deprecated
                        $value = '';
                    }
                    $options[] = array(
                        'value' => $value,
                        'label' => $description,
                        'optgroup' => $optgroup,
                    );
                }
                else if (($description == '') && ($value != '')) {
                    $options[] = array(
                        'value' => $value,
                        'label' => $value,
                        'optgroup' => $optgroup,
                    );
                }
            }
        }

        return $options;
    }

    /**
     * Add the hook
     *
     * @param mixed &$extensions
     */
    public function addHook(&$extensions) {
        //echo '<pre style="background-color: #fff; z-index: 10000;">ADDING HOOK'; var_dump($this->location); echo '</pre>';
        $extensions->addHook(
            'in_pivotx_template',
            $this->location,
            array('callback' => 'functionalCallBonusfieldsHook')
        );
        $extensions->addHook(
            'in_pivotx_template',
            'ml'.$this->location,
            array('callback' => 'functionalCallBonusfieldsHook')
        );
    }

    /**
     * Render the hook
     *
     * @param string $subject  source entry or page
     */
    public function renderHook($subject, $arguments=false) {
        global $PIVOTX;
        static $is_first = true;

        $pre_content = '';
        if ($is_first) {
            $is_first = false;

            $fname  = dirname(__FILE__) . '/templates/shared.itpl';

            $pre_content = file_get_contents($fname);
        }

        $prefix = 'main';
        $header = bonusfieldsRender::get_main_item_header();
        $footer = bonusfieldsRender::get_main_item_footer();

        if (strstr($this->location,'-category-before') ||
            strstr($this->location,'-chapter-before') ||
            strstr($this->location,'-code-after')) {
                $prefix = 'sidebar';
                $header = bonusfieldsRender::get_sidebar_item_header();
                $footer = bonusfieldsRender::get_sidebar_item_footer();
        }

        if (!isset($arguments['language'])) {
            list($_subject,$_subposition) = explode('-',$this->location,2);
            if (bonusfieldsConfig::instance()->isMultilingualPosition($_subposition)) {
                $arguments['language'] = Multilingual::instance()->getDefaultLanguage();
            }
        }

        $actual_type = preg_replace('|[^a-z0-9]|','_',$this->type);
        $actual_data = $this->data['data'];

        if (method_exists('bonusfieldsInputSpecials',$this->type)) {
            list($actual_type,$actual_data) = bonusfieldsInputSpecials::$actual_type($this);

            //* 
            // this is deprecated
            if (($actual_type == 'select') && (substr(strtolower(trim($this->data['data'])),0,7) == 'empty::')) {
                $value = trim(substr(trim($this->data['data']),7));
                if ($value == '') {
                    $value = '(none)';
                }
                $actual_data = '##EMPTY##::'.$value."\n" . trim($actual_data);
            }
            //*/
        }

        $fname  = dirname(__FILE__) . '/templates/' . $prefix . '-';
        $fname .= $actual_type . '.itpl';
        if (!file_exists($fname)) {
            return 'Type "'.$this->type.'" on location "'.$this->location.'" not supported. Filename "'.$fname.'".<br/>';
            return 'Type "'.$this->type.'" on location "'.$this->location.'" not supported.<br/>';
        }

        $content = file_get_contents($fname);


        // get the current value
        $current_value = '';
        $fieldkey      = $this->fieldkey;

        if (isset($arguments['language'])) {
            $fieldkey = $arguments['language'].'__'.$fieldkey;
        }
        if (isset($subject['extrafields']) && (is_array($subject['extrafields']))) {
            if (isset($subject['extrafields'][$fieldkey])) {
                $current_value = $subject['extrafields'][$fieldkey];
            }
        }


        // build some very special replacements
        $options     = $this->decodeOptions($actual_data);
        if (($actual_type == 'select') && (trim($this->data['empty_text']) != '')) {
            array_unshift($options,array(
                'value' => '',
                'label' => trim($this->data['empty_text']),
                'optgroup' => '',
            ));
        }


        // taxonomy
        if ((in_array($this->type, array('select_multiple','checkbox_multiple'))) && ($this->taxonomy != '')) {
            $db     = bonusfieldsConfig::getDatabase();
            $prefix = $PIVOTX['config']->get('db_prefix');

            $db->query('select * from '.$prefix.'taxonomies where contenttype='.$db->quote($this->contenttype).' and taxonomy='.$db->quote($this->taxonomy).' and target_uid='.$subject['uid']);

            $current_value = array();
            while ($rec = $db->fetch_row()) {
                $current_value[] = $rec['name'];
            }
        }


        $html_select     = '';
        $html_radio      = '';
        $html_checkboxes = '';
        $in_optgroup     = false;
        $optgroup        = false;
        static $anchor_counter = 1000;
        foreach($options as $option) {
            $x_select = '';
            $x_radio  = '';
            if (is_scalar($current_value)) {
                if ($option['value'] == $current_value) {
                    $x_select = ' selected="selected"';
                    $x_radio  = ' checked="checked"';
                }
            }
            else if (is_array($current_value)) {
                if (in_array($option['value'],$current_value)) {
                    $x_select = ' selected="selected"';
                    $x_radio  = ' checked="checked"';
                }
            }

            if ($html_radio != '') {
                $html_radio .= ', ';
            }

            if (isset($option['optgroup']) && ($option['optgroup'] != $optgroup)) {
                if ($in_optgroup) {
                    $html_select .= '</optgroup>';
                }

                $html_select .= '<optgroup label="'.($option['optgroup']).'">';
                $in_optgroup  = true;
                $optgroup     = $option['optgroup'];
            }
            $html_select .= '<option value="'.($option['value']).'"'.$x_select.'>'.($option['label']).'</option>';

            $html_radio  .= '<label><input type="radio" name="extrafields['.$fieldkey.']" value="'.($option['value']).'"'.$x_radio.'> '.($option['label']) .'</label>';

            $html_checkboxes .= '<label id="anchor-'.$anchor_counter.'"><input type="checkbox" name="extrafields['.$fieldkey.'][]" value="'.($option['value']).'"'.$x_radio.'> '.($option['label']) .'</label>';
            $anchor_counter++;
        }
        if ($in_optgroup) {
            $html_select .= '</optgroup>';
        }
        if (is_scalar($current_value)) {
            $text_value = htmlspecialchars($current_value);
            $html_value = $current_value;
        }
        else if (is_array($current_value)) {
            $text_value = implode(', ',$current_value);
            $html_value = implode(', ',$current_value);
        }
        else {
            $text_value = '';
            $html_value = '';
        }

        $check_value = 'on';
        if (count($lines) > 0) {
            $check_value = trim($lines[0]);
        }
        $html_checked = '';
        if (($check_value != '') && ($current_value == $check_value)) {
            $html_checked = ' checked="checked"';
        }

        if (!strstr($html_radio,' checked="checked"')) {
            // if none is selected, by default we select the first one
            $html_radio = preg_replace('|<input type="radio" |','<input type="radio" checked="checked" ',$html_radio,1);
        }


        // header/footer
        $content = str_replace('%header%',$header,$content);
        $content = str_replace('%footer%',$footer,$content);

        // replace the basic stuff
        $content = str_replace('%name%',$this->name,$content);
        $content = str_replace('%description%',$this->description,$content);
        $content = str_replace('%fieldkey%',$fieldkey,$content);
        $content = str_replace('%text_value%',$text_value,$content);
        $content = str_replace('%html_value%',$html_value,$content);

        // special replaces
        $content = str_replace('%options%',$html_select,$content);
        $content = str_replace('%checkboxes%',$html_checkboxes,$content);
        $content = str_replace('%radio%',$html_radio,$content);
        $content = str_replace('%checked%',$html_checked,$content);  // -- Toegevoegd door Bob.
        $content = str_replace('%check_value%',$check_value,$content);  // -- Toegevoegd door Bob.

        // some translated stuff
        $content = str_replace('%label1%',__("Upload an image"), $content);
        $content = str_replace('%label2%',__("Upload"), $content);
        $content = str_replace('%labeladdimg%',__("Add an image"), $content);
        $content = str_replace('%labeltitle%',__("Title"), $content);
        $content = str_replace('%labeldata%',__("Data attribute"), $content);
        $content = str_replace('%labelthumb%',__("Thumbnail"), $content);
        $content = str_replace('%labelpos%',__("Position"), $content);
        $content = str_replace('%labelgall%',__("Gallery"), $content);
        $content = str_replace('%labelsel%',__("Select"), $content);
        $content = str_replace('%labelselimg%',__("Select an image"), $content);
        $content = str_replace('%labelsave%',__("Save"), $content);
        $content = str_replace('%labelcancel%',__("Cancel"), $content);
        $content = str_replace('%labeledit%',__("Edit"), $content);
        $content = str_replace('%labeldelete%',__("Delete"), $content);
        $content = str_replace('%labelimage%',__("Image"), $content);

        $pre_content = str_replace('%labeladdimg%',__("Add an image"), $pre_content);
        $pre_content = str_replace('%labeledit%',__("Edit"), $pre_content);
        $pre_content = str_replace('%labeldelete%',__("Delete"), $pre_content);
        $pre_content = str_replace('%labelimage%',__("Image"), $pre_content);

        $fieldtype = 'text';
        if (count($lines) > 0) {
            if (trim($lines[0]) != '') {
                $fieldtype = trim($lines[0]);
            }
        }
        $content = str_replace('%fieldtype%',$fieldtype,$content);

        // copy the actual values
        foreach($subject as $key => $value) {
            if (is_scalar($value)) {
                $content = str_replace('%'.$key.'%',htmlspecialchars($value),$content);
            }
        }
        if (is_array($subject['extrafields'])) {
            foreach($subject['extrafields'] as $key => $value) {
                $content = str_replace('%'.$key.'%',htmlspecialchars($value),$content);
            }
        }

        // surround by showif's
        if ($this->data['showif'] != '') {
            $class_str = 'showif';

            switch ($this->data['showif_type']) {
                case '':
                    $names = preg_split('/(\r|\n|\r\n])/',$this->data['showif']);

                    $is_page  = false;
                    $chapters = array();
                    if (substr($this->location,0,5) == 'page-') {
                        $is_page = true;

                        global $PIVOTX;
                        $_chapters = $PIVOTX['pages']->getIndex();
                        foreach($_chapters as $_c) {
                            $chapters[strtolower(trim($_c['chaptername']))] = $_c['uid'];
                        }
                    }

                    $prefix = ' showif-category-';
                    if ($is_page) {
                        $prefix = ' showif-chapter-';
                    }

                    foreach($names as $name) {
                        $name = strtolower(trim($name));
                        if ($is_page) {
                            if (isset($chapters[$name])) {
                                $name = $chapters[$name];
                            }
                            else {
                                $name = '';
                            }
                        }
                        if ($name != '') {
                            $class_str .= $prefix.$name;
                        }
                    }
                    break;

                case 'records':
                    $ids = preg_split('/(\r|\n|\r\n])/',$this->data['showif']);
                    foreach($ids as $id) {
                        if (is_numeric($id)) {
                            $class_str .= ' showif-uid-' . $id;
                        }
                        else {
                            $class_str .= ' showif-uri-' . $id;
                        }
                    }
                    break;
            }

            $content = str_replace('%showif%',$class_str,$content);
        }

        // 
        $pre_content = str_replace('%upload_base_url%',$PIVOTX['paths']['upload_base_url'],$pre_content);
        $content     = str_replace('%upload_base_url%',$PIVOTX['paths']['upload_base_url'],$content);
        $pre_content = str_replace('%site_url%',$PIVOTX['paths']['site_url'],$pre_content);
        $content     = str_replace('%site_url%',$PIVOTX['paths']['site_url'],$content);

        if (strstr($content,'%uploadelement%')) {
            $params = array(
                'filters' => 'image',
                'upload_type' => 'images',
                'progress_selector' => '#divFileProgressContainer-'.$fieldkey,
                'input_selector' => '#inputElement-'.$fieldkey,
                'browse_button' => 'uploadbutton-'.$fieldkey,
                'container' => 'uploadcontainer-'.$fieldkey,
                'rendername' => 'renderfor-'.$fieldkey
            );
            $u = new UploadElement;
            $content = str_replace('%uploadelement%',$u->render($params),$content);
        }

        // remove all unwanted 'macros'
        $content = preg_replace('|%([a-z0-9_-]+)%|i','',$content);

        //$content = '<style type="text/css"> span.description { color: #666; font-size: 11px; } </style>' . $content;
        $content = $pre_content . $content;

        return $content;
    }

    public function getOptions()
    {
        $actual_data = $this->data['data'];

        if (method_exists('bonusfieldsInputSpecials',$this->type)) {
            list($actual_type,$actual_data) = bonusfieldsInputSpecials::$actual_type($this);
        }

        $options = $this->decodeOptions($actual_data);

        if (class_exists('oops_iterator')) {
            return new oops_iterator($options);
        }
        return $options;
    }
}

/**
 * Bonusfields configuration class
 */
class bonusfieldsConfig {
    /**
     * The singleton instance variable
     */
    protected static $instance = false;

    /**
     * Flag to notify if the configuration should be written out
     */
    protected $configuration_changed = false;

    /**
     * All the fields
     */
    protected $fields = false;


    /**
     * Types
     */
    public $types = false;

    /**
     * Helptypes
     */
    public $help_types = false;

    /**
     * Showif types
     */
    public $showif_types = false;

    /**
     * Position
     */
    public $positions = false;

    /**
     * Subposition
     */
    public $subpositions = false;


    /**
     * Multilingual Subposition
     */
    public $ml_subpositions = false;


    /**
     * The constructor
     */
    protected function __construct() {
        $this->types = array(
            '1' => '',
            'hidden' => 'input hidden',
            'input_text' => 'input text',
            'textarea' => 'textarea',
            'select' => 'select',
            'select_multiple' => 'select (multiple)',
            'radio' => 'radio',
            'checkbox' => 'checkbox',
            'checkbox_multiple' => 'checkbox (multiple)',
            'file' => 'file',
            '2' => '',
            'image' => 'image',
            'gallery' => 'gallery',
            'date' => 'date',
            'datetime' => 'date and time',
            'textarea_html' => 'wysiwyg editor',
            'choose_page' => __('choose page'),
            'choose_parent' => __('choose parent'),
            'choose_entry' => __('choose entry'),
            'choose_category' => __('choose category'),
            '3' => '',
            'view_separator' => 'view separator (line)',
            'view_text' => 'view extrafield as text',
            'view_html' => 'view extrafield as html',
//            'view_date' => 'view extrafield as date',
//            'view_datetime' => 'view extrafield as date/time',
            '4' => '',
            'view_fixedhtml' => 'view fixed html',
        );

        $this->help_types = array(
            'hidden' => 'input type hidden, not visible at all',
            'input_text' => 'input type text box, first line can contain a specific input type, one of:<br/><br/>tel, url, email, datetime, time, number<br/><br/>PS. at this time only browsers that support these type, actually check for the value of this type',
            'textarea' => 'textarea input',
            'textarea_html' => 'textarea html',
            'select' => 'select input<br/>seperate options with newlines and you can use "::" to seperate key/values, for example:<br/>nl :: Nederland<br/>be :: België',
            'select_multiple' => 'select input<br/>seperate options with newlines and you can use "::" to seperate key/values, for example:<br/>nl :: Nederland<br/>be :: België<br/>Only works for really new versions of PivotX 3.x',
            'radio' => 'radio input<br/>seperate options with newlines and you can use "::" to seperate key/values, for example:<br/>nl :: Nederland<br/>be :: België',
            'checkbox' => 'checkbox input<br/>enter the value to set when checked on the first line, value will be <em>empty</em> when not checked', // -- Toegevoegd door Bob.
            'checkbox_multiple' => 'multiple-checkbox input<br/>seperate options with newlines and you can use "::" to seperate key/values, for example:<br/>nl :: Nederland<br/>be :: België<br/>Only works for really new versions of PivotX 3.x',
            'date' => 'date input',
            'datetime' => 'date and time input',
            'file' => 'file, upload a file and store the filename',
            'image' => 'image, upload an image and store the filename',
            'gallery' => 'images, upload multiple images',
            'view_text' => 'view extrafield as text',
            'view_html' => 'view extrafield as html',
            'view_fixedhtml' => 'view fixed html',

            'choose_entry' => 'Here you can enter the categories from which entries are shown and set the order by which the entries are sorted..<br/><br/>Each category should be on his own line. Use the internal name (left most column in Categories) for the name.<br/><br/>To sort the entries either by "title" or "date" enter on a separate line either one of these:<br/>sort: title<br/>sort: date<br/>',
            'choose_page' => 'Here you can enter the chapters from which pages are shown.<br/><br/>Each chapter should be on his own line.',
            'choose_parent' => 'Here you can enter the chapters from which pages are shown.<br/><br/>Each chapter should be on his own line.',
            'choose_category' => 'No options.',
        );

        $this->showif_types = array(
            '' => __('show if by category (entry) or chapter (page)'),
            'records' => __('show if certain uid\'s or uris selected')
        );

        $this->positions = array(
            '1' => '',
            'entry-introduction-before' => __('entry - before introduction'),
            'entry-body-before' => __('entry - before body'),
            'entry-keywords-before' => __('entry - before keywords'),
            'entry-bottom' => __('entry - bottom of the page'),
            'entry-category-before' => __('entry - before category'),
            'entry-code-after' => __('entry - after code'),
            '2' => '',
            'page-introduction-before' => __('page - before introduction'),
            'page-body-before' => __('page - before body'),
            'page-keywords-before' => __('page - before keywords'),
            'page-bottom' => __('page - bottom of the page'),
            'page-chapter-before' => __('page - before chapter'),
            'page-code-after' => __('page - after code')
        );

        $this->subpositions = array(
            'introduction-before',
            'body-before',
            'keywords-before',
            'bottom',
            'category-before',
            'chapter-before',
            'code-after',
        );

        $this->ml_subpositions = array(
            'introduction-before',
            'body-before',
            'keywords-before'
        );

        $this->help_positions = array(
            'entry-introduction-before' => 'entry before introduction',
            'entry-body-before' => 'entry before body',
            'entry-keywords-before' => 'entry keywords before',
            'entry-bottom' => 'entry bottom of the page',
            'entry-category-before' => 'entry before category',
            'entry-sidebar-after' => 'entry after code',

            'page-introduction-before' => 'page before introduction',
            'page-body-before' => 'page before body',
            'page-keywords-before' => 'page before keywords',
            'page-bottom' => 'page bottom of the page',
            'page-chapter-before' => 'page before chapter',
            'page-code-after' => 'page after code'
        );


        $this->update_customentrytypes();
    }

    /**
     * Get the correct instance
     *
     * @return object  the configuration instance
     */
    public static function instance() {
        if (self::$instance == false) {
            self::$instance = new bonusfieldsConfig();
        }
        return self::$instance;
    }


    /**
     * Update positions for custom entrytypes
     */
    protected function update_customentrytypes()
    {
        global $PIVOTX;

        if (!isset($PIVOTX['config']->data['entrytypes_types'])) {
            return;
        }
        $entrytypes = trim($PIVOTX['config']->data['entrytypes_types']);
        if ($entrytypes == '') {
            return;
        }

        $types = explode(',',$entrytypes);

        // we will always add page and/or entry
        if (!in_array('page', $types)) {
            array_unshift($types,'page');
        }
        if (!in_array('entry', $types)) {
            array_unshift($types,'entry');
        }

        $cnt = 1;

        $positions = array();
        foreach($types as $type) {
            $positions[$cnt++] = '';

            foreach($this->subpositions as $subpos) {
                $key   = $type . '-' . $subpos;
                $label = $type . ' - ' . str_replace('-',' ',$subpos);

                if (in_array($type,array('page','entry'))) {
                    $label = __($type . ' - ' . str_replace('-',' ',$subpos));
                }

                $positions[$key] = $label;

            }
        }

        $this->positions = $positions;
    }
    
    /**
     * Helper call to get a database object
     */
    public function getDatabase() {
        global $PIVOTX;

        return new sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'), $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    }
    
    /**
     * Import ultimatefields into bonusfields
     */
    protected function importUltimatefields() {
        global $PIVOTX;

        $db        = $this->getDatabase();
        $dbprefix  = $PIVOTX['config']->get('db_prefix');

        $have_ultimatefields = false;
        $db->query('show tables like "'.$dbprefix.'ultimatefields"');
        if ($row = $db->fetch_row()) {
            $have_ultimatefields = true;
        }

        $fields = array();

        if ($have_ultimatefields) {
            $db->query('select * from '.$dbprefix.'ultimatefields');
            while ($row = $db->fetch_row()) {
                $ufields[] = $row;
            }

            $fields = array();
            foreach($ufields as $ufield) {
                $values = array();

                $values['fieldkey']    = $ufield['fieldsafename'];
                $values['name']        = $ufield['fieldname'];
                $values['sortorder']   = count($fields) + 1;
                $values['type']        = $ufield['fieldtype'];
                $values['location']    = $ufield['contenttype'].'-'.$ufield['fieldposition'];
                $values['contenttype'] = $ufield['contenttype'];

                switch ($ufield['fieldtype']) {
                    case 'text':
                        $values['type'] = 'input_text';
                        break;
                    case 'select':
                        $values['data'] = join("\n",explode(',',$values['ultimatefieldselectoptions']));
                        break;
                }
                switch ($ufield['fieldposition']) {
                    case 'body-after':
                        $values['location'] = $ufield['contenttype'].'-keywords-before';
                        break;

                    case 'sidebar-top':
                        $values['location'] = $ufield['contenttype'].'-category-before';
                        break;

                    case 'sidebar-bottom':
                        $values['location'] = $ufield['contenttype'].'-code-after';
                        break;
                }

                $def = new bonusfieldsDefinition();
                $def->importFromArray($values);

                $fields[] = $def;
            }
        }

        $this->fields = $fields;
    }

    /**
     * Read the bonusfields configuration
     *
     * If non-existent we import it from the ultimatefields
     */
    public function readConfiguration() {
        global $PIVOTX;

        if (function_exists('load_serialize')) {
            $config = load_serialize($PIVOTX['paths']['db_path'].'ser_bonusfields.php', true);
        }
        else if (function_exists('loadSerialize')) {
            $config = loadSerialize($PIVOTX['paths']['db_path'].'ser_bonusfields.php', true);
        }

        if ($config == false) {
            if ($PIVOTX['config']->get('db_model') == 'mysql') {
                $this->importUltimatefields();

                $this->configuration_changed = true;
            }
        }
        else {
            // fields are stored as arrays so there should be no problem upgrading the
            // object in the future
            $this->fields = array();
            foreach($config['definition'] as $array_field) {
                $field = new bonusfieldsDefinition();

                $field->importFromArray($array_field);

                $this->fields[] = $field;
            }
        }
    }

    /**
     * Write the bonusfields configuration if something changed
     */
    public function writeConfiguration() {
        global $PIVOTX;

        if ($this->configuration_changed == false) {
            return;
        }

        // we don't export the actual field-object but we create an array this won't present problems
        // in the future when we upgrade the object
        $fields = array();
        foreach($this->fields as $field) {
            $fields[] = $field->exportToArray();
        }

        $config = array();

        $config['definition'] = $fields;

        if (function_exists('save_serialize')) {
            save_serialize($PIVOTX['paths']['db_path'].'ser_bonusfields.php',$config);
        }
        else if (function_exists('saveSerialize')) {
            saveSerialize($PIVOTX['paths']['db_path'].'ser_bonusfields.php',$config);
        }

        $this->configuration_changed = false;
    }

    /**
     * Get the html for viewing all the fields
     */
    public function getConfigurationTableHtml() {
        $html = '';

        $html .= "<table cellspacing=\"0\" border=\"0\" width=\"\">\n";
        $html .= "\t<thead>\n";
        $html .= "\t\t<tr>\n";
        $html .= "\t\t\t<th style=\"width: 80px;\">Position</th>\n";
        $html .= "\t\t\t<th style=\"width: 200px;\">Name</th>\n";
        $html .= "\t\t\t<th style=\"width: 200px;\">Fieldkey</th>\n";
        $html .= "\t\t\t<th style=\"width: 200px;\">Type</th>\n";
        $html .= "\t\t\t<th style=\"width: 200px;\">Location</th>\n";
        $html .= "\t\t\t<th style=\"width: 40px;\">&#160;</th>\n";
        $html .= "\t\t</tr>\n";
        $html .= "\t</thead>\n";
        $html .= "\t<tbody class=\"bonusfieldrows\">\n";

        $position = 1;
        foreach($this->fields as $field) {
            $html .= $field->getConfigurationRowHtml($position++,$this);
        }

        $html .= "\t</tbody>\n";
        $html .= "</table>\n";

        $smartadd_html = $this->getSmartAddHtml();

        $html .= "<div class=\"buttons\" style=\"margin-top: 10px\">\n";
        $html .= "\t" . '<button id="bonusfield_addbutton" class="positive">Add bonusfield</button>';
        if ($smartadd_html != '') {
            $html .= "\t" . '<button id="bonusfield_smartbutton" class="positive">Smart bonusfield</button>';
        }
        if (function_exists('searchUpdateExtraSearchTexts')) {
            $html .= "\t" . '<button id="bonusfield_updateindex" class="positive">Update searchindex</button>';
        }
        $html .= "\t<br style=\"clear: both\"/>\n";
        $html .= "</div>\n";

        $html .= $smartadd_html;

        foreach($this->fields as $field) {
            $html .= $this->getEditFormHtml($field);
        }

        return $html;
    }

    /**
     * Look for extrafields not yet listed in the bonusfields
     */
    public function getSmartAddHtml() {
        global $PIVOTX;

        $have = false;

        $html  = '';
        $html .= "<div id=\"smartfields\" style=\"display: none; margin-top: 30px;\">\n";
        $html .= "<h3>Smart bonusfield add</h3>\n";
        $html .= "<table cellspacing=\"0\" border=\"0\" width=\"\" style=\"\">\n";
        $html .= "\t<thead>\n";
        $html .= "\t\t<tr>\n";
        //$html .= "\t\t\t<th style=\"width: 80px;\">Position</th>\n";
        $html .= "\t\t\t<th style=\"width: 200px;\">Name</th>\n";
        $html .= "\t\t\t<th style=\"width: 200px;\">Fieldkey</th>\n";
        $html .= "\t\t\t<th style=\"width: 200px;\">Type</th>\n";
        $html .= "\t\t\t<th style=\"width: 200px;\">Location</th>\n";
        $html .= "\t\t\t<th style=\"width: 40px;\">&#160;</th>\n";
        $html .= "\t\t</tr>\n";
        $html .= "\t</thead>\n";
        $html .= "\t<tbody>\n";

        $cfginstance = bonusfieldsConfig::instance();

        if ($PIVOTX['config']->get('db_model') == 'mysql') {
            $db = new sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'), $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
            $sql = 'select *,count(uid) as no_of_values,count(distinct value) as no_of_diff_values from '.$PIVOTX['config']->get('db_prefix').'extrafields group by contenttype,fieldkey';
            $db->query($sql);

            $rows = array();
            while ($row = $db->fetch_row()) {
                $rows[] = $row;
            }

            foreach($rows as $row) {
                $suggest = true;
                if ($row['no_of_values'] < 1) {
                    $suggest = false;
                }
                if (trim($row['fieldkey']) == '') {
                    $suggest = false;
                }
                else if ($cfginstance->findField($row['fieldkey'],$row['contenttype']) != false) {
                    $suggest = false;
                }


                if ($suggest) {
                    $have = true;

                    $values = array();

                    $values['name']        = preg_replace('|[-_]|',' ',$row['fieldkey']);
                    $values['fieldkey']    = $row['fieldkey'];
                    $values['type']        = 'input_text';
                    $values['location']    = $row['contenttype'].'-introduction-before';

                    $perc = round((100 * $row['no_of_diff_values']) / $row['no_of_values']);
                    if ($row['no_of_diff_values'] < 10) {
                        $values['type'] = 'select';
                    }
                    if ($row['fieldkey'] == 'galleryimagelist') {
                        $values['type'] = 'gallery';
                    }

                    $html .= "\t\t<tr>\n";
                    //$html .= "\t\t\t<td>&#160;</td>\n";
                    $field = '';
                    foreach($values as $key => $value) {
                        $html  .= "\t\t\t<td>".htmlspecialchars($value)."</td>\n";

                        if ($field != '') {
                            $field .= ';';
                        }
                        $field .= $key . '=' . $value;
                    }

                    $href  = '';
                    $href .= '?page=configuration';
                    $href .= '&amp;action=smartadd';
                    $href .= '&amp;field='.rawurlencode($field);
                    $href .= '#section-bonusfields';

                    $html .= "\t\t\t<td><a href=\"".$href."\"><img src=\"pics/page_add.png\" alt=\"add\" style=\"border: 0\"/></a></td>\n";
                    $html .= "\t\t</tr>\n";
                }
            }
        }

        $html .= "\t</tbody>\n";
        $html .= "</table>\n";
        $html .= "</div>\n";

        if (!$have) {
            $html = '';
        }


        return $html;
    }

    /**
     * Get the form to edit a particular field
     *
     * @param string $field   fieldkey to edit (or false for new)
     */
    public function getEditFormHtml($field = false) {
        $form = new Form('editbonusfield',$url,'Edit bonusfield');

        $data = array();
        if ($field instanceof bonusfieldsDefinition) {
            $data = $field->exportToArray();
        }

        $form->add(array(
            'type' => 'hidden',
            'name' => 'original_fieldkey',
            'label' => 'Original fieldkey (this field will be hidden)',
            'class' => 'noautoupdate',
            'value' => (($data['fieldkey'] == '') ? 'newnewnew' : $data['fieldkey']),
            'isrequired' => 1
        ));
        $form->add(array(
            'type' => 'text',
            'name' => 'name',
            'label' => 'Name',
            'class' => 'noautoupdate',
            'value' => $data['name'],
            'isrequired' => 1
        ));
        $form->add(array(
            'type' => 'text',
            'name' => 'fieldkey',
            'label' => 'Internal name',
            'class' => 'noautoupdate fieldkey',
            'value' => $data['fieldkey'],
            'isrequired' => 1
        ));
        $form->add(array(
            'type' => 'select',
            'name' => 'type',
            'label' => 'Type',
            'class' => 'noautoupdate',
            'value' => $data['type'],
            'isrequired' => 1,
            'options' => $this->types
        ));
        $form->add(array(
            'type' => 'select',
            'name' => 'location',
            'label' => 'Location',
            'class' => 'noautoupdate',
            'value' => $data['location'],
            'isrequired' => 1,
            'options' => $this->positions
        ));
        $form->add(array(
            'type' => 'select',
            'name' => 'showif_type',
            'label' => 'Show if condition type',
            'class' => 'noautoupdate',
            'value' => $data['showif_type'],
            'options' => $this->showif_types,
            'isrequired' => 0
        ));
        if (function_exists('searchUpdateExtraSearchTexts')) {
            $form->add(array(
                'type' => 'checkbox',
                'name' => 'searchable',
                'label' => 'Make this searchable',
                'class' => 'noautoupdate',
                'value' => $data['searchable'],
                'isrequired' => 0
            ));
        }
        $form->add(array(
            'type' => 'textarea',
            'name' => 'showif',
            'label' => 'Show if condition is met',
            'class' => 'noautoupdate',
            'value' => $data['showif'],
            'isrequired' => 0
        ));
        $form->add(array(
            'type' => 'textarea',
            'name' => 'data',
            'label' => 'Extra type information',
            'class' => 'noautoupdate',
            'value' => $data['data'],
            'isrequired' => 0
        ));
        $form->add(array(
            'type' => 'text',
            'name' => 'empty_text',
            'label' => 'Empty text (if applicable)',
            'class' => 'noautoupdate',
            'value' => $data['empty_text'],
            'isrequired' => 0
        ));
        $form->add(array(
            'type' => 'textarea',
            'name' => 'description',
            'label' => 'Optional description',
            'class' => 'noautoupdate',
            'value' => $data['description'],
            'isrequired' => 0
        ));
        $form->add(array(
            'type' => 'text',
            'name' => 'taxonomy',
            'label' => 'Taxonomy (if applicable)',
            'class' => 'noautoupdate',
            'value' => $data['taxonomy'],
            'isrequired' => 0
        ));

        $title = 'Edit bonusfield';
        if ($data['name'] == '' ) {
            $title = 'Add bonusfield';
        }
        $form->submit = $title;

        $html = '';

        $form_html = $form->fetch();
        //$form_html = str_replace('<form ','<form style="width: 600px; float: left;" ',$form_html);
        $form_html = str_replace('<form ','<form class="bonusfieldedit" ',$form_html);
        $form_html = str_replace('<td class="buttons" colspan="3">','<td></td><td class="buttons" colspan="2">',$form_html);
        $form_html = preg_replace('|<table([^>]+)class="formclass"|','<table\\1 style="width: 600px; float: left" class="formclass"',$form_html);
        $form_html = str_replace('class="resizable ','class="noautoupdate resizable ',$form_html);

        $html .= "<div class=\"bonusfields_editform bffieldkey_" . $data['fieldkey'] . '_' . $data['contenttype'] . "\" style=\"margin-top: 30px\">\n";
        $html .= "\t<h3>$title</h3>\n";
        $html .= $form_html;
        $html .= "\t<div class=\"bonusfields_help\" style=\"float: left; border-left: 1px solid #bbb; padding-left: 10px; color: #666; width: 350px;\">\n";
        $html .= "\t\t<h4 style=\"margin-top: 0;\">Help</h4>\n";
        $html .= "\t\t<div class=\"actualhelptext\">\n";
        $html .= "\t\t\t<p>No help available on this topic.</p>\n";
        $html .= "\t\t</div>\n";
        $html .= "\t</div>\n";
        $html .= "\t<br style=\"clear: both\" />\n";
        $html .= "</div>\n";

        return $html;
    }

    /**
     * Find a field
     *
     * @param string $fieldkey
     * @param object            returned the definition or false
     */
    public function findField($fieldkey,$contenttype) {
        $idx = false;
        $cnt = count($this->fields);

        for($i=0; $i < $cnt; $i++) {
            if (($this->fields[$i]->fieldkey == $fieldkey) && ($this->fields[$i]->contenttype == $contenttype)) {
                $idx = $i;
                break;
            }
        }

        if ($idx !== false) {
            return $this->fields[$idx];
        }

        return false;
    }

    /* 
     * Save field definition
     *
     * @param string $fieldkey
     * @param object $field
     */
    public function saveField($fieldkey, $contenttype, $field) {
        $idx = false;
        $cnt = count($this->fields);

        for($i=0; $i < $cnt; $i++) {
            if (($this->fields[$i]->fieldkey == $fieldkey) && ($this->fields[$i]->contenttype == $contenttype)) {
                $idx = $i;
                break;
            }
        }

        if ($idx !== false) {
            $this->fields[$idx] = $field;
        }
        else {
            $this->fields[] = $field;
        }

        $this->configuration_changed = true;
    }

    /**
     * Delete field definition
     *
     * @param string $fieldkey
     */
    public function deleteField($fieldkey,$contenttype) {
        $idx = false;
        $cnt = count($this->fields);

        for($i=0; $i < $cnt; $i++) {
            if (($this->fields[$i]->fieldkey == $fieldkey) && ($this->fields[$i]->contenttype == $contenttype)) {
                $idx = $i;
                break;
            }
        }

        if ($idx !== false) {
            array_splice($this->fields,$idx,1);
        }

        $this->configuration_changed = true;
    }

    /**
     * Duplicate field definition
     *
     * @param string $fieldkey
     */
    public function duplicateField($fieldkey,$contenttype) {
        $idx = false;
        $cnt = count($this->fields);

        for($i=0; $i < $cnt; $i++) {
            if (($this->fields[$i]->fieldkey == $fieldkey) && ($this->fields[$i]->contenttype == $contenttype)) {
                $idx = $i;
                break;
            }
        }

        if ($idx !== false) {
            $newfield = new bonusfieldsDefinition();
            $newfield->importFromArray($this->fields[$idx]->exportToArray());

            $oldkey    = $this->fields[$idx]->fieldkey;
            $oldnumber = 1;
            if (preg_match('|^(.+?)([0-9]+)$|',$this->fields[$idx]->fieldkey,$match)) {
                $oldkey    = $match[1];
                $oldnumber = intval($match[2]);
            }
            $newnumber = $oldnumber;
            $found = true;
            while ($found) {
                $newnumber++;

                $found = false;
                $oldcomplete = $oldkey . $oldnumber;
                foreach($this->fields[$i] as $f) {
                    if ($f->fieldkey == $oldcomplete) {
                        $found = true;
                        break;
                    }
                }
            }
            $newfield->fieldkey = $oldkey . $newnumber;
            if (preg_match('|^(.+?)([0-9]+)$|',$this->fields[$idx]->name,$match)) {
                $newfield->name = $match[1] . (intval($match[2]) + 1);
            }

            if ($idx == (count($this->fields)-1)) {
                $this->fields[] = $newfield;
            }
            else {
                array_splice($this->fields,$idx+1,0,array($newfield));
            }
        }

        $this->configuration_changed = true;
    }

    /**
     * Get taxonomy
     *
     * @param string    $taxonomy
     * @param string    $contenttype
     * @param object    returned the definition or false
     */
    public function getTaxonomy($taxonomy,$contenttype) {
        $idx = false;
        $cnt = count($this->fields);

        $values = array();

        for($i=0; $i < $cnt; $i++) {
            if (($this->fields[$i]->taxonomy == $taxonomy) && ($this->fields[$i]->contenttype == $contenttype)) {
                $idx = $i;

                $options = $this->fields[$i]->getOptions();
                foreach($options as $option) {
                    $values[] = array(
                        'name' => $option['label'],
                        'value' => $option['value'],
                    );
                }
            }
        }

        return $values;
    }

    /**
     * Set the field order
     *
     * @param array $order   fieldkey's separated by comma's
     */
    public function setFieldOrder($order) {
        $fields = array();
        $cnt    = count($this->fields);

        $fo = explode(',',$order);
        foreach($fo as $fieldid) {
            list($fieldkey,$contenttype) = explode(':',$fieldid);

            for($i=0; $i < $cnt; $i++) {
                if (($this->fields[$i]->fieldkey == $fieldkey) && ($this->fields[$i]->contenttype == $contenttype)) {
                    $fields[] = $this->fields[$i];
                }
            }
        }

        $this->fields = $fields;

        $this->configuration_changed = true;
    }

    /**
     * Add some useful Smarty tags to the primary smarty instance
     */
    public function addSmartyTags() {
        global $PIVOTX;

        $classes = array('bonusfieldsSmarty');

        foreach($classes as $class) {
            $methods = get_class_methods($class);

            foreach($methods as $method) {
                if (substr($method,0,9) == 'function_') {
                    $name = substr($method,9);

                    $PIVOTX['template']->register_function($name,array($class,$method));
                }
                elseif (substr($method,0,6) == 'block_') {
                    $name = substr($method,6);

                    $PIVOTX['template']->register_block($name,array($class,$method));
                }
            }
        }
    }

    /**
     * Determine if we want to show the hierarchy admin interface
     *
     * @return mixed     returns name of the parent field or false if no parent field
     */
    public function getParentFieldkey() {
        foreach($this->fields as $f) {
            if ($f->type == 'choose_parent') {
                return $f->fieldkey;
            }
        }

        return false;
    }

    /**
     * Add all the hooks for the fields
     *
     * @param mxed &$extensions
     */
    public function addAllHooks(&$extensions) {
        $this->readConfiguration();

        $cnt = count($this->fields);

        for($i=0; $i < $cnt; $i++) {
            if (is_object($this->fields[$i])) {
                $this->fields[$i]->addHook($extensions);
            }
        }
    }

    /**
     * Render all the hooks fore a position
     *
     * @param string $position  hook position to render
     * @param mixed $subject    subject to render (page or entry)
     * @param mixed $arguments  extra arguments for the hook
     * @return string           added html
     */
    public function renderHooks($position, $subject, $arguments=false) {
        $this->readConfiguration();

        $cnt = count($this->fields);

        if (substr($position,0,2) == 'ml') {
            $position = substr($position,2);
        }

        $output = '';
        for($i=0; $i < $cnt; $i++) {
            if ($this->fields[$i]->location == $position) {
                $output .= $this->fields[$i]->renderHook($subject, $arguments);
            }
        }

        echo $output;

        return $output;
    }
    
    /**
     * Return the complete search text
     */
    public function addSearchText($target, $type)
    {
        global $PIVOTX;

        $ml    = false;
        $langs = array('-');
        if (is_object($PIVOTX['multilingual']) && ($PIVOTX['multilingual']->isEnabled())) {
            $ml    = true;
            $langs = $PIVOTX['multilingual']->getLanguages();
        }

        $this->readConfiguration();

        $cnt = count($this->fields);

        $text = array();
        foreach($langs as $l) {
            $text[$l] = '';
        }

        for($i=0; $i < $cnt; $i++) {
            if (($this->fields[$i]->contenttype == $type) && ($this->fields[$i]->searchable == '1')) {
                $key = $this->fields[$i]->fieldkey;

                if ($ml) {
                    list($_subject,$_subposition) = explode('-',$this->fields[$i]->location,2);
                    if ($this->isMultilingualPosition($_subposition)) {
                        foreach($langs as $l) {
                            $key   = $l . '__' . $this->fields[$i]->fieldkey;
                            $value = $target['extrafields'][$key];

                            $text[$l] .= $value . ' ';
                        }
                    }
                    else {
                        $key   = $this->fields[$i]->fieldkey;
                        $value = $target['extrafields'][$key];

                        foreach($langs as $l) {
                            $text[$l] .= $value . ' ';
                        }
                    }
                }
                else {
                    $key   = $this->fields[$i]->fieldkey;
                    $value = $target['extrafields'][$key];

                    $text['-'] .= $value . ' ';
                }
            }
        }

        return $text;
    }

    /**
     * Fix the taxonomies
     */
    function taxonomyAfterSave($target,$contenttype)
    {
        global $PIVOTX;

        $db     = self::getDatabase();
        $prefix = $PIVOTX['config']->get('db_prefix');

        $this->readConfiguration();

        $cnt = count($this->fields);
        $target_uid = $target['uid'];

        if (false) {
            echo '<pre>';
            var_dump($target);
            var_dump($_POST);
            die('stop');
        }

        $do_update = false;
        for($i=0; $i < $cnt; $i++) {
            if (($this->fields[$i]->contenttype == $contenttype) && (trim($this->fields[$i]->taxonomy) != '')) {
                $do_update = true;
                break;
            }
        }

        if ($do_update) {
            $db->query('delete from '.$prefix.'taxonomies where contenttype="'.$contenttype.'" and target_uid='.$target_uid);

            $taxonomies = array();
            for($i=0; $i < $cnt; $i++) {
                if (($this->fields[$i]->contenttype == $contenttype) && ($this->fields[$i]->taxonomy != '')) {
                    $taxonomy = $this->fields[$i]->taxonomy;
                    $taxonomies[] = $taxonomy;

                    if (is_array($_POST['extrafields'][$taxonomy])) {
                        foreach($_POST['extrafields'][$taxonomy] as $value) {
                            $inssql = array();
                            $inssql['into']   = $prefix . 'taxonomies';
                            $inssql['value']['contenttype'] = $contenttype;
                            $inssql['value']['taxonomy']    = $taxonomy;
                            $inssql['value']['name']        = $value;
                            $inssql['value']['target_uid']  = $target_uid;

                            $sql = $db->build_insert($inssql);

                            $db->query($sql);
                        }
                    }
                }
            }
        }

        if (false) {
            echo '<pre>';
            var_dump($target_uid);
            var_dump($taxonomies);
            var_dump($_POST);

            die('-');
        }
    }

    /**
     * Return true if given position is a multilingual position
     *
     * @param string $position
     * @return boolean
     */
    public function isMultilingualPosition($position) {
        global $PIVOTX;

        if ((class_exists('Multilingual')) && ($PIVOTX['multilingual'] instanceof Multilingual) && ($PIVOTX['multilingual']->isEnabled())) {
            return in_array($position,$this->ml_subpositions);
        }

        return false;
    }
}

/**
 * Render helper
 */
class bonusfieldsRender {
    /**
     * The regular header for main items
     *
     * @return string  html
     */
    public static function get_main_item_header() {
        $html = <<<THEEND
        <table class="formclass" border="0" cellspacing="0" width="650">
            <tbody>
THEEND;
                /*
                <tr>
                    <td colspan="3"><hr noshade="noshade" size="1" /></td>
                </tr>
                */
        return $html;
    }

    /**
     * The regular footer for main items
     *
     * @return string  html
     */
    public static function get_main_item_footer() {
        $html = <<<THEEND
            </tbody>
        </table>
THEEND;
        return $html;
    }

    /**
     * The regular header for sidebar items
     *
     * @return string  html
     */
    public static function get_sidebar_item_header() {
        return '';
    }

    /**
     * The regular footer for sidebar items
     *
     * @return string  html
     */
    public static function get_sidebar_item_footer() {
        return '';
    }
}

/**
 * Smarty gallery object
 */
class bonusfieldsGalleryImage {
    public $image = false;
    public $title = '';
    public $alt = '';
    public $data = '';

    public $rel = false;

    public $src = '';
    public $thumbsrc = '';

    protected $use_thumbs = false;

    public function __construct($text,$rel,$thumbwidth=false,$thumbheight=false,$use_thumbs=false)
    {
        $this->rel        = $rel;
        $this->use_thumbs = $use_thumbs;

        $parts = explode('###',trim($text));

        switch (count($parts)) {
            case 4:
                $this->data  = trim($parts[3]);
            case 3:
                $this->alt   = trim($parts[2]);
            case 2:
                $this->title = trim($parts[1]);
            case 1:
                $this->image = trim($parts[0]);
                break;
        }

        if ($this->valid()) {
            global $PIVOTX;
            $this->src = $PIVOTX['paths']['upload_base_url'] . $this->image;

            if (($thumbwidth !== false) && ($thumbheight !== false)) {
                $this->thumbsrc = $this->getImgSrc($thumbwidth,$thumbheight);

            }
        }
    }

    /**
     * True if we have a valid image
     */
    public function valid()
    {
        return ($this->image !== false);
    }

    /**
     * Get the image-url in a particular width/height
     */
    public function getImgSrc($width,$height,$options='zc=1')
    {
        global $PIVOTX;

        if ($this->use_thumbs) {
            if ($options == '#default#') {
                $options = '';
            }

            $src = '/thumbs/'.$width.'x'.$height.$options.'/'.$this->image;
        }
        else {
            if ($options == '#default#') {
                $options = 'zc=1';
            }

            $src .= $PIVOTX['paths']['pivotx_url']  . 'includes/timthumb.php';
            $src .= '?src=' . rawurlencode($this->image);
            $src .= '&amp;w='.$width;
            $src .= '&amp;h='.$height;
            if ($options != '') {
                $src .= '&amp;' . $options;
            }
        }

        return $src;
    }
}

/**
 * Smarty gallery object
 */
class bonusfieldsGallery implements Iterator {
    protected $images = array();
    public $length = 0;
    protected $pointer = 0;

    protected static $idcounter = false;
    public $rel = false;
    
    public function __construct($text, $use_thumbs=false)
    {
        if (self::$idcounter === false) {
            self::$idcounter = rand(1000,9000);
        }
        $this->rel = 'gallery-'.self::$idcounter++;


        $lines = preg_split('|[\r\n]+|',trim($text));

        foreach($lines as $line) {
            $line = trim($line);
            
            if ($line != '') {
                $img = new bonusfieldsGalleryImage($line,$this->rel,$thumbw,$thumbh,$use_thumbs);

                if ($img->valid()) {
                    $this->images[] = $img;
                }
            }
        }

        $this->length = count($this->images);
    }

    /**
     */
    public function __get($name) {
        switch ($name) {
            case 'index':
                return $this->pointer;
            case 'number':
                return $this->pointer + 1;
            case 'count':
                return $this->length;

            case 'first':
                return ($this->pointer == 0);
            case 'last':
                return ($this->pointer == ($this->length-1));

            case 'odd':
                return ($this->pointer % 2) == 1;
            case 'even':
                return ($this->pointer % 2) == 0;
        }

        return false;
    }

    /**
     * Iterator stuff
     */

    public function key()
    {
        return $this->images[$this->pointer];
    }

    public function current()
    {
        return $this->images[$this->pointer];
    }

    public function next()
    {
        if ($this->pointer > $this->length) {
            return false;
        }
        ++$this->pointer;
        return $this->images[$this->pointer-1];
    }

    public function valid()
    {
        return (($this->pointer >= 0) && ($this->pointer < $this->length));
    }

    public function rewind()
    {
        $this->pointer = 0;
    }
    
    public function count()
    {
        return $this->length;
    }
    
}

/**
 * Smarty field functions for bonusfields
 */
class bonusfieldsSmarty {
    protected static $pages_index = false;

    /**
     * Build an internal page index (no chapters and with extrafields)
     */
    protected static function update_index() {
        global $PIVOTX;

        if ((self::$pages_index === false) && (class_exists('oops_page'))) {
            $page = new oops_page;
            self::$pages_index = $page->loadall(array('order'=>'chapter,sortorder'));
        }
        
        if (self::$pages_index === false) {
            $chapters = $PIVOTX['pages']->getIndex();
            
            $index = array();
            foreach($chapters as $chapter) {
                foreach($chapter['pages'] as $simple_page) {
                    $page = $PIVOTX['pages']->getPage($simple_page['uid']);
                    
                    $index[] = $page;
                }
            }
            
            self::$pages_index = $index;
        }
    }

    /**
     * Return the page index
     */
    public static function get_pages() {
        self::update_index();

        return self::$pages_index;
    }

    /**
     * Get the page hierarchy
     */
    public static function get_page_hierarchy($extrafieldname,$extrafieldtype)
    {
        $xpages = self::get_pages();

        if (class_exists('oops_page')) {
            $pages = array();
            foreach($xpages as $xpage) {
                $parent_key = $xpage->$extrafieldname;

                // using an undocumented feature. this will bite me as long as pivotx 3.x exists..
                $vars = $xpage->get_object_vars();
                $data = $vars['data'];
                
                $data['link'] = makePagelink($data['uri'],$data['title'],$data['uid'],$data['date'],'',false);
                if (isset($PIVOTX['multilingual']) && ($PIVOTX['multilingual'] instanceof Mulitlingual)) {
                    $data['link'] = $PIVOTX['multilingual']->fixLanguageUri(false,$data['link']);
                }

                $pages[] = new bonusfieldsPage($data,$parent_key);
            }
        }
        else {
            $pages = array();
            foreach($xpages as $xpage) {
                $parent_key = $xpage['extrafields'][$extrafieldname];

                $pages[] = new bonusfieldsPage($xpage,$parent_key);
            }
        }

        $cnt = count($pages);
        for($i=0; $i < $cnt; $i++) {
            $parent_key = $pages[$i]->parent_key;
            $key        = $pages[$i]->$extrafieldtype;

            if ($parent_key != $key) {
                for($j=0; $j < $cnt; $j++) {
                    if ($pages[$j]->$extrafieldtype == $parent_key) {
                        $pages[$i]->set_parent($pages[$j]);
                        $pages[$j]->add_child($pages[$i]);
                        break;
                    }
                }
            }
        }

        return $pages;
    }

    /**
     * Special shortcut to assign stuff in Smarty from PHP
     */
    protected static function assign_smarty(&$smarty, $params, $name, $data) {
        $assignto = $name;
        if (isset($params['var'])) {
            $assignto = $params['var'];
        }
        if (isset($params['assignto'])) {
            $assignto = $params['assignto'];
        }
        $smarty->assign($assignto,$data);
    }

    /**
     * Get all pages with a certain value for a specific extrafield
     */
    public static function function_getpagelistwithextrafield($params,&$smarty) {
        self::update_index();

        $fieldkey   = false;
        $fieldvalue = false;
        if (isset($params['extrafield'])) {
            $fieldkey = $params['extrafield'];
        }
        if (isset($params['value'])) {
            $fieldvalue = $params['value'];
        }


        $uris = array();

        if ($fieldkey !== false) {
            foreach(self::$pages_index as $page) {
                if ((isset($page['extrafields'][$fieldkey])) && ($page['extrafields'][$fieldkey] == $fieldvalue)) {
                    $uris[] = $page['uri'];
                }
            }
        }

        self::assign_smarty($smarty,$params, 'pagelist',$uris);
    }

    /**
     * Get page path
     */
    public static function function_getpagepath($params,&$smarty) {
        $uri = $params['uri'];

        $pagepath = self::find_path($uri);

        self::assign_smarty($smarty,$params, 'pagepath',$pagepath);
    }

    /**
     * Returns either 'odd' or 'even' and switched everytime
     */
    public static function function_oddoreven($params, &$smarty) {
        static $counter = 0;

        if (($counter % 2) == 0) {
            echo 'even';
        }
        else {
            echo 'odd';
        }

        $counter++;
    }

    /**
     * Look for the root page of a certain uri
     *
     * @return string    the root uri
     */
    protected static function find_path($searchroot)
    {
        $extrafieldname = bonusfieldsConfig::instance()->getParentFieldkey();
        $extrafieldtype = 'uid';
        $path           = array();

        if (!is_array($searchroot)) {
            $searchroot = array($searchroot);
        }

        $pages = self::get_page_hierarchy($extrafieldname,$extrafieldtype);

        $rootpage     = false;
        $temprootpage = false;
        foreach($pages as $page) {
            if (in_array($page->uri,$searchroot)) {
                $temprootpage = $page;
                break;
            }
        }

        if ($temprootpage !== false) {
            if ($temprootpage->is_root()) {
                $path[] = $temprootpage->uri;
            }
            else {
                while (!$temprootpage->is_root()) {
                    $path[] = $temprootpage->uri;
                    $parentpage = $temprootpage->get_parent();
                    if ($parentpage === false) {
                        break;
                    }
                    $temprootpage = $parentpage;
                }
                if ($temprootpage->is_root()) {
                    $path[] = $temprootpage->uri;
                }
            }
        }

        $path = array_reverse($path);

        return $path;
    }

    /**
     * Look for the root page of a certain uri
     *
     * @return string    the root uri
     */
    protected static function find_searchroot($searchroot)
    {
        $path = self::find_path($searchroot);

        if (count($path) == 0) {
            return false;
        }

        return array($path[0]);
    }

    /**
     */
    public static function function_getpagehierarchy($params, &$smarty) {
        $extrafieldname = bonusfieldsConfig::instance()->getParentFieldkey();
        $extrafieldtype = 'uid';
        $root           = false;
        $searchroot     = false;
        $assignto       = 'root';

        if (isset($params['extrafieldname'])) {
            $extrafieldname = $params['extrafieldname'];
            $extrafieldtype = 'uri';
        }
        if (isset($params['root'])) {
            $root = explode(',',$params['root']);
        }
        if (isset($params['searchroot'])) {
            $searchroot = explode(',',$params['searchroot']);
        }
        if (isset($params['assignto'])) {
            $assignto = $params['assignto'];
        }

        $pages = false;
        if ($searchroot !== false) {
            $troot = self::find_searchroot($searchroot);

            if ($troot !== false) {
                $root = $troot;
            }
        }

        $rootp = false;
        if ($root !== false) {
            if ($pages === false) {
                $pages = self::get_page_hierarchy($extrafieldname,$extrafieldtype);
            }

            foreach($pages as $page) {
                if (in_array($page->uri,$root)) {
                    $rootp = $page;
                    break;
                }
            }
        }

        $smarty->assign($assignto,$rootp);
    }

    /**
     * Get pages who shared the same root
     */
    public static function function_getsubpages($params, &$smarty)
    {
        $extrafieldname = bonusfieldsConfig::instance()->getParentFieldkey();
        $extrafieldtype = 'uid';
        $root           = false;
        $searchroot     = false;
        $subpages       = false;
        $assignto       = 'subpages';

        if (isset($params['root'])) {
            $root = explode(',',$params['root']);
        }
        if (isset($params['searchroot'])) {
            $searchroot = explode(',',$params['searchroot']);
        }
        if (isset($params['assignto'])) {
            $assignto = $params['assignto'];
        }

        $pages = false;
        if ($searchroot !== false) {
            $troot = self::find_searchroot($searchroot);

            if ($troot !== false) {
                $root = $troot;
            }
        }

        if ($root !== false) {
            if ($pages === false) {
                $pages = self::get_page_hierarchy($extrafieldname,$extrafieldtype);
            }

            $rootp = false;
            foreach($pages as $page) {
                if (in_array($page->uri,$root)) {
                    $rootp = $page;
                    break;
                }
            }

            $subpages = array();

            if ($rootp !== false) {
                $subpages[] = $rootp;
                $uris       = array($rootp->uri);
                for($idx=0; $idx < count($subpages); $idx++) {
                    if ($idx > 10) {
                        break;
                    }
                    $cnt = $subpages[$idx]->get_no_of_children();
                    for($i=0; $i < $cnt; $i++) {
                        if (!in_array($subpages[$idx]->get_child($i)->uri,$uris)) {
                            $subpages[] = $subpages[$idx]->get_child($i);
                            $uris[]     = $subpages[count($subpages)-1]->uri;
                        }
                    }
                }
            }
        }

        $smarty->assign($assignto,$subpages);
    }

    /**
     * Get a gallery object
     *
     * <dl>
     *   <dt>content</dt>
     *   <dd>content to initialize a gallery with</dd>
     * </dl>
     */
    public static function function_bonusgallery($params, &$smarty)
    {
        $content   = false;
        if (isset($params['content'])) {
            $content = trim($params['content']);
        }

        if (isset($params['fancybox']) && (in_array($params['fancybox'],array('1','true')))) {
            if (function_exists('fancyboxIncludeCallback')) {
                $html = '';
                fancyboxIncludeCallback($html);
            }
        }
        if (isset($params['thumbs']) && ($params['thumbs'])) {
            $thumbs = true;
        }

        $gallery = new bonusfieldsGallery($content,$thumbs);

        $assignto = 'gallery';
        if (isset($params['assign'])) {
            $assignto = $params['assign'];
        }
        $smarty->assign($assignto,$gallery);
    }

    /**
     * Get bonusfield information
     */
    public static function function_getbonusfieldinfo($params, &$smarty)
    {
        $info = false;

        $fieldkey    = false;
        $contenttype = false;
        if (isset($params['fieldkey'])) {
            $fieldkey = $params['fieldkey'];
        }
        if (isset($params['contenttype'])) {
            $contenttype = $params['contenttype'];
        }

        if (($fieldkey !== false) && ($contenttype !== false)) {
            $field = bonusfieldsConfig::instance()->findField($fieldkey,$contenttype);

            if ($field !== false) {
                $info = $field;
            }
        }

        $assignto = 'options';
        if (isset($params['assign'])) {
            $assignto = $params['assign'];
        }
        $smarty->assign($assignto,$info);
    }

    /**
     * Get taxonomy list
     */
    public static function function_gettaxonomy($params, &$smarty)
    {
        $values = array();

        $taxonomy    = false;
        $contenttype = false;
        if (isset($params['taxonomy'])) {
            $taxonomy = $params['taxonomy'];
        }
        if (isset($params['contenttype'])) {
            $contenttype = $params['contenttype'];
        }

        if (($taxonomy !== false) && ($contenttype !== false)) {
            $values = bonusfieldsConfig::instance()->getTaxonomy($taxonomy,$contenttype);
        }

        $assignto = 'taxonomy';
        if ($taxonomy !== false) {
            $assignto = $taxonomy;
        }
        if (isset($params['assign'])) {
            $assignto = $params['assign'];
        }
        if (class_exists('oops_iterator')) {
            $smarty->assign($assignto,new oops_iterator($values));
        }
        else {
            $smarty->assign($assignto,$values);
        }
    }
}

/**
 * Some special cases for bonusfields
 */
class bonusfieldsInputSpecials {
    /**
     * Internal function sort compare entries array by date
     */
    public static function _CmpEntryByTitle(&$a,&$b) {
        $ret = strcasecmp($a[2],$b[2]);
        if ($ret == 0) {
            $ret = strcasecmp($a[1],$b[1]);
        }
        return $ret;
    }

    /**
     * Internal function sort compare entries array by date
     */
    public static function _CmpEntryByDate(&$a,&$b) {
        $ret = -1 * strcasecmp($a[1],$b[1]);
        if ($ret == 0) {
            $ret = strcasecmp($a[2],$b[2]);
        }
        return $ret;
    }

    /**
     * 'Choose another page'
     *
     * @param object &$field
     */
    public static function _choose_page(&$field, $key='uri') {
        global $PIVOTX;

        $type = 'select';

        $only_chapters = false;
        if (trim($field->data) != '') {
            $chapters = preg_split("/[\r|\n|\r\n]/",trim($field->data));
            foreach($chapters as $chapter) {
                $chapter = trim($chapter);
                if ($chapter != '') {
                    if ($only_chapters === false) {
                        $only_chapters = array();
                    }
                    $only_chapters[] = $chapter;
                }
            }
        }

        $index = $PIVOTX['pages']->getIndex();
        $data  = '';
        foreach($index as $chapter) {
            if ($only_chapters !== false) {
                if (!in_array($chapter['chaptername'],$only_chapters)) {
                    // chaptername not found, skip
                    continue;
                }
            }

            $data .= 'OPTGROUP::' . htmlspecialchars($chapter['chaptername']) . "\n";
            if (is_array($chapter['pages'])) {
                foreach($chapter['pages'] as $page) {
                    $data .= $page[$key] . ' :: ' . htmlspecialchars($page['title']) . "\n";
                }
            }
        }

        return array($type,$data);
    }

    /**
     * 'Choose another page'
     *
     * @param object &$field
     */
    public static function choose_page(&$field) {
        return self::_choose_page($field,'uri');
    }

    /**
     * 'Choose parent'
     *
     * @param object &$field
     */
    public static function choose_parent(&$field) {
        list($type,$data) = self::_choose_page($field,'uid');

        $data = "- :: (no parent)\n" . $data;

        return array($type,$data);
    }

    /**
     * 'Choose another entry'
     *
     * @param object &$field
     */
    public static function choose_entry(&$field) {
        global $PIVOTX;

        $type     = 'select';
        $order_by = 'date';

        $only_categories = false;
        if (trim($field->data) != '') {
            $cats = preg_split("/[\r|\n|\r\n]/",trim($field->data));
            foreach($cats as $cat) {
                $cat = trim($cat);
                if (($cat != '') && (preg_match('/^sort: *(title|date) */i',$cat,$match))) {
                    $order_by = strtolower($match[1]);
                }
                else if ($cat != '') {
                    if ($only_categories === false) {
                        $only_categories = array();
                    }
                    $only_categories[] = $cat;
                }
            }
        }

        $cats = array();
        if (is_array($only_categories)) {
            $cats = $only_categories;
        }

        // weakpoint here: we show show max. 1000 entries
        $entries = $PIVOTX['db']->read_entries(array('show'=>1000, 'cats'=>$cats));

        $adata   = array();
        foreach($entries as $entry) {
            if (strlen($entry['title'] > 65)) {
                $title = substr($entry['title'], 0, 65).'..';
            }
            else {
                $title = $entry['title'];
            }
            $adata[] = array($entry['uid'],substr($entry['publish_date'],0,10),$title);
//            $data .= $entry['uid'] . '::' . substr($entry['publish_date'],0,10).' '.$entry['title'] . "\n";
        }

        switch ($order_by) {
            case 'title':
                usort($adata,array('bonusfieldsInputSpecials','_CmpEntryByTitle'));

                $data = '';
                foreach($adata as $e) {
                    $data .= $e[0] .'::'. $e[2] .' ('. $e[1] .")\n";
                }
                break;

            default:
                usort($adata,array('bonusfieldsInputSpecials','_CmpEntryByDate'));

                $data = '';
                foreach($adata as $e) {
                    $data .= $e[0] .'::'. $e[1] .' '. $e[2] ."\n";
                }
                break;
        }

        return array($type,$data);
    }

    /**
     * 'Choose chapter'
     *
     * @param object &$field
     */
    public static function choose_chapter(&$field) {
        global $PIVOTX;

        $type = 'select';
        $data = "\n";

        $chaps = $PIVOTX['pages']->getIndex();
        foreach($chaps as $chap) {
            $data .= $chap['uid'] . '::' . $chap['chaptername'] . "\n";
        }
        
        $data = rtrim($data);

        return array($type,$data);
    }

    /**
     * 'Choose category'
     *
     * @param object &$field
     */
    public static function choose_category(&$field) {
        global $PIVOTX;

        $type = 'select';
        $data = "\n";

        $cats = $PIVOTX['categories']->getCategories();
        foreach($cats as $cat) {
            $data .= $cat['name'] . '::' . $cat['display'] . "\n";
        }
        
        $data = rtrim($data);

        return array($type,$data);
    }
}

/**
 */
class bonusfieldsPage {
    protected $parent_key = false;
    protected $parent = false;
    protected $children = false;
    protected $data = false;
    protected $keyfield = 'uri';

    public function __construct($data, $parent_key, $keyfield='uri') {
        $this->parent_key = $parent_key;
        $this->children   = array();
        $this->data       = &$data;
        $this->keyfield   = $keyfield;
    }

    public function __get($key) {
        if (isset($this->$key)) {
            return $this->$key;
        }
        return $this->data[$key];
    }

    public function get_parent_key() {
        return $this->data[$this->keyfield];
    }

    public function get_key() {
        return $this->data[$this->keyfield];
    }

    public function get_parent() {
        return $this->parent;
    }

    public function set_parent(&$page) {
        $this->parent = $page;
    }

    public function add_child(&$page) {
        $this->children[] = &$page;
    }

    public function is_root() {
        return ($this->parent === false) && (count($this->children) > 0);
    }

    public function is_fallen() {
        return ($this->parent === false) && (count($this->children) == 0);
    }

    public function get_no_of_children() {
        return count($this->children);
    }

    public function get_child($idx) {
        return $this->children[$idx];
    }

    public function str_children() {
        $uri = array();
        foreach($this->children as $child) {
            $uri[] = $child->uri;
        }
        return join(',',$uri);
    }
}

/**
 * PivotX hierarchy page
 */
function pageHierarchy()
{
    global $PIVOTX;

    $extrafieldname = bonusfieldsConfig::instance()->getParentFieldkey();
    $extrafieldtype = 'uid';

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Hierarchy'));

    $pages = bonusfieldsSmarty::get_page_hierarchy($extrafieldname,$extrafieldtype);


    $roots  = array();
    $fallen = array();

    $cnt = count($pages);
    for($i=0; $i < $cnt; $i++) {
        if ($pages[$i]->is_root()) {
            $roots[] = $pages[$i];
        }
        if ($pages[$i]->is_fallen()) {
            $fallen[] = $pages[$i];
        }
    }

    $PIVOTX['template']->assign('roots', $roots);
    $PIVOTX['template']->assign('fallen', $fallen);

    renderTemplate('../extensions/bonusfields/templates/page_hierarchy.tpl');
}

?>

<?php

/**
 * Two Kings Form Class, to construct web based forms, do validation and
 * handle the output.
 *
 * For more information, read: http://twokings.eu/tools/
 *
 * Two Kings Form Class and all its parts are licensed under the GPL version 2.
 * see: http://www.twokings.eu/tools/license for more information.
 *
 * @version 1.2
 * @author Lodewijk Evers, lodewijk@twokings.nl
 *
 * @version 1.1
 * @author Bob den Otter, bob@twokings.nl
 * @copyright GPL, version 2
 * @link http://twokings.eu/tools/
 *
 * $Rev:: xxx                                            $: SVN revision,
 * $Author:: pivotlog                                    $: author and
 * $Date:: 2009-10-20                                    $: date of last commit
 *
 */



/**
 * This file contains the default HTML definition for each of the form elements
 * If you want to change these, you can either override some of the elements
 * in your PHP code, modify this file, or instantiate the form class using
 * another HTML definitions file.
 */


/**
 * Header and footer of the form
 */
$this->html['start'] = <<< EOM
<form %encoding% name="%name%" id="%id%" action="%action%" method="%method%">
<fieldset style="display: none">
%hidden_fields%
</fieldset>
EOM;


$this->html['finish'] = <<< EOM
</form>
EOM;

/**
 * The submit button
 */
$this->html['submit'] = <<< EOM
<input type="submit" tabindex="%tabindex%" value="%submit%" name="%submit%" class="button" />
EOM;

/**
 * The form header
 */
$this->html['header'] = <<< EOM
%text%
EOM;

/**
 * For adding a 'row' to display information
 */
$this->html['info'] = <<< EOM
%text%
EOM;

/**
 * Add whatever to the form.
 */
$this->html['custom'] = <<< EOM
%text%
EOM;

$this->html['hr'] = <<< EOM
<hr size="1" noshade="noshade" />
EOM;


/**
 * Basic text input
 */
$this->html['text'] = <<< EOM
<label for="%name%">%label% %isrequired%</label><br />
<input name="%name%" id="%name%" class="%class% %haserror%" type="text" value="%value%" size="%size%" style="%style%" tabindex="%tabindex%" %extra% />
%error%
%text%
EOM;


/**
 * Insert a text input that's readonly
 */
$this->html['text_readonly'] = <<< EOM
<label for="%name%">%label% %isrequired%</label><br />
<input name="%name%" id="%name%" class="%haserror%" type="text" value="%value%" size="%size%" style="%style%" readonly="readonly" tabindex="%tabindex%" />
%error%
%text%
EOM;


/**
 * Text input, with an option to select/upload an image.
 */
$this->html['image_select'] = <<< EOM
<label for="%name%">%label% %isrequired%</label>
<table border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td valign="top" style="padding: 0;">
    <input name="%name%" id="%name%" class="%class% %haserror%" type="text" value="%value%" size="%size%" style="%style%" tabindex="%tabindex%" %extra% />
   %error%
        </td>
        <td valign="top" class="buttons_small" style="padding: 0 0 0 5px;">
            <a href="javascript:;" onclick="openUploadWindow('Select or upload an image', $('#%name%'), 'gif,jpg,png');">
                Select
            </a>
        </td>
    </td>
</table>
%text%
EOM;


/**
 * Insert a date select box.
 *
 * TODO: replace with jquery select box.
 */
$this->html['date_select'] = <<< EOM
<label for="%name%">%label% %isrequired%</label>
<div>
<input name="%name%" id="%name%" class="%haserror%" type="text" value="%value%" size="%size%"  tabindex="%tabindex%" />
<button type="reset" id="trigger[%tabindex%]">...</button>
<script type="text/javascript">
    Calendar.setup({
        inputField: "%name%", ifFormat: "%Y-%m-%d", showsTime: false,
        button: "trigger[%tabindex%]",  singleClick: true,  step: 1, align: "BR"
    });
</script>
%error%
%text%
</div>
EOM;


/**
 * Insert a date/time select box.
 *
 * TODO: replace with jquery select box.
 */
$this->html['datetime_select'] = <<< EOM
<label for="%name%">%label% %isrequired%</label>
<div>
<input name="%name%" id="%name%" class="%haserror%"  type="text" value="%value%" size="%size%" tabindex="%tabindex%" />
<button type="reset" id="trigger[%tabindex%]">...</button>
<script type="text/javascript">
    Calendar.setup({
        inputField: "%name%", ifFormat: "%Y-%m-%d %H:%I:00", showsTime: true,
        button: "trigger[%tabindex%]",  singleClick: true,  step: 1, align: "BR"
    });
</script>
%error%
%text%
</div>
EOM;

/**
 * Insert a basic textarea field
 */
$this->html['textarea'] = <<< EOM
<label for="%name%" class="wide">%label% %isrequired%</label><br />
<textarea name="%name%" id="%name%" cols="%cols%" class="resizable %haserror%"  rows="%rows%" style="%style%" tabindex="%tabindex%" >%value%</textarea>
%error%
%text%
EOM;

/**
 * Insert a password field
 */
$this->html['password'] = <<< EOM
<label for="%name%">%label% %isrequired%</label><br />
<input name="%name%" id="%name%" type="password" class="%haserror%" value="%value%" size="%size%"  style="%style%" tabindex="%tabindex%" />
%error%
%text%
EOM;

/**
 * Insert a hidden field. (will not be displayed on the form, but can be seen
 * using 'view source', so don't pass security related info this way.
 */
$this->html['hidden'] = <<< EOM
<input name="%name%" id="%name%" type="hidden" value="%value%" />
EOM;



/**
 * Insert a hidden CSRF check field. We fill the form in the browser with the
 * value of the 'cookie' cookie. On the serverside, this value will be compared
 * to the 'sessionvalue'. If they do not match, an error will be raised.
 *
 * For info on why this is necessary, see:
 * http://en.wikipedia.org/wiki/Cross-site_request_forgery.
 *
 */
$this->html['csrf'] = <<< EOM
<input name="csrfcheck" id="csrfcheck" type="hidden" value="" />
<script type="text/javascript">
$(function() {
    setTimeout('$("#csrfcheck").val( $.cookie("%cookie%"))', 500 );
});
</script>

EOM;


/**
 * Radio and radio_element are used together to create groups of radio buttons
 */
$this->html['radio'] = <<< EOM
<label class="wide">%label% %isrequired%</label>
<div class="set">
%elements%
</div>
%error%
%text%
EOM;


$this->html['radio_element'] = <<< EOM
<input type="radio" name="%name%" value="%value%" id="%formname%_%name%_%value%" %checked% class="radioinput noborder" tabindex="%tabindex%" />
<label for="%formname%_%name%_%value%">%label%</label>
EOM;



/**
 * Radiogrid and is used to create groups of radio buttons
 */
$this->html['radiogrid'] = <<< EOM
<div class="set">
%label% %isrequired%
%elements%
%error%
%text%
</div>
EOM;



/**
 * Select and select_element are used together to create select drop down menu"s
 */
$this->html['select'] = <<< EOM
<label class="wide" for="%name%">%label% %isrequired%</label>
<div class="set">
<select name="%name%" id="%name%" size="%size%" class="%haserror%"  %multiple% %extra%  tabindex="%tabindex%" >
%elements%
</select>
%multiple_selectors%
</div>
%error%
%text%
EOM;


$this->html['select_element'] = <<< EOM
<option value="%value%" %selected% %disabled% >%label%</option>
EOM;



$this->html['add_select'] = <<< EOM
<label for="%name%">%label% %isrequired%</label>
<table border="0" cellpadding="1">
    <tr>
    <td>
        <b>Selected</b><br />
        <select name="%name%[]" id="%name%" multiple size="12" style="width: 150px"
                onDblClick="moveOver("%name%","not%name%")" tabindex="%tabindex%" >
            %elements%
        </select>
    </td>
    <td align="center">
        <input type="button" value="&raquo; Remove" onclick="moveOver("%name%","not%name%")"
                style="width: 120px; margin: 3px;" /><br />
        <input type="button" value="&laquo; Add to selection"
                onclick="moveOver("not%name%","%name%")" style="width: 120px; margin: 3px;" /><br />

        <br />

        <input type="button" value="Move Up" onclick="moveUp("%name%")"
                style="width: 120px; margin: 3px;" /><br />
        <input type="button" value="Move Down" onclick="moveDown("%name%")"
                style="width: 120px; margin: 3px;" /><br />
    </td>
    <td>
        <b>Available</b>
        <br />
        <select name="not%name%[]" id="not%name%" multiple size="12"
                style="width: 150px" onDblClick="moveOver("not%name%","%name%")">
            %unselected-elements%
        </select>
    </td>
    </tr>
</table>
<script type="text/javascript">
    document.%formname%.onsubmit = function() { selectAll("%name%"); }
</script>
EOM;


/**
 * Some of the more obscure elements:
 */

$this->html['color_select'] = <<< EOM
<label for="%name%">%label% %isrequired%</label>
<select name="%name%" id="%name%" size="%size%" class="%haserror%" %multiple% onchange="this.style.background=this.value;" style="background-color:%value%" tabindex="%tabindex%">
%elements%
</select>
%error%
%text%
EOM;


$this->html['color_select_element'] = <<< EOM
<option value="%value%" %selected% style="background-color:%value%">%value% - %label%</option>
EOM;



$this->html['checkbox'] = <<< EOM
<label for="%formname%_%name%">%label% %isrequired%</label>
<input type="checkbox" name="%name%" value="1" %checked% id="%formname%_%name%" class="checkboxinput noborder" tabindex="%tabindex%" />
%error%
%text%
EOM;


/**
 * checkboxgrid is used to create groups of radio buttons
 */
$this->html['checkboxgrid'] = <<< EOM
<div class="set">
%label% %isrequired%
%elements%
%error%
%text%
</div>
EOM;

$this->html['checkboxgrid_element'] = <<< EOM
<input type="checkbox" name="%name%" value="%value%" %checked% id="%formname%_%name%" class="checkboxinput noborder" tabindex="%tabindex%" />
EOM;

$this->html['file'] = <<< EOM
<label for="%formname%_%name%">%label% %isrequired%</label>
<input name="%name%" type="file" value="%value%" id="%formname%_%name%" class="%haserror%" size="%size%" style="%style%" tabindex="%tabindex%" />
%error%
%text%
EOM;


/**
 * How errors in your forms are displayed.
 */
$this->error = <<< EOM
<div class="error">%error%</div>
EOM;

/**
 * The class to be added to a field that has an error..
 */
$this->haserror = "error";

/**
 * How 'required' elements are shown in the form.
 */
$this->isrequired = <<< EOM
<span class="required">*</span>
EOM;
?>
[[ include file="inc_window_header.tpl" ]]

<script type="text/javascript" src="[[$paths.pivotx_url]]includes/uploader/swfupload.js"></script>
<script type="text/javascript" src="[[$paths.pivotx_url]]includes/uploader/swfupload.queue.js"></script>
<script type="text/javascript" src="[[$paths.pivotx_url]]includes/uploader/fileprogress.js"></script>
<script type="text/javascript" src="[[$paths.pivotx_url]]includes/uploader/handlers.js"></script>
[[ literal ]]
<script type="text/javascript">
//<![CDATA[

var imagearray = new Array();

//We need to submit this to the opener, that is to the editor
function do_submit(){

    var f_image = trim($('#f_image').val());
    var f_image_alt = trim($('#f_image_alt').val());
    var f_image_title = trim($('#f_image_title').val());

    if (f_image) {
        imagearray[ imagearray.length ] = f_image + "###" + f_image_title + "###" + f_image_alt;
    } else {
        f_image = imagearray[ imagearray.length-1 ];
        imagearray[ imagearray.length-1 ] = f_image + "###" + f_image_title + "###" + f_image_alt;
    }

    // If window.opener is unknown, we've opened the image inserter as a Dialog. then 
    /// we can use top.frames[0]
    if (window.opener==null) {
        top.addToGallery(imagearray);
    } else {
        window.opener.addToGallery(imagearray);
        window.close();
    }

}



/**
 * Javascript for dynamic uploader..
 */
var swfu;

window.onload = function() {
    var settings = {
        flash_url : "[[/literal]][[$paths.pivotx_url]][[literal]]includes/uploader/swfupload.swf",
        upload_url: "[[/literal]][[$paths.pivotx_url]][[literal]]includes/editor/handle_upload.php",	
        file_post_name: "userfile",
        file_size_limit : "[[/literal]][[if $config.upload_max_filesize!=""]][[$config.upload_max_filesize]][[else]]2 MB[[/if]][[literal]]",
        file_types : "*.jpg;*.gif;*.jpeg;*.png",
        file_types_description : "Images",
        file_upload_limit : 100,
        file_queue_limit : 0,
        custom_settings : {
            progressTarget : "divFileProgressContainer",
            cancelButtonId : "btnCancel"
        },
        debug: false,

        post_params : {
            pivotxsession: [[/literal]]"[[$pivotxsession]]"[[literal]]
        },

        // Button settings
        button_image_url: "[[/literal]][[$paths.pivotx_url]][[literal]]includes/uploader/button.png",	// Relative to the Flash file
        button_width: "199",
        button_height: "31",
        button_placeholder_id: "spanButtonPlaceHolder",
        button_text: '<span class="theFont"> </span>',
        button_text_style: ".theFont { font-size: 14; }",
        button_text_left_padding: 12,
        button_text_top_padding: 3,
        
        // The event handler functions are defined in handlers.js
        file_queued_handler : fileQueued,
        file_queue_error_handler : fileQueueError,
        file_dialog_complete_handler : fileDialogComplete,
        upload_start_handler : uploadStart,
        upload_progress_handler : uploadProgress,
        upload_error_handler : uploadError,
        upload_success_handler : pivotUploadSuccess,
        upload_complete_handler : uploadComplete,
        queue_complete_handler : queueComplete	// Queue plugin event
    };

    swfu = new SWFUpload(settings);
 };



function pivotUploadSuccess(file, serverData) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		progress.setStatus("Complete.");
		progress.toggleCancel(false);

	} catch (ex) {
		this.debug(ex);
	}

    filename = trim(serverData);
    imagearray[ imagearray.length ] = filename; 

}


// Submitting and cancelling..
jQuery(function($) {

    // Attach event for 'insert image' button.
    $("#button_submit").click(function(event, data, formatted) {
        do_submit();
    });

    // Attach event for 'cancel' button.
    $("#button_cancel").click(function(event, data, formatted) {
        if (window.opener==null) {
            top.$('#dialogframe').dialog('close');
        } else {
            self.close();
        }
    });

    // Make sure the window is on top..
    self.focus();

});

/**
 * End of Javascript for dynamic uploader..
 */



//]]>
</script>
[[/literal]]
</head>
<body style="margin: 0 12px 0 0; background-image: none; background-color: #FFF;">

[[ if $msg != "" ]]
    <p style="background-color:#FFF6BF; border-bottom:1px solid #FFD324; border-top:1px solid #FFD324; margin-bottom:6px;   min-height:16px; padding:6px;">[[ $msg ]]</p>
[[ /if ]]

<table border="0" cellspacing="0" cellpadding="2" class="formclass" style="border:0px;">


    <tr>
        <td class="nowrap">
            <b>[[t]]Upload[[/t]]:</b>
        </td>
        <td colspan="2">


        <form style="clear:both;">
        
            <p style="margin: 2px 0px;" class="buttons">
    

                <span id="spanButtonPlaceHolder">
                    <a href="#">
                        <img src="../../pics/page_lightning.png" alt="" />[[t]]Upload an image[[/t]] 
                        <span style="font-size: 7pt;">([[t]]2 MB Max[[/t]])</span>
                    </a>                    
                    
                    
                    
                </span>
                <!--  
                <p><small><strong>[[t]]Tip[[/t]]:</strong> 
                    [[t]]Use 'ctrl' to to select multiple images.[[/t]]</small>
                </p>
                -->
        
            </p>

        </form>
		<div id="divFileProgressContainer" style="width:330px; clear:both;"></div>

        <a href="#" id="btnCancel"  onclick="swfu.cancelQueue();"></a> 

        </td>
    </tr>

    <tr>
        <td class="nowrap">
            <b>[[t]]Image name[[/t]]:</b>
        </td>
        <td>
            <input type='text' name='f_image' id='f_image' size='25' value='[[ $imagename ]]' class='input' style='width: 230px;' />
        </td>
        <td class="buttons_small">
            <a href="#" onclick="top.openFileSelector('[[t]]Select an image[[/t]]', $('#f_image'), 'gif,jpg,png');">
                <img src='../../pics/page_lightning.png' alt="" /> [[t]]Select[[/t]]
            </a>

        </td>
    </tr>

    <tr>
        <td class="nowrap">
            <b>[[t]]Alternate text[[/t]]:</b>
        </td>
        <td colspan="3">
            <input type='text' name='f_image_alt' id='f_image_alt' size='25' value='' class='input' />
       </td>
    </tr>

         <tr>
        <td class="nowrap">
            <b>[[t]]Title[[/t]]:</b>
        </td>
        <td colspan="3">
            <input type='text' name='f_image_title' id='f_image_title' size='25' value='' class='input' />
       </td>
    </tr>

    <tr>
        <td colspan="3">

            <input type='hidden' name='f_target' id='f_target' value='[[ $target ]]' />

            <p style="margin: 8px 0px;" class="buttons">

            <a href="#" class="positive" id='button_submit'>
            <img src="../../pics/tick.png" alt="" />[[t]]Add to gallery![[/t]]</a>

            <a href="#" class="negative" id='button_cancel'>
            <img src="../../pics/delete.png" alt="" />[[t]]Cancel[[/t]]</a>
            </p>

        </td>
    </tr>

</table>
</form>




</body>
</html>

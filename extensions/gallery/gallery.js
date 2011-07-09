

jQuery(function($) {
    previewGalleryThumbnails();
});

/**
 * Open the window to insert (and/or upload) an image in an entry or page.
 */
function openGalleryUploadWindow(title,target) {

    var my_url = 'extensions/gallery/upload_images.php';    
    openDialogFrame(title, my_url, 600, 390);

}

/**
 * Add files to the gallery. Called from the 'uploader' popup.
 */
function addToGallery(imagearray) {
    
    $('#dialogframe').dialog('close');    
    
    var filenames = $('#extrafield-galleryimagelist').val();
    filenames = trim(filenames);
    if (filenames.length > 10) {
        filenames += "\n";
    }
    
    for (i=0;i<imagearray.length;i++) {
        filenames += imagearray[i]+"\n";
    }
    
    $('#extrafield-galleryimagelist').val(filenames);
    
    previewGalleryThumbnails();
    
}

/**
 * Update the thumbnails. Parse the (hidden) list of files, and create the images
 * on the fly..
 */
function previewGalleryThumbnails() {
    
    $('#gallerythumbnails').html('');
    
    var dummy = new Array();

    var filenames = $('#extrafield-galleryimagelist').val();
    filenames = explode("\n", filenames);

    var html = "";

    var filename, title;
    for (i=0; i<filenames.length; i++) {
        dummy = explode("###", filenames[i]);
        filename = String(dummy[0]);
        title = filename;
        if (dummy[1]) {
            title += " | " + String(dummy[1]);
        }
        if (dummy[2]) {
            title += " | " + String(dummy[2]);
        }
        if (filename.length > 3) {
            html += "<img src='./includes/timthumb.php?src=" + filename + "&w=70&h=70&zc=1' width='70' height='70' alt='" + 
                filename + "' title='" + title + "' /></li>"
        }
    }

    html += "<div class='cleaner'>&nbsp;</div>";
    
    $('#gallerythumbnails').html(html);
    
    // Add 'drag and drop' for the thumbnails and the wastebin.
    $('.gallerysortable').sortable({
        placeholder: 'ghost',
        connectWith: ['.gallerysortable'],
        items: 'img',
        stop: function() { galleryStopDrag(); }
    });
       
}


/**
 * Handler for when the user stops dragging..
 */
function galleryStopDrag() {
        
    var filenames = "";

    // Make a list of all images, in the right order. (The filename, title and
    // description is stored in the title attribute.)
    $('#gallerythumbnails img').each(function(){
        filenames += $(this).attr('title').replace(/ \| /g, "###") + "\n"; 
    });
    
    // Fill the hidden field with the list
    $('#extrafield-galleryimagelist').val(filenames);
    
    // Empty the wastebin..
    $('#gallerywastebin img').fadeOut();
    
}


function explode( delimiter, string, limit ) {
    // Split a string by string
    // 
    // +    discuss at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_explode/
    // +       version: 809.522
    // +     original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     improved by: kenneth
    // +     improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     improved by: d3x
    // +     bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: explode(' ', 'Kevin van Zonneveld');
    // *     returns 1: {0: 'Kevin', 1: 'van', 2: 'Zonneveld'}
    // *     example 2: explode('=', 'a=bc=d', 2);
    // *     returns 2: ['a', 'bc=d']
 
    var emptyArray = { 0: '' };
    
    // third argument is not required
    if ( arguments.length < 2
        || typeof arguments[0] == 'undefined'
        || typeof arguments[1] == 'undefined' )
    {
        return null;
    }
 
    if ( delimiter === ''
        || delimiter === false
        || delimiter === null )
    {
        return false;
    }
 
    if ( typeof delimiter == 'function'
        || typeof delimiter == 'object'
        || typeof string == 'function'
        || typeof string == 'object' )
    {
        return emptyArray;
    }
 
    if ( delimiter === true ) {
        delimiter = '1';
    }
    
    if (!limit) {
        return string.toString().split(delimiter.toString());
    } else {
        // support for limit argument
        var splitted = string.toString().split(delimiter.toString());
        var partA = splitted.splice(0, limit - 1);
        var partB = splitted.join(delimiter.toString());
        partA.push(partB);
        return partA;
    }
}

function showGallery() {

    $('#galleryrow1, #galleryrow3').slideUp();
    $('#galleryrow2, #galleryrow4, #galleryrow5').slideDown();

}

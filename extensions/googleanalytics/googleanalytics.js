
jQuery(function($) {
   
    $('#ga-simpletabs span').bind('click', function() {

        $('#ga-simpletabs span').not(this).removeClass('active');
        $(this).addClass('active');     
    
        $('.ga-simpletab').hide();
        $('#ga-' + $(this).attr('id') ).show();
    
    });
    
    $('#ga-simpletabs .first').trigger('click');
    
    
    
});
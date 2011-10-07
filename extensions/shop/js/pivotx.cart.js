jQuery(function($){
    $('.buythisform.simple form').bind('submit', function(e) {
        e.preventDefault();
        
        $(this).append('<input type="hidden" name="fromajax" value="yes" />');
        
        //console.log('submit form: ', $(this).attr('action'), $(this).serializeArray());
        
        $.fancybox.showActivity();
   
        $.ajax({
            type	: "POST",
            cache	: false,
            url		: $(this).attr('action'),
            data	: $(this).serializeArray(),
            success: function(data) {
                //$.fancybox('<p>Je item is toegevoegd aan de cart</p>');
                $.fancybox(data);
                // update the shopping cart in the sidebar
                $('.shoppingcartcontainer').html(data);
                // remove the addtocart message in the sidebar
                $('.shoppingcartcontainer').find('.cartaddmessage').remove();
            }
        });
    
    });
    
    
    $('.buythisform.update form .buythisbutton').addClass('disabled').attr("disabled", true);
    $('.buythisform.update input.productamount').bind('change', function(e) {
        e.preventDefault();
        $(this).parents('form').find('.buythisbutton').removeClass('disabled').attr("disabled", false);
        $('.formrow_submit button.button').addClass('disabled').attr("disabled", true);
    });
    
    if($('.shop_autorefreshmessage').is('*')) {
        $('.shop_autorefreshform').trigger('submit');
    }
    
    $('#fancybox-content a.continue_shopping').live('click', function(e){
        e.preventDefault();
        $.fancybox.close();
    });
});
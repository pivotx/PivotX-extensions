jQuery(function($){
    $('.buythisform.simple form').bind('submit', function(e) {
        e.preventDefault();

        $(this).append('<input type="hidden" name="fromajax" value="yes" />');
        
        //console.log('submit form: ', $(this).attr('action'), $(this).serializeArray());

        var ok = true;
        var message = '';
        $('input, textarea, select',this).each(function(){
            if (($(this).attr('data-condition') != undefined) && ($(this).attr('data-condition') != '')) {
                switch ($(this).attr('data-condition')) {
                    case 'required':
                        if ($(this).val() == '') {
                            message = $(this).attr('data-condition-error-message');
                            ok = false;
                        }
                        break;
                }
            }
        });
   
        if (ok) {
            $('p.message',this).hide();

            if(!$.browser.msie) {
                $.fancybox.showActivity();
            }

            $.ajax({
                type	: "POST",
                cache	: false,
                url		: $(this).attr('action'),
                data	: $(this).serializeArray(),
                success: function(data) {
                    //$.fancybox('<p>Je item is toegevoegd aan de cart</p>');
                    $.fancybox(
                        data,
                        {
                            'autoDimensions' : false,
                            'width' : 350,
                            'height' : 'auto'
                        }
                    );
                    // update the shopping cart in the sidebar
                    $('.shoppingcartcontainer').html(data);
                    // remove the addtocart message in the sidebar
                    $('.shoppingcartcontainer').find('.cartaddmessage').fadeOut().remove();
                }
            });
        }
        else {
            $('p.message',this).html(message);
            $('p.message',this).show();
        }
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

    $('.cart-extras .check input').bind('change',function(e){
        document.location = $(this).attr('data-href');
    });
});
jQuery(function($){
    $('.buythisform.simple form').bind('submit', function(e) {
        e.preventDefault();
        
        $(this).append('<input type="hidden" name="fromajax" value="yes" />');
        
        console.log('submit form: ', $(this).attr('action'), $(this).serializeArray());
        
        $.fancybox.showActivity();
   
        $.ajax({
            type	: "POST",
            cache	: false,
            url		: $(this).attr('action'),
            data	: $(this).serializeArray(),
            success: function(data) {
                //$.fancybox('<p>Je item is toegevoegd aan de cart</p>');
                $.fancybox(data);
                $('.shoppingcartcontainer').html(data);
            }
        });
    
    });
    
    $('#fancybox-content a.continue_shopping').live('click', function(e){
        e.preventDefault();
        $.fancybox.close();
    });
});
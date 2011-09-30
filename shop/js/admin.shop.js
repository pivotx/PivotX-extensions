jQuery(function($){
    $('.ajax-table td div.popup').each(function() {
        //console.log($(this));
        //$(this).hide().removeClass('hidden');
        $(this).parent().prepend('<a href="#" rel="'+$(this).attr('id')+'" class="openpopup button">+</a>');
    });
    
    // confirm negative actions
    $('.ajax-table td button.negative').bind('click', function(e) {
        e.preventDefault();
        return confirm('Are you sure you want to '+ $(this).text() +'?');
    });

    $('a.openpopup').live('click', function(e) {
        e.preventDefault();
        console.log($(this).attr('rel'), e);
        $('#'+$(this).attr('rel')).dialog({
            title: $('#'+$(this).attr('rel')).find('h4').text(),
            modal: true,
            position: [e.clientX-20,e.clientY-50]
        });
        $('#'+$(this).attr('rel')).find('h4').remove();
    });
});
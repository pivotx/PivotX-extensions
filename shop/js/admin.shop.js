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
    
    // prefill form fields with default values
    $('a.defaultoptions').bind('click', function(e) {
        e.preventDefault();
        target = $(this).attr('rel');
        console.log('defaultvalues ', target, this);
        // small / medium / large / extra large
        if($(this).text() == 'smlxl') {
            $('#'+target).val("s::Small\nm::Medium\nl::Large\nxl::Extra Large");
        }
    });
    
    // add actions to entry edit form
    if($('form .rightcolumn select').is('*')) {
        $("form .rightcolumn select").each(function(){
            var name = $(this).attr("name");
            if(name=="categories[]") {
                shopUpdate(this);
            
                $(this).bind("change",function(e){
                    shopUpdate(this);
                });
            }
        });
        
        $("#extrafield-item_price").bind("change", function(e) {
            var tax = 1 * $("#extrafield-item_tax").val();
            var taxless = 1 * $(this).val();
            var inctax = taxless + (taxless * tax);
            //console.log(taxless, tax, (taxless * tax), inctax);
            $("#extrafield-item_price_incl_tax").val(inctax);
        });
        $("#extrafield-item_tax").bind("change", function(e) {
            var tax = 1 * $("#extrafield-item_tax").val();
            var taxless = 1 * $("#extrafield-item_price").val();
            var inctax = taxless + (taxless * tax);
            $("#extrafield-item_price_incl_tax").val(inctax);
        });
        $("#extrafield-item_price_incl_tax").bind("change", function(e) {
            var tax = 1 * $("#extrafield-item_tax").val();
            var inctax = 1 * $(this).val();
            var taxless = inctax / (1 + tax);
            //console.log(taxless, tax, (1 + tax), inctax);
            $("#extrafield-item_price").val(taxless);
        });
    }
    
    function shopUpdate(el) {
        var found = false;
        $("option:selected", el).each(function(){
            if($(this).attr("value")==shopcategory) {
                found = true;
            }
            if((shopextrascategory!='') && ($(this).attr("value")==shopextrascategory)) {
                found = true;
            }
        });
     
        if (found) {
            $(".shopvisible").removeClass("hidden").slideDown("fast");
        } else {
            $(".shopvisible").addClass("hidden").slideUp(0);
        }
    }
});
jQuery(document).ready(function ($) {

    /**
     * Show/Hide amount option according to amount type.
     */
    $("input[name=_codup_gc_amount_type]").on('change', (function () {

        var radio_value = $(this).val();

        if (radio_value == 'preset' && $(this).is(':checked')) {
            $(".amount-range").hide();
            $(".gift_card_range").prop('required', false);
            $(".gift-card-amount").show();
            $("._gift_card_amount_field").show();
        } else if (radio_value == 'userdefined' && $(this).is(':checked')) {
            $(".amount-range").show();
            $(".gift_card_range").prop('required', true);
            $(".gift-card-amount").hide();
            $("._gift_card_amount_field").hide();
        }

    }));
    /**
     * Show/Hide amount option according to amount type.
     */
    $("input[name=codup-gc-expires]").on('change', (function () {
        
        cb = jQuery(this);
        if (cb.prop('checked')) {
            jQuery('.cgc-exp').show();
            toggle_validation(true);
        } else {
            jQuery('.cgc-exp').hide();
            toggle_validation(false);
        }

    })).trigger('change');

    /**
     * update min of Maximum Gift card price.
     */
    $("input[name=_codup_gc_minimum]").change(function () {
        var min_value = $(this).val();
        $("input[name=_codup_gc_maximum]").attr('min', min_value);

    });

    /**
     * Add a new amount to current gift card
     * @param item
     */
    function add_amount(item) {
        var data = {
            'action': 'codup_add_gift_card_amount',
            'amount': $("#gift_card-amount").val(),
            'product_id': $("#post_ID").val()
        };

        var clicked_item = item.closest("span.add-new-amount-section");
        clicked_item.block({
            message: null,
            overlayCSS: {
                background: "#fff  no-repeat center",
                opacity: .6
            }
        });

        $.post(wcgc_vars.ajax_url, data, function (response) {

            if (!response.success) {
                $('#amount_validation').text(response.message);

                setTimeout(function () {
                    $('#amount_validation').delay(30000).text('');
                }, 3000);

            }

            $("p._gift_card_amount_field").replaceWith(response.card_amounts);
            $("#gift_card-amount").val('');
            clicked_item.unblock();
        });
    }

    /**
     * Add a new amount for the current gift card
     */
    $(document).on("click", "a.add-new-amount", function (e) {
        e.preventDefault();
        add_amount($(this));
    });
    

    /**
     * Add a new amount for the current gift card
     */
    $(document).on('keypress', 'input#gift_card-amount', function (e) {
        if (event.which === 13) {

            e.preventDefault();

            //Disable textbox to prevent multiple submit
            $(this).attr("disabled", "disabled");

            //Do Stuff, submit, etc..
            add_amount($(this));

            $(this).removeAttr("disabled");

        }
    });
    $("input[name=_codup_gc_amount_type]").change();
    
    /**
     * Remove the price option .
     */
    $(document).on("click", "a.gc-remove-amount", function (e) {
        e.preventDefault();

        var data = {
            'action': 'codup_remove_gift_card_amount',
            'amount': $(this).closest("span.variation-amount").find('input[name="gift-card-amounts[]"]').val(),
            'product_id': $("#post_ID").val()
        };

        var clicked_item = $(this).closest("span.variation-amount");
        clicked_item.block({
            message: null,
            overlayCSS: {
                background: "#fff  no-repeat center",
                opacity: .6
            }
        });

        $.post(wcgc_vars.ajax_url, data, function (response) {

            if (1 == response.code) {
                clicked_item.remove();
            }

            clicked_item.unblock();
        });

    });
    /**
     *  save range for current gift card product.
     */
    $(document).on("click", "#save-range", function (e) {
        
       var response = validate_range();
        if(response.valid){
            save_range();
            $('#success-message').delay(30000).text('Saved')
            setTimeout(function () {
                $('#success-message').delay(30000).text('');
            }, 3000);
        }else{
            $('#range_validation').text(response.message);

                setTimeout(function () {
                    $('#range_validation').delay(30000).text('');
                }, 3000);
        }

    });
    function save_range() {
        var data = {
            'action': 'codup_add_gift_card_range',
            'min': $("input[name=_codup_gc_minimum]").val(),
            'max': $("input[name=_codup_gc_maximum]").val(),
            'product_id': $("#post_ID").val()
        };
        $.post(wcgc_vars.ajax_url, data, function (response) {

            
        });
    }
    
    
    function validate_range(){
        var min  = parseFloat( $("input[name=_codup_gc_minimum]").val());
        var max  = parseFloat($("input[name=_codup_gc_maximum]").val());
        var valid = true;
         var message = '' ;

        if(!min){
            message = 'Minimum is required.\n\r';
            valid = false;
        }
        if(!max){
            message += 'Maximum is required.\n\r';
            valid = false;
        }
        
        if(min  && max  && min > max ){
            message += 'Maximum should be greater than ' + min + '.' ;
            valid = false;
        }
        return  {valid: valid , message :message};
    }
    function toggle_validation(show){
        
        var that = '#codup-gc-exp-days';
        var attr = $(that).attr('min');
        if (show !== false) {
            $(that).removeAttr('min step');
        }else{
            $(that).attr({
                min:"0", 
                step:"1"
            });
        }
    }
});
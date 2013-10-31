(function($) {
    var banner                               = $("#blog_banner");
    var bannerHeight                         = $("#icap_blog_options_form_banner_height");
    var bannerBackgroundImageColorPicker     = $('.banner_background_color');
    var bannerBackgroundColorField           = $('#icap_blog_options_form_banner_background_color');
    var bannerBackgroundImageContainer       = $("#icap_blog_options_form_banner_background_image_container");
    var bannerBackgroundImageFieldTemplate   = '<input type="file" id="icap_blog_options_form_banner_background_image" name="icap_blog_options_form[banner_background_image]" class="form-control">';
    var bannerBackgroundImagePositionField   = $("#icap_blog_options_form_banner_background_image_position").hide();
    var removeBannerBackgroundImageButton    = $("#remove_banner_background_image");
    var bannerBackgroundImageParametersBlock = $("#banner_background_image_parameters");
    var bannerBackgroundImagePositionBlock   = $(".position_table", bannerBackgroundImageParametersBlock);
    var bannerBackgroundImageRepeatField     = $("#icap_blog_options_form_banner_background_image_repeat", bannerBackgroundImageParametersBlock).hide();
    var bannerBackgroundImageRepeatBlock     = $("#icap_blog_options_form_banner_background_image_repeat_choices", bannerBackgroundImageParametersBlock);

    bannerBackgroundImageColorPicker.colorpicker({format: 'hex'}).on('changeColor', function (event) {
        changeBannerBackgroundColor(event.color.toHex());
    });
    bannerBackgroundColorField.change(function (event) {
        changeBannerBackgroundColor($(this).val());
    });

    function changeBannerBackgroundColor(color)
    {
        bannerBackgroundColorField.val(color);
        console.log($(".input-group-addon", this));
        $(".input-group-addon", bannerBackgroundImageColorPicker).css('background-color', color);
        banner.css('background-color', color);
    }

    $("#icap_blog_options_form_banner_activate").change(function (event) {
        banner.toggleClass('hidden');
    });

    bannerHeight.spinner(bannerHeight.data());
    bannerHeight
        .on('spin', function (event, ui) {
            var newHeight = $(this).val();
            banner.css('height', newHeight);
        })
        .change(function (event) {
            var newHeight = $(this).val();

            if (100 > newHeight) {
                $(this).val(100);
            }
            banner.css('height', $(this).val());
        });

    bannerBackgroundImageContainer.on('change', "#icap_blog_options_form_banner_background_image", function(){
        var input = this;
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (event) {
                banner.css('background-image', 'url(' + event.target.result + ')');
                removeBannerBackgroundImageButton.removeClass("hidden");
                bannerBackgroundImageParametersBlock.removeClass("hidden");
            };

            reader.readAsDataURL(input.files[0]);
        }
    });

    removeBannerBackgroundImageButton.click(function (event) {
        banner.css('background-image', 'none');
        removeBannerBackgroundImageButton.addClass("hidden");
        bannerBackgroundImageParametersBlock.addClass("hidden");
        $("#icap_blog_options_form_banner_background_image", bannerBackgroundImageContainer).remove();
        bannerBackgroundImageContainer.append(bannerBackgroundImageFieldTemplate);
    });

    $("input[type=checkbox]", bannerBackgroundImageRepeatBlock).click(function (event) {
        var checkbox       = $(this);
        if (checkbox.is(':checked')) {
            bannerBackgroundImageRepeatField.val(parseInt(bannerBackgroundImageRepeatField.val()) + parseInt(checkbox.val()));
        }
        else {
            bannerBackgroundImageRepeatField.val(parseInt(bannerBackgroundImageRepeatField.val()) - parseInt(checkbox.val()));
        }

        updateBannerBackgroundImageRepeat();
    });

    function updateBannerBackgroundImageRepeat() {
        var repeatValue    = bannerBackgroundImageRepeatField.val();
        var repeatString   = "no-repeat";

        switch(repeatValue) {
            case '0':
                break;
            case '1':
                repeatString  = "repeat-x";
                break;
            case '2':
                repeatString = "repeat-y";
                break;
            case '3':
                repeatString = "repeat";
                break;
        }

        banner.css('background-repeat', repeatString);
    }

    $(".orientation_btn", bannerBackgroundImagePositionBlock).click(function (event) {
        $(".orientation_btn.selected", bannerBackgroundImagePositionBlock).removeClass('selected');
        $(this).addClass('selected');
    });
})(jQuery);
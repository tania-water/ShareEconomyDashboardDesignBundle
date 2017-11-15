$.validator.addMethod('mobile', function (value, element) {
    if (this.optional(element) || $(element).is(':focus')) {
        return true;
    }
    var $element = $(element);
    var elementValue=$element.intlTelInput("getNumber", intlTelInputUtils.numberFormat.NATIONAL);
    if ($.trim(elementValue.replace('+'+$element.intlTelInput("getSelectedCountryData").dialCode),'')!='' && $.trim(elementValue.replace('+'+$element.intlTelInput("getSelectedCountryData").dialCode),'') !='undefined' ) {
    if ($element.intlTelInput("isValidNumber")) {
     return true;
    } else {

      return false;
    }
  }
  return true;
//    $element.attr('data-remove-color', 'false');
//    var phoneText = formatPhoneText($element);
//    var countryElement = $element.parent().find('select.dev-phone-country-select');
//    var phoneCountry = countryForE164Number(phoneText);
//    if (phoneCountry.toUpperCase() !== countryElement.val().toUpperCase()) {
//        $element.attr('data-remove-color', 'false');
//        return false;
//    }
//    return isValidNumber(phoneText, countryElement.val());
}, 'phone must be in the right format');

    $.validator.addMethod('youtube', function (value, element) {
        return value.match(/^(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?(?=.*v=((\w|-){11}))(?:\S+)?$/);
    },"invalid youtube url");

$.validator.addMethod('email', function(value, element) {
//    return this.optional(element) || /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(value);
    $(element).attr('data-remove-color', 'false');
    if (!value) {
        return true;
    }
    if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(value))
    {
        var emailVal = new Array();
        emailVal = value.split('@');
        if(emailVal[0].length > 64 || emailVal[1].length > 64){
            return false;
        }
        return true;
    }
    return false;
}, $.validator.format('Please enter a valid email address.'));

$.validator.addMethod('alphaNum', function (value, element) {
    var unicodeWord = "^[a-zA-Z0-9\u0600-\u06ff\-]{0,}$";
    var match = value.match(unicodeWord);
    if (match == null) {
        return false;
    }
    else
    {
        return true;
    }
}, $.validator.format('This value only allows letters or numbers'));

$.validator.addMethod('decimal', function(value, element) {
    return this.optional(element) || /^[0-9,]+(\.\d{0,2})?$/.test(value);
}, "Please enter a correct number, format xxxx.xx");

$.validator.addMethod('staffUsername', function (value, element) {
    var unicodeWord = "^[a-zA-Z0-9\u0600-\u06ff\-]{0,}$";
    var match = value.match(unicodeWord);
    if (match == null) {
        return false;
    }
    else
    {
        return true;
    }
}, $.validator.format('not valid'));

$.validator.addMethod('filesize', function (value, element, param) {
    // param = size (bytes)
    // element = element to validate (<input>)
    // value = value of the element (file name)

    return this.optional(element) || (element.files[0].size <= (param * 1024 * 1024));
}, $.validator.format('file must be less than {0} mb'));

$.validator.addMethod('phone', function (value, element) {

    var regEx = /^[+]?\d+$/;
    return regEx.test(value);
});

$.validator.addMethod('imageRequired', function (value, element) {
    return $('.'+element.id+'_preview').find('img').length;
});

$.validator.addMethod('imageDimensions', function (value, element, param) {

    var width = $(element).attr('data-image-width');
    var height = $(element).attr('data-image-height');
    if (width && height) {
        return this.optional(element) || (width >= param && height >= param)
    } else {
        return true;
    }
}, 'image dimensions must be greater than {0}*{0}');

$.validator.addMethod('letters', function (value, element) {
    var unicodeWord = "^[a-zA-Z\u0600-\u06ff\-]{0,}$";
    return value.match(unicodeWord);
});

$.validator.addMethod('minval', function (value, element) {
    return value>0;
}, 'value must be greater than 0');

$.validator.addMethod('dimensions', function (value, element, param) {

    var width = $(element).attr('data-image-width');
    var height = $(element).attr('data-image-height');
    if (width && height) {
        return this.optional(element) || (width >= param && height >= param)
    } else {
        return true;
    }
}, 'image dimensions must be greater than 200*200');



$.validator.addMethod('passwordMin', function (value, element) {
    return this.optional(element) || (/\D+/.test(value) && /\d+/.test(value) && value.length >= 6) || $(element).attr('data-remove-password-validation') === 'true';
}, 'The Password must be at least {{ limit }} characters and numbers length');

$.validator.addMethod('passwordMax', function (value, element) {
    return this.optional(element) || value.length < 4096 || $(element).attr('data-remove-password-validation') === 'true';
}, 'The Password must be {{ limit }} maximum characters and numbers length');

$.validator.addMethod('mincheck', function (value, element, options) {
    return $('input[name="' + $(element).attr('name') + '"]:checked').length >= parseInt(options);
}, $.validator.format('Please select at least {0} of these fields.'));


$.validator.addMethod('unique', function (value, element) {
    if (($(element).is(':focus') && 'undefined' === typeof $(element).attr('data-unique-valid'))) {
        return true;
    }
    if (this.optional(element) || $(element).attr('data-force-unique-valid') === 'true' || $(element).closest('form').attr('data-force-unique-valid') === 'true') {
        $(element).attr('data-remove-color', 'true');
        return true;
    }
    if ('undefined' === typeof $(element).attr('data-unique-valid')) {
        $(element).attr('data-remove-color', 'true');
        setTimeout(function() {
            checkUniqueValidation(element);
        }, 200);
        return true;
    }
    if ($(element).attr('data-unique-valid') === 'true') {
        return true;
    }
}, $.validator.format('unique'));




/**
 * remove error block
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @param object $helpBlock
 */
function removeUniquHelpBlock($helpBlock) {
    if ($helpBlock.length > 0) {
        if ($helpBlock.html() === unAvailableMessage || $helpBlock.html() === availableMessage) {
            $helpBlock.remove();
        }
    }
}

/**
 * remove error or success highlight and message
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @param object element
 * @param boolean removeUniqueMessageOnly
 */
function unhighlightElement(element, removeUniqueMessageOnly) {
//    console.log('unhighlight: ');
    var $this = $(element);
    var errorSelector = '.help-block';
    if($this.attr('data-error-template-selector')) {
        errorSelector = $this.attr('data-error-template-selector');
    }
    var errorAfterSelector = $this.attr('data-error-after-selector');
    if (errorAfterSelector) {
        if (removeUniqueMessageOnly) {
            return removeUniquHelpBlock($this.closest(errorAfterSelector).next(errorSelector));
        }
        $this.closest(errorAfterSelector).next(errorSelector).remove();
    }
    var errorAtStartOfSelector = $this.attr('data-error-at-start-of-selector');
    if (errorAtStartOfSelector) {
        if (removeUniqueMessageOnly) {
            return removeUniquHelpBlock($('body').find(errorAtStartOfSelector).find(errorSelector));
        }
        $('body').find(errorAtStartOfSelector).find(errorSelector).remove();
    }
    if (removeUniqueMessageOnly) {
        return removeUniquHelpBlock($this.closest('.form-group').find(errorSelector));
    }
//    console.log(element);
    if ($this.attr('data-error-selector')) {
        errorSelector = $this.attr('data-error-selector');
        $(errorSelector).html('');
    } else {
        $this.closest('.form-group').removeClass('has-error').removeClass('has-success').find(errorSelector).remove();
        $this.siblings('.dev-loader').hide();

    }
}

/**
 * set success status for valid field
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @param object element
 */
function markElementAsValid(element) {
//console.log('valid')
    if ($(element).hasClass('dev-datetimepicker')) {
       $(element).closest('.form-group').removeClass('has-error').find('.help-block').remove();
       }
    $(element).closest('.form-group').addClass('has-success');
    $(element).closest('.form-group').find('.dev-failLoader').hide();
    $(element).closest('.form-group').find('.dev-successLoader').show();

    if ($(element).attr('data-validation-message') === availableMessage) {
        if ($(element).val().trim() != '') {
            $(element).after('<span class="help-block">' + $(element).attr('data-validation-message') + '</span>');
        }

        // fix bug 17291
        if($(element).attr('data-name') == "mobile.phone" && $(element).closest('.form-group').find('.help-block').length > 1){
            $(element).closest('.form-group').find('.help-block').eq(0).replaceWith($(element).closest('.form-group').find('.help-block').eq(1));
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        }
    }
    if ($(element).hasClass('dev-datetimepicker')) {
        $(element).find('.dev-startingDateTime').parent('div').find('.help-block').remove();

    }
}


function markElementAsNotValid(element) {
//    console.log('element not valid');
//    console.log(element);
    if (($(element).val() && $(element).val().trim() != '' && $(element).attr('data-validation-message')) || $(element).hasClass('dev-datetimepicker')) {
        if (typeof $(element).attr('data-error-after-selector') != "undefined") {
            if ($(element).hasClass('dev-datetimepicker')) {
                $($(element).attr('data-error-after-selector')).closest('.dev-datetimepicker-container').find('.help-block').remove();
                $($(element).attr('data-error-after-selector')).after('<span class="help-block">' + $(element).attr('data-validation-message') + '</span>');

            } else {
                $(element).parents($(element).attr('data-error-after-selector')).after('<span class="help-block">' + $(element).attr('data-validation-message') + '</span>');

            }
        } else {
            $(element).parent().find('.help-block').after('<span class="help-block">' + $(element).attr('data-validation-message') + '</span>');
        }

    }
    if(typeof $(element).attr('data-parent-element') != 'undefined'){
        $(element).closest($(element).attr('data-parent-element')).addClass('has-error');
    }
    $(element).closest('.form-group').addClass('has-error');
    $(element).closest('.form-group').find('.dev-failLoader').show();
    $(element).closest('.form-group').find('.dev-successLoader').hide();

}

/**
 * validate form field
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @param object element
 */
function validateElement(element) {
//    console.log('validate: ');
//    console.log('validating');
//    console.log($(element));
    if ($(element).hasClass('select2-focusser')) {
        element = $(element).parent().next();
    }
    if (!$(element).val() && !$(element).hasClass('select2-input')) {
        unhighlightElement(element);
    }
    $(element).removeAttr('data-unique-valid').valid();
    $(element).removeAttr('data-published-valid');
}

/**
 * @author Gehad Mohamed <gehad.mohamed@ibtikar.net.sa>
 */
function scrollToFirstNotification() {
    setTimeout(
            function(){
                if ($('.alert-success:eq(0)').is(':visible')) {
                    $("html, body").animate({scrollTop: $('.content-wrapper').offset().top - 20}, 500);
                } else if ($('.help-error:eq(0)').length > 0) {
                    $("html, body").animate({scrollTop: $('.help-error:eq(0)').offset().top - 75}, 500);
                }
            }
            ,0);
}

/**
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @param object element
 */
function checkUniqueValidation(element) {
    var $this = $(element);
    $this.attr('data-force-unique-valid', 'true');
    if ($this.valid()) {
        $this.removeAttr('data-force-unique-valid');
        $this.removeAttr('data-unique-valid');
        var $loader = $this.closest('.form-group').find('.dev-inputLoader').show();
        var validatedValue = $this.val();
        var data={fieldName: $this.attr('data-name'), fieldValue: $this.val(), id: requestId};
        if($this.attr('data-name')=='mobile'){
           $.extend(data,{'countryCode':$($this.attr('data-country-code')).intlTelInput("getSelectedCountryData").iso2})
        }
        $.ajax({
            url: $this.attr('data-url'),
            data: data,
            success: function (data) {
                if ($this.closest('form').attr('ajax-running') === 'true') {
                    return;
                }
                if (data.unique) {
                    $this.attr('data-unique-valid', 'true');
                    $this.closest('.form-group').find('.dev-successLoader').show();
                } else {
                    $this.attr('data-unique-valid', 'false');
                }
                $this.attr('data-unique-validated-value', validatedValue);
                $this.removeAttr('data-remove-color');
                $this.attr('data-validation-message', data.message);
                $this.valid();
            },
            error: function () {
            },
            complete: function () {
                $loader.hide();
            }
        });
    }
}
/**
 * @author Mahmoud Gehad Mohamed <gehad.mohamed@ibtikar.net.sa>
 * @param object element
 */
function checkPublishedValidation(element) {
    var $this = $(element);
    if($this.val().trim() == "") return;
    if ($this.valid()) {
        $this.removeAttr('data-force-published-valid');
        $this.removeAttr('data-published-valid');
        var $loader = $this.closest('.form-group').find('.InputLoader').show();

        if($this.parents('.form-group').find('#material_type_relatedMaterials').val() != ""){
            RelatedMaterialObj = JSON.parse($this.parents('.form-group').find('#material_type_relatedMaterials').val());
        }else{
            RelatedMaterialObj = [];
        }
        var ids     = $.map( RelatedMaterialObj, function( value, key ) {
            return value.id;
        });

        $.ajax({
            url: $this.attr('data-url'),
            data: {existing: ids, fieldValue: $this.val(), id: requestId},
            success: function (data) {
                if ($this.closest('form').attr('ajax-running') === 'true') {
                    return;
                }

                $this.parents('.form-group').removeClass('has-error').find('.help-block').remove();

                if (data.valid) {
                    $this.val("");
                    RelatedMaterialObj.push({
                        'id':data.id,
                        'title':data.title,
                        'slug':data.slug
                    });
                    $('#material_type_relatedMaterials').val(JSON.stringify(RelatedMaterialObj));
                    updateRelatedMaterial();
                }else{
                    $this.attr('data-validation-message', data.message);
                    markElementAsNotValid($this);
                }
            },
            error: function () {
            },
            complete: function () {
                $loader.hide();
            }
        });
    }
}

disableValidation = false;

function initFormValidation(form_selector) {
    if (!form_selector) {
        form_selector = 'form.dev-page-main-form,form.dev-js-validation';
    }
    $(form_selector).each(function () {
        var ignoredElementsSelector = ':reset,:hidden:not(#keycode),.select-input,input.contact_subject_dev,.dev-ignore-validation';
        var $form = $(this);
        var $resetButtons = $form.find(':reset');
        $resetButtons.each(function() {
            $(this).click(function() {
                $form.find('input, select, textarea').not(ignoredElementsSelector).each(function() {
                    unhighlightElement(this);
                });
            });
        });
        $form.validate({
            //        debug: true,
            ignore: ignoredElementsSelector,
            rules: {
                "hiddencode": {
                    required: function () {
                        if (grecaptcha.getResponse() == '') {
                            return true;
                        } else {
                            return false;
                        }
                    }}

            },
            onkeyup: function(element, event) {
//                $(element).siblings('.dev-loader').hide()

                if(event.keyCode !== 9) {
                    validateElement(element);
                }
            },
            onfocusout: function (element) {
//                $(element).siblings('.dev-loader').hide()

                if ($(element).attr('autofocus')) {
                    setTimeout(function () {
                        if (!disableValidation) {
                            validateElement(element);
                        }
                    }, 500);
                } else {
                    validateElement(element);
                }
            },
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
    //        invalidHandler: function(e, validator) {
    //            //validator.errorList contains an array of objects, where each object has properties "element" and "message".  element is the actual HTML Input.
    //            for (var i = 0; i < validator.errorList.length; i++) {
    //                console.log(validator.errorList[i]);
    //            }
    //
    //            //validator.errorMap is an object mapping input names -> error messages
    //            for (var i in validator.errorMap) {
    //                console.log(i, ":", validator.errorMap[i]);
    //            }
    //        },
            errorElement: 'span',
            errorClass: 'help-block',
            errorPlacement: function (error, element) {
                if(typeof $(element).attr('data-error-right') == 'string') {
                    $(error).css('text-align', 'right');
                }
                if(typeof $(element).attr('data-error-hide') != "defined" && $(element).attr('data-error-hide') == "true"){
                    return;
                }
                if ($(element).attr('data-remove-color') === 'true') {
                    return unhighlightElement(element);
                }
                if($(element).attr('data-error-template') === 'profile-image') {
                    error = $(profileImageErrorTemplate.replace('%error%', error.text()));
                }
//                console.log('placing the error');
                var errorSelector = $(element).attr('data-error-selector');
                if (errorSelector) {
//                    console.log(error);
//                    console.log($(errorSelector));
                    $(errorSelector).html(error);
//                    console.log($(errorSelector).html());
                    return;
                }
                var errorAfterSelector = $(element).attr('data-error-after-selector');
                if (errorAfterSelector) {
                    if (errorAfterSelector == '.select2-container') {
                        $(element).siblings(errorAfterSelector).after(error);
                        return;

                    }
                    $(element).closest(errorAfterSelector).after(error);
                    return;
                }
                var errorAtStartOfSelector = $(element).attr('data-error-at-start-of-selector');
                if (errorAtStartOfSelector) {
                    $('body').find(errorAtStartOfSelector).prepend(error);
                    return;
                }
                $(element).after(error);
                $(element).closest('.form-group').find('.dev-failLoader').show();
                $(element).closest('.form-group').find('.dev-successLoader').hide();
            },
            highlight: function (element) {
                unhighlightElement(element);
                if ($(element).attr('data-remove-color') === 'true') {
                    return;
                }
                markElementAsNotValid(element);
                $(element).closest('.form-group').find('.dev-failLoader').show();
                $(element).closest('.form-group').find('.dev-successLoader').hide();
            },
            success: function (label, element) {
                unhighlightElement(element);
                if ($(element).attr('data-remove-color') === 'true') {
                    return;
                }
                markElementAsValid(element);
                $(element).closest('.form-group').find('.dev-failLoader').hide();
                $(element).closest('.form-group').find('.dev-successLoader').show();
                if ($(element).attr('data-rule-equalto')) {
                    var $equalToElement = $($(element).attr('data-rule-equalto'));
                    if ($equalToElement.length > 0) {
                        markElementAsValid($equalToElement.get(0));
                    }
                }
            }
        });
    });
}

jQuery(document).ready(function ($) {
    $('body').on('ajaxCallback', function () {
        if (!$('.alert-success').is(':visible')) {
            $('*[data-rule-unique]').each(function () {
                var $this = $(this);
                if (!$this.closest('.form-group').hasClass('has-error')) {
                    if($this.val().trim() != ''){
                        $this.attr('data-validation-message', availableMessage);
                    }
                    markElementAsValid(this);
                }
            });
        }
    });

    $('body').on('keyup', '*[data-rule-unique]', function () {
        unhighlightElement(this, true);
    });

    $(window).unload(function () {
        disableValidation = true;
        $('form').each(function () {
            if (typeof $(this).validate === 'function') {
                $(this).validate().settings.ignore = "*";
            }
        });
    });

    $(document).on("select2:close", "form.dev-js-validation select", function () {
        $(this).valid();
    });

    initFormValidation();
});

function InternationalPhone(required) {
    this.formatPhoneText = function (element) {
        var phoneText = element.val();
        if (phoneText.substring(0, 2) === "00") {
            phoneText = "+" + phoneText.substring(2, phoneText.length);
        }
        phoneText = formatInternational(element.parent().find(".countries").val(), phoneText);
        element.val(phoneText);
        return phoneText;
    }
    if(typeof required =='undefined'){
        required=true;
    }

    $(".countries").msDropdown();
    var formUsed='staff_type';
    if(typeof formName !='undefined'){
        formUsed=formName;
    }

    var phoneElmSelectorPrefix = $("#"+formUsed+"_mobile_phone").length > 0?formUsed+"_mobile":"mobile";

    $('#'+phoneElmSelectorPrefix+'_phone').on("change",  function () {
        if ($("#" + phoneElmSelectorPrefix + "_phone").val() == '') {
            $("#" + phoneElmSelectorPrefix + "_countryCode_child li").removeClass('selected');
        }
    })
    $("body").on('change', ".countries", function () {
        unhighlightElement(this, false);
        $("#"+phoneElmSelectorPrefix+"_phone").val(getCountryCodeNumber($(".countries").val()) + " ");
        $("#"+phoneElmSelectorPrefix+"_phone").fadeIn(300);
        $("#"+phoneElmSelectorPrefix+"_phone").focus();
        document.getElementById(phoneElmSelectorPrefix+'_phone').selectionStart = $("#"+phoneElmSelectorPrefix+"_phone").val().length;
        $(".countries").msDropdown().data("dd").set("value", $(".countries").val());
    });
    //load country calling code if phone is empty
    if ($("#"+phoneElmSelectorPrefix+"_phone").val() === "" && required == true) {
        $("#"+phoneElmSelectorPrefix+"_phone").val(getCountryCodeNumber($(".countries").val()));
    }

    $("body").on('blur', "#"+phoneElmSelectorPrefix+"_countryCode_titleText", function () {
        $("#"+phoneElmSelectorPrefix+"_phone").fadeIn(300);
    });
    $("body").on('keyup', "#"+phoneElmSelectorPrefix+"_countryCode_titleText", function () {
        if($(this).val().length > 0)
            $("#"+phoneElmSelectorPrefix+"_phone").fadeOut(300);
        else
            $("#"+phoneElmSelectorPrefix+"_phone").fadeIn(300);
    });
    if(typeof onValidation == 'undefined' && typeof formName !='undefined'){
        $("#"+phoneElmSelectorPrefix+"_countryCode_child li").removeClass('selected');
    }
}
var lastCountryCodeValue;
function InternationalPhoneEvent(phone, required, onValidation) {
    this.formatPhoneText = function (element) {
        var phoneText = element.val();
        if (phoneText.substring(0, 2) === "00") {
            phoneText = "+" + phoneText.substring(2, phoneText.length);
        }
        phoneText = formatInternational(element.parent().find("#event_type_"+phone+"_countryCode").val(), phoneText);
        element.val(phoneText);
        return phoneText;
    }

    $("#event_type_"+phone+"_countryCode").msDropdown();
    $('#event_type_' + phone + '_phone').on("change", function () {
        if ($('#event_type_' + phone + '_phone').val() == '') {
            $("#event_type_" + phone + "_countryCode_child li").removeClass('selected');

        }
    })
    $("#event_type_"+phone+"_countryCode").on('change', function () {
        if(required){
            if($("#event_type_"+phone+"_countryCode").val() != "" && $("#event_type_"+phone+"_countryCode").val() != null){
                lastCountryCodeValue = $("#event_type_"+phone+"_countryCode").val();
            }
            if($("#event_type_"+phone+"_countryCode").val() == "" || $("#event_type_"+phone+"_countryCode").val() == null){
                $("#event_type_"+phone+"_countryCode").val(lastCountryCodeValue);
            }
        }
        unhighlightElement(this, false);
        $("#event_type_"+phone+"_phone").val(getCountryCodeNumber($("#event_type_"+phone+"_countryCode").val()) + " ");
        $("#event_type_"+phone+"_phone").fadeIn(300);
        $("#event_type_"+phone+"_phone").focus();
        document.getElementById('event_type_'+phone+'_phone').selectionStart = $("#event_type_"+phone+"_phone").val().length;
        $("#event_type_"+phone+"_countryCode").msDropdown().data("dd").set("value", $("#event_type_"+phone+"_countryCode").val());
    });

    //load country calling code if phone is empty
    if ($("#event_type_"+phone+"_phone").val() === "" && required == true) {
        $("#event_type_"+phone+"_phone").val(getCountryCodeNumber($("#event_type_"+phone+"_countryCode").val()));
    }

    $("body").on('blur', "#event_type_"+phone+"_countryCode_titleText", function () {
        $("#event_type_"+phone+"_phone").fadeIn(300);
    });
    $("body").on('keyup', "#event_type_"+phone+"_countryCode_titleText", function () {
        if($(this).val().length > 0)
            $("#event_type_"+phone+"_phone").fadeOut(300);
        else
            $("#event_type_"+phone+"_phone").fadeIn(300);
    });
    if(typeof onValidation == 'undefined'){
        $("#event_type_"+phone+"_countryCode_child li").removeClass('selected');
    }
}

/**
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @param jquery object for the phone input $element
 * @returns {String} the formated phone text
 */
function formatPhoneText($element) {
    var phoneText = $element.val();
    if (phoneText.substring(0, 2) === '00') {
        phoneText = '+' + phoneText.substring(2, phoneText.length);
    }
    phoneText = formatInternational($element.parent().find('select.dev-phone-country-select').val(), phoneText);
    $element.val(phoneText);
    return phoneText;
}

/**
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 * @param jquery object for the country select $element
 */
function initializePhonePlugin($element) {
    if ($element.data('phone-plugin-initialized') === 'true') {
        return;
    }
    $element.msDropdown();
    var $phoneInput = $element.parents('.selectCountries').find('input.dev-phone-input');
    var phoneId = $phoneInput.attr('id');
    // Remove the last part from the id "_phone"
    var pluginIdPrefix = phoneId.slice(0, phoneId.length - 6);
    $phoneInput.on('change', function () {
        if ($phoneInput.val() === '') {
            $('#' + pluginIdPrefix + '_countryCode_child li').removeClass('selected');
        }
    });
    $element.on('change', function () {
        unhighlightElement(this, false);
        $phoneInput.val(getCountryCodeNumber($element.val()) + ' ');
        $phoneInput.fadeIn(300);
        $phoneInput.focus();
        $phoneInput.get(0).selectionStart = $phoneInput.val().length;
        $element.msDropdown().data('dd').set('value', $element.val());
    });
    $('body').on('blur', '#' + pluginIdPrefix + '_countryCode_titleText', function () {
        $phoneInput.fadeIn(300);
    });
    $('body').on('keyup', '#' + pluginIdPrefix + '_countryCode_titleText', function () {
        if ($(this).val().length > 0) {
            $phoneInput.fadeOut(300);
        } else {
            $phoneInput.fadeIn(300);
        }
    });
    $('#' + pluginIdPrefix + '_countryCode_child li').removeClass('selected');
    $element.data('phone-plugin-initialized', 'true');
}

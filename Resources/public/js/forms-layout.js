'use strict';

jQuery(document).ready(function ($) {
    // auto focus on the first editable elemnt in the form
    $('.dev-main-form-container form :input:enabled:visible:not([readonly], button, :file):first').focus();
    // submit form when pressing enter
    $('.dev-main-form-container form').on('keyup', function (e) {
        if (e.keyCode === 13) {
            $('.dev-main-form-container form').submit();
        }
    });
    // design buttons actions
    $('.dev-form-save-button').on('click', function () {
        $('.dev-main-form-container form').submit();
    });
    $('.dev-form-reset-button').on('click', function () {
        $('.dev-main-form-container form').get(0).reset();
    });
    // global fix for firefox filling password fields
    $('.dev-main-form-container form input[type=password][autocomplete=off]').each(function () {
        $(this).val('');
    });
});
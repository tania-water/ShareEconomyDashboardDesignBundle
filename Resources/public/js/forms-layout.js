'use strict';

jQuery(document).ready(function ($) {
    $('.dev-form-save-button').on('click', function () {
        $('.dev-main-form-container form').submit();
    });
    $('.dev-form-reset-button').on('click', function () {
        $('.dev-main-form-container form')[0].reset();
    });
    // global fix for firefox filling password fields
    $('.dev-main-form-container form input[type=password][autocomplete=off]').each(function () {
        $(this).val('');
    });
});
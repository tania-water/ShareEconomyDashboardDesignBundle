(function ($) {

    $.fn.symfonyFormCollection = function (options, callback) {

        var collectionHolder = this;
        var settings = $.extend({
            rowWrapperSelector: '[data-type="row-wrapper"]',
            newItemButtonSelector: '#add_new_item',
            removeItemButtonSelector: '[data-type="remove-row"]',
            originalItemsCount: 0,
            minItems: 0,
            maxItems: 0,
            postRemoveRowCallback: function () {},
            postNewRowCallback: function () {}
        }, options);

        fixChildFormsIndexes();

        $(settings.newItemButtonSelector).on('click', function (e) {
            // prevent the link from creating a "#" on the URL
            e.preventDefault();

            if (settings.maxItems > 0 && collectionHolder.find(settings.rowWrapperSelector).length >= settings.maxItems) {
                return false;
            }

            var prototype = collectionHolder.data('prototype');
            var newForm = prototype.replace(/__name__/g, 1);

            collectionHolder.append(newForm);

            settings.postNewRowCallback.call();

            fixChildFormsIndexes();
        });

        // handle the removal
        collectionHolder.delegate(settings.removeItemButtonSelector, 'click', function (e) {
            e.preventDefault();

            if (collectionHolder.find(settings.rowWrapperSelector).length <= settings.minItems) {
                return false;
            }

            $(this).parents(settings.rowWrapperSelector).remove();

            fixChildFormsIndexes();

            if (collectionHolder.find(settings.rowWrapperSelector).length == 0) {
                settings.postRemoveRowCallback.call();
            }

            return false;
        });

        function fixChildFormsIndexes() {
            startIndex = settings.originalItemsCount;
            totalCurrentRows = collectionHolder.children(settings.rowWrapperSelector).length;

            collectionHolder.children(settings.rowWrapperSelector).each(function (index, value) {
                row = $(this);

                if (totalCurrentRows <= settings.minItems) {
                    row.find(settings.removeItemButtonSelector).addClass('disabled');
                } else {
                    row.find(settings.removeItemButtonSelector).removeClass('disabled');
                }

                if (row.find('[data-type="itemId"]').val() == undefined) {console.log(row)
                    row.find('input').each(function (index, value) {
                        if ($(this).attr('name') && $(this).attr('id')) {
                            $(this).attr('name', $(this).attr('name').replace(new RegExp("[0-9]+", "g"), startIndex));
                            $(this).attr('id', $(this).attr('id').replace(new RegExp("[0-9]+", "g"), startIndex));
                        }
                    });

                    startIndex++;
                }
            });

            if (settings.maxItems > 0 && collectionHolder.find(settings.rowWrapperSelector).length >= settings.maxItems) {
                $(settings.newItemButtonSelector).addClass('disabled');
            } else {
                $(settings.newItemButtonSelector).removeClass('disabled');
            }
        }

        return this;
    };
}(jQuery));
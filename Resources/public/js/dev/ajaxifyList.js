var table;
var callBack = false;
var checkbox = false;
var dataTableDefault = {
    "sPaginationType": "full_numbers",
    "bLengthChange": true,
    "iDisplayLength": limit,
    "iDisplayStart": start,
    "destroy": true,
    'sAjaxSource': ajaxData,
    "bRetrieve": true,
    "bJQueryUI": false,
    "aLengthMenu": [10, 20, 50],
    "bServerSide": true,
    "bPaginate": true,
    "deferRender": true,
    "initComplete": function (settings, json) {
    },
    "preDrawCallback": function (settings) {
        if (checkbox) {
            checkbox = false;
            return false;
        }
    },
    'fnServerData': function (sSource, aoData, fnCallback)
    {
        if (!callBack) {
            var sorting = table.order();
            var order = sorting[0];
            if ($.isArray(order)) {
                var columndir = order[1];
                var columnName = $(table.column(order[0]).header()).attr('data-name').trim();
            } else {
                var columndir = sorting[1];
                var columnName = $(table.column(sorting[0]).header()).attr('data-name').trim();
            }
            var searchKey = [];
            var searchValue = [];
            $(".dev-search-input").each(function(){
                if($(this).val() != ''){
                    searchKey.push($(this).closest('th').data('name'));
                    searchValue.push($(this).val());
                }
            });
            var page = parseInt(table.page(), 10) + parseInt(1, 10);
            var url = ajaxData + '?page=' + page + '&sort=' + columnName + '&columnDir=' + columndir + '&limit=' + table.page.info().length;

            if(searchKey.length > 0){
                url+= '&searchKey=' + JSON.stringify(searchKey) + '&searchValue=' + JSON.stringify(searchValue)
            }

            // apply filters
            if ($('[data-type="listFilter"]').length) {
                $('[data-type="listFilter"]').each(function () {
                    if ($(this).val()) {
                        url += "&" + $(this).data('name') + '=' + $(this).val();
                    }
                });
            }

            // apply auto-complete filters
            if ($('[data-type="listAutoCompleteFilter"]').length) {
                $('[data-type="listAutoCompleteFilter"]').each(function () {
                    if ($(this).val()) {
                        url += "&" + $(this).data('name') + '=' + $(this).val();
                    }
                });
            }
            
            // apply one field search
            if ($('input[data-type="one-field-search"]').length && $('input[data-type="one-field-search"]').val()) {
                url += "&" + $('input[data-type="one-field-search"]').data('name') + '=' + $('input[data-type="one-field-search"]').val();
            }

            pushNewState(null, null, url);
        } else {
            url = window.location.href;
        }
        callBack = false;
        $.ajax({
            'dataType': 'json',
            'url': url,
            beforeSend: function () {
                blockPage();
            },
            'success': function (json) {
//                        if (json.columns.length == columns.length) {
                fnCallback(json)
                $('input[type=checkbox]').closest('td').addClass('text-center');
                $('.dev-td-btn').closest('td').addClass('text-center');
                $('.dev-td-btn').closest('td').css('white-space', 'nowrap');
                setTimeout(function () {
                    $('input').uniform();
                    unblockPage();
                }, 200)
//                        } else {
//                            reIntaializeTable(json);
//                        }
            }
        });
    },
    columns: columns,
    dom: '<"datatable-scroll"t><"datatable-footer"lip>',
    language: listLanguage,
    drawCallback: function () {
        if (layoutIsLeftDirection === true) {
            // Popover
            $('[data-popup="popover"]:not(table [data-popup="popover"])').popover();

            // Popover
            $('table [data-popup="popover"]').popover({
                placement: 'left',
                delay: {"hide": 500}
            });

            // Tooltip
            $('[data-popup="tooltip"]:not(table [data-popup="tooltip"])').tooltip({
                trigger: 'hover'
            });

            // Tooltip
            $('table [data-popup="tooltip"]').tooltip({
                trigger: 'hover',
                placement: 'left'
            });

        } else {
            // Popover
            $('[data-popup="popover"]').popover();


            // Tooltip
            $('[data-popup="tooltip"]').tooltip({
                trigger: 'hover'
            });

        }
        $('.dev-checkbox-all').closest('th').removeClass('sorting_asc').addClass('sorting_disabled')
        if ($('.datatable-column-search-inputs input.dev-checkbox').length == $('.datatable-column-search-inputs input:checked.dev-checkbox').length && $('.datatable-column-search-inputs input:checked.dev-checkbox').length != 0) {
            $('.dev-checkbox-all').prop('checked', true).uniform('refresh');
        } else {
            $('.dev-checkbox-all').prop('checked', false).uniform('refresh');
        }
        $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').addClass('dropup');
    },
};

function intializeTable() {
    if (sort) {
        table = $('.datatable-column-search-inputs').DataTable($.extend({}, dataTableDefault, {"deferLoading": totalNumber, "order": sort}));
    } else {
        table = $('.datatable-column-search-inputs').DataTable($.extend({}, dataTableDefault, {"deferLoading": totalNumber}));
    }
}

function reIntaializeTable(data) {
    table.clear();
    table.destroy();
    dataTableDefault.columns = data.columns;
    columns = data.columns;
    dataTableDefault.iDisplayStart = table.page.info().start;
    dataTableDefault.iDisplayLength = table.page.info().length;

    callBack = true
    var th = ''
    $.each(data.columns, function (key, column) {
//                if (column.data == 'id') {
//                    th += '<th class="text-center sorting_disabled" id="dev-checkbox"> </th>';
//                } else
//                {
        th += '<th class="' + column.class + '" data-orderable=' + column.orderable + ' data-name="' + column.name + '">' + column.title + '</th>'
//                }

    })
    $('.datatable-column-search-inputs thead tr').remove()
    $('.datatable-column-search-inputs thead').html('<tr>' + th + '</tr>')
    if (data.sort) {
        datatableSetting = $.extend({}, dataTableDefault, {"order": JSON.parse(data.sort), "initComplete": function (settings, json) {
                $('.dev-checkbox-all').removeClass('sorting_asc').addClass('sorting_disabled')
                $(".dataTables_length select").select2({
                    /* select2 options, as an example */
                    minimumResultsForSearch: -1,
                    width: 'auto'
                });
            }})
    } else {
        datatableSetting = $.extend({}, dataTableDefault, {"initComplete": function (settings, json) {
                $(".dataTables_length select").select2({
                    /* select2 options, as an example */
                    minimumResultsForSearch: -1,
                    width: 'auto'
                });
            }});
    }
    table = $('.datatable-column-search-inputs').DataTable(datatableSetting)
}


function pushNewState(data, title, url) {
    stateChanged = true;
    history.pushState(data, title, url);
}
function blockPage() {
    $('div.panel-flat').block({
        message: '<i class="icon-spinner2 spinner"></i>',
        overlayCSS: {
            backgroundColor: '#fff',
            opacity: 0.8,
            cursor: 'wait',
            'box-shadow': '0 0 0 1px #ddd'
        },
        css: {
            border: 0,
            padding: 0,
            backgroundColor: 'none'
        }
    });
}
function unblockPage() {
    $('div.panel-flat').unblock();

}
function saveListSelectedColumns(basicModal, url) {
    //modified to use this way instead of form serialize to fix this bug #3535:
    if ($('.dev-save-columns').attr('ajax-running')) {
        return;
    }
    $('.dev-save-columns').attr('ajax-running', true)
    $('.dev-save-columns').append('<i class="icon-spinner6 spinner position-right"></i>');
    var str = "";
    $('.dev-columns-multi-select input:checked').each(function () {
        str += "columns[]=" + $(this).val() + "&";
    });

    $.ajax({
        url: url,
        method: 'POST',
        data: str,
        success: function (data) {
            if (data.status == 'login') {
                window.location.reload(true);
            } else {
                basicModal.hide();
                reIntaializeTable(data);
            }
        }
    });
}

/**
 * @returns {BaseList}
 */
function BaseList() {

    this.showColumnOptionsModal = function () {
        var basicModal = new BasicModal();
        basicModal.show(changeListColumnsUrl, function () {
            $(".dev-save-columns").click(function () {
                saveListSelectedColumns(basicModal, changeListColumnsUrl);
            })
        });
    }

    this.showPermisionModal = function (clickedElement) {
        var basicModal = new BasicModal();
        basicModal.show(showPermisionUrl + '?id=' + clickedElement.attr("data-id"), function () {
        });
    }


    /* Binding events */
    var thisObject = this;


    $('div.panel-flat').on('click', '.dev-change-columns', function () {
        $('[data-popup="tooltip"]').tooltip("hide");
        blockPage();
        thisObject.showColumnOptionsModal();
    });

    $('div.panel-flat').on('click', '.dev-role-getPermision', function () {
        thisObject.showPermisionModal($(this))
    })



}

function BasicModal() {
    var thisObject = this;
    this.hideCallback;
    this.show = function (url, callback, params) {
        $('#modal_theme_primary').on('hidden.bs.modal', function () {
            thisObject.hide();
        });
        $.ajax({
            url: url,
            method: 'GET',
            data: params,
            success: function (data) {
                if (data.status == 'failed-reload') {
                    thisObject.hide();
                    var numOfRecords = $('tr[data-id]').length;
                    var pageNum = getQueryVariable('page');
                    if (pageNum !== 1 && numOfRecords === 1) {
                        retunToPreviousPage(pageNum);
                    } else {
                        stateChangeHandler();
                    }
                    return;
                }
                if (data.status == "error") {
                    showAlertBox(data.message);
                    return;
                }
                var basicModal = $('#modal_theme_primary');
                basicModal.find('.modal-content').html(data);


                $('select.select2').on('select2-close', function () {
                    $('#modal_theme_primary').attr('tabindex', '-1');
                }).on("select2-open", function () {
                    $('#modal_theme_primary').removeAttr('tabindex');
                });
                callback();
                basicModal.modal({keyboard: true})
                basicModal.modal('show');
            }
        });
    }
    this.hide = function () {
        $('#modal_theme_primary .select2').select2('destroy');
        $('#modal_theme_primary').modal('hide');
        if (thisObject.hideCallback !== undefined)
            thisObject.hideCallback();
    }
    this.onHide = function (callback) {
        thisObject.hideCallback = callback;
    }
}
$(document).ready(function () {

    $('.select').select2();

    $(window).on("popstate", function (e) {
//        if (typeof stateChanged !== "undefined" && stateChanged) {
//            back = true;
//            console.log(e.originalEvent.state)
//            console.log(window.location.href)
        window.location.reload();
//            table.ajax.url(window.location.href).load();
//        }
    });

    $('div.panel-flat').on('click', '.dev-checkbox-all', function (e) {
        if ($(this).is(':checked')) {
            $('.datatable-column-search-inputs').find('input.dev-checkbox').prop('checked', true).uniform('refresh');
        } else {
            $('.datatable-column-search-inputs').find('input.dev-checkbox').prop('checked', false).uniform('refresh');

        }
        checkbox = true;
    });

    $('div.panel-flat').on('click', '.dev-checkbox', function (e) {
        if ($('.datatable-column-search-inputs input:checked.dev-checkbox').length != 0 && $('.datatable-column-search-inputs input.dev-checkbox').length == $('.datatable-column-search-inputs input:checked.dev-checkbox').length) {
            $('.dev-checkbox-all').prop('checked', true).uniform('refresh');
        } else {
            $('.dev-checkbox-all').prop('checked', false).uniform('refresh');
        }
    });

    $('.panel [data-action=reload]').click(function (e) {
        e.preventDefault();
        table.ajax.reload(null, false)

    });


    $('#advanced-search-Btn').on('click', function () {
        if ($(".advanced-search").hasClass("searchhidden")) {
            $(".advanced-search").slideDown();
            $(".advanced-search").removeClass('searchhidden');
        } else {
            $(".advanced-search").slideUp();
            $(".advanced-search").addClass('searchhidden');
            $(".advanced-search-more").slideUp();
        }

    });


    // advanced-search-more
    $('#advanced-search-more').on('click', function () {
        if ($(".advanced-search-more").hasClass("searchhidden")) {
            $(".advanced-search-more").slideDown();
            $(".advanced-search-more").removeClass('searchhidden');
        } else {
            $(".advanced-search-more").slideUp();
            $(".advanced-search-more").addClass('searchhidden');
            $(".advanced-search-more").slideUp();
        }
    });

    $(".dev-btn-search").on('click', function(){
        table.draw();
    });

   $('[data-type="listFilter"]').on('change', function () {
        table.draw();
    });

    $('[data-type="listAutoCompleteFilter"]').on("change", function (e) {
        table.draw();
    });

    $('[data-type="listAutoCompleteFilter"]').each(function () {
        var $this = $(this);
        $this.select2({
            width: 200,
            placeholder: $this.attr('placeholder'),
            ajax: {
                url: $this.attr('data-url'),
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        searchKey: params.term
                    };
                },
                processResults: function (data, params) {
                    return {
                        results: data.items
                    };
                },
                cache: false
            },
            allowClear: true,
            minimumInputLength: 1
        });
    });

    $(document).on('click', ".dev-delete-btn", function(){
        $.ajax({
            url: $(this).data("url"),
            type: "POST",
            success: function(data){
                if(data.allowAdd){
                    $(".dev-btn-add").removeAttr('data-max');
                }
                else{
                    $(".dev-btn-add").attr('data-max', data.maxRecords);
                }
                if ('message' in data && 'status' in data) {
                    showNotificationMsg('', data.message, data.status);
                }
                table.draw();
            }
        });
    });

    $(".dev-search-input").on('keyup', function(e){
        if(e.which == 13){
            if($(this).val() != '')
                $(".dev-btn-search").click();
        }
    });

    // handling multiple check start
    $('#multipleCheckChanger').on('change', function () {
        if ($(this).is(':checked')) {
            $("input[data-type='multipleCheckBox']").prop('checked', true).uniform('refresh');
            $("a[data-type='multipleCheckActionButton']").show();
        } else {
            $("input[data-type='multipleCheckBox']").prop('checked', false).uniform('refresh');
            $("a[data-type='multipleCheckActionButton']").hide();
        }
    });

    $("input[data-type='multipleCheckBox']").on('change', function () {
        multipleCheckerNewState = true;
        multipleCheckActionsButtonsShown = false;

        $("input[data-type='multipleCheckBox']").each(function () {
            if (multipleCheckerNewState === true && !$(this).is(':checked')) {
                multipleCheckerNewState = false;
            }

            if (multipleCheckActionsButtonsShown === false && $(this).is(':checked')){
                multipleCheckActionsButtonsShown = true;
            }
        });

        if (multipleCheckActionsButtonsShown){
            $("a[data-type='multipleCheckActionButton']").show();
        } else {
            $("a[data-type='multipleCheckActionButton']").hide();
        }

        $('#multipleCheckChanger').prop('checked', multipleCheckerNewState).uniform('refresh');
    });

    // handling the multiple action request button
    $("body").delegate("[data-type='multipleCheckAcceptAction']", "click", function () {
        var URL = $(this).data('url');
        var ids = [];

        $("input[data-type='multipleCheckBox']").each(function () {
            if ($(this).is(':checked')) {
                ids.push($(this).val());
            }
        });

        $('form#action_form').find('input#form_data').val(ids.join(","));

        $.ajax({
            type: 'POST',
            url: URL,
            data: $('form#action_form').serialize(),
            success: function (data) {
                if ('message' in data && 'status' in data) {
                    showNotificationMsg('', data.message, data.status);
                }

                table.draw();
            }
        });
    });
    // handling multiple check end

    $(".dev-btn-add").on('click', function(e){
        e.preventDefault();
        if($(this).attr('data-max') == undefined){
            window.location = $(this).attr('data-url');
        }else
            showNotificationMsg('', actionsValidationMessages['maximumRecordError'].replace("%limit%", $(this).attr('data-max')), "error");
    });
});
jQuery(document).on('ajaxComplete', function (event, response) {
    if (response) {
        if (response.status === 0 && detectIE()) {
            window.location.reload(true);
        }
        if (response.status === 404) {
            window.location = notFoundUrl;
        }
        if (typeof response.responseJSON === 'object') {
            if (typeof response.responseJSON.status !== 'undefined') {
                handleAjaxResponse(response.responseJSON);
            }
        }
    }
});
function handleAjaxResponse(responseJSON) {

    switch (responseJSON.status) {
        case 'login':
            window.location = loginUrl + '?redirectUrl=' + encodeURIComponent(window.location.href);
            break;
        case 'denied':
            window.location = accessDeniedUrl;
            break;
        case 'reload-page':
            window.location.reload(true);
            break;
        case 'redirect':
            window.location = responseJSON.url;
            break;
        case 'notification':
            var hideAfterSeconds = null;
            if (typeof responseJSON.hideAfterSeconds !== 'undefined') {
                hideAfterSeconds = responseJSON.hideAfterSeconds;
            }
            showNotification(responseJSON.message, responseJSON.type, hideAfterSeconds);
            break;
    }
}

// start handling one field search
function applyOneFieldSearch() {
    if (typeof(oneFieldSearchDelayTimer) !== 'undefined') {
        clearTimeout(oneFieldSearchDelayTimer);
    }
    oneFieldSearchDelayTimer = setTimeout(function() {
        table.draw();
    }, 250);
}

$(document).ready(function () {
    var oneFieldSearchCurrentVal = $('input[data-type="one-field-search"]').val();
    $('input[data-type="one-field-search"]').keyup(function () {
        if ($(this).val() !== oneFieldSearchCurrentVal){
            oneFieldSearchCurrentVal = $(this).val();
            applyOneFieldSearch();
        }
    });
});
// end handling one field search
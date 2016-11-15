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
            if(searchKey.length > 0)
                url+= '&searchKey=' + JSON.stringify(searchKey) + '&searchValue=' + JSON.stringify(searchValue)
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
    language: {
        search: '<span>بحث:</span> _INPUT_',
        lengthMenu: '_MENU_',
        sLengthMenu: "اظهر _MENU_ ",
        sInfo: " _START_ - _END_ من _TOTAL_ ",
        sZeroRecords: "لا يوجد ما تبحث عنه",
        sInfoEmpty: " 0 - 0 من 0 ",
        paginate: {'first': 'الاول', 'last': 'الاخير', 'next': '&larr;', 'previous': '&rarr;'}
    },
    drawCallback: function () {
        $('[data-popup="tooltip"]').tooltip({
            trigger: 'hover'
        });
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

    $(".dev-search-input").on('keyup', function(e){
        if(e.which == 13){
            if($(this).val() != '')
                $(".dev-btn-search").click();
        }
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
/* ------------------------------------------------------------------------------
*
*  # Datatables API
*
*  Specific JS code additions for datatable_api.html page
*
*  Version: 1.0
*  Latest update: Aug 1, 2015
*
* ---------------------------------------------------------------------------- */

$(function() {


    // Table setup
    // ------------------------------

    // Setting datatable defaults
    $.extend( $.fn.dataTable.defaults, {
        autoWidth: false,
        order: [[1, 'asc']],
        columnDefs: [{ 
            bSort : false,
            orderable: false,
            width: '30px',
            targets: [0]
        },
         { 
            width: '200px',
            targets: [1]
        },
                 { 
            width: '200px',
            targets: [2]
        },
        
                 { 
            width: '200px',
            targets: [3]
        },
        
                 { 
            width: '200px',
            targets: [4]
        },
                
                 { 
            width: '200px',
            targets: [5]
        },
         { 
            bSort : false,
            orderable: false,
            width: '200px',
            targets: [-1]
        }],
        dom: '<"datatable-scroll"t><"datatable-footer"lip>',
        language: {
            search: '<span>بحث:</span> _INPUT_',
            lengthMenu: '_MENU_',
            sLengthMenu: "اظهر _MENU_ ",
            sInfo: " _START_ - _END_ من _TOTAL_ ",
            sZeroRecords: "لا يوجد ما تبحث عنه",
            sInfoEmpty: " 0 - 0 من 0 ",
            paginate: { 'first': 'الاول', 'last': 'الاخير', 'next': '&larr;', 'previous': '&rarr;' }
        },

  drawCallback: function () {
            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').addClass('dropup');
        },
        preDrawCallback: function() {
            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').removeClass('dropup');
        }
    });


    // Single row selection
    var singleSelect = $('.datatable-selection-single').DataTable();
    $('.datatable-selection-single tbody').on('click', 'tr', function() {
        if ($(this).hasClass('success')) {
            $(this).removeClass('success');
        }
        else {
            singleSelect.$('tr.success').removeClass('success');
            $(this).addClass('success');
        }
    });


    // Multiple rows selection
    $('.datatable-selection-multiple').DataTable();
    
    $('.datatable-selection-multiple tbody').on('click', 'tr', function() {
        $(this).toggleClass('success');
    });


    // Setup - add a text input to each footer cell
    $('.datatable-column-search-inputs thead tr#filterrow th').not(':last-child').not(':first-child').each( function () {
        var title = $('.datatable-column-search-inputs thead th').eq( $(this).index() ).text();
        $(this).html('<input type="text" class="form-control input-sm" placeholder="بحث '+title+'" />');
    } );
 
    // DataTable
    var table = $('.datatable-column-search-inputs').DataTable();
     
    // Apply the filter
    $(".datatable-column-search-inputs thead input").on( 'keyup change', function () {
        table
            .column( $(this).parent().index()+':visible' )
            .search( this.value )
            .draw();
    } );


    // Individual column searching with selects
    $('.datatable-column-search-selects').DataTable({
        initComplete: function () {
            this.api().columns().every( function() {
                var column = this;
                var select = $('<select class="filter-select" data-placeholder="Filter"><option value=""></option></select>')
                    .appendTo($(column.footer()).not(':last-child').empty())
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
 
                        column
                            .search( val ? '^'+val+'$' : '', true, false )
                            .draw();
                    });
 
                column.data().unique().sort().each( function (d, j) {
                    select.append('<option value="'+d+'">'+d+'</option>')
                });
            });
        }
        
    });



    // External table additions
    // ------------------------------

    // Add placeholder to the datatable filter option
    $('.dataTables_filter input[type=search]').attr('placeholder','Type to filter...');


    // Enable Select2 select for the length option
    $('.dataTables_length select').select2({
        minimumResultsForSearch: Infinity,
        width: 'auto'
    });


    // Enable Select2 select for individual column searching
    $('.filter-select').select2();
    
});

/* ------------------------------------------------------------------------------
*
*  # Row Reorder extension for Datatables
*
*  Specific JS code additions for datatable_extension_row_reorder.html page
*
*  Version: 1.0
*  Latest update: Nov 9, 2015
*
* ---------------------------------------------------------------------------- */

$(function() {


    // Table setup
    // ------------------------------

    // Setting datatable defaults
    $.extend( $.fn.dataTable.defaults, {
        autoWidth: false,
//         bSort : false,
        bPaginate: false,
        dom: '<"datatable-scroll"t><"datatable-footer"lip>',
        language: {
            search: '<span>Filter:</span> _INPUT_',
            lengthMenu: '<span>Show:</span> _MENU_',
            paginate: { 'first': 'First', 'last': 'Last', 'next': '&larr;', 'previous': '&rarr;' }
        },
        
        drawCallback: function () {
            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').addClass('dropup');
        },
        preDrawCallback: function() {
            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').removeClass('dropup');
        }
    });


    // Basic initialization
    $('.datatable-row-basic').DataTable({
        rowReorder: true,
                "oLanguage": {
  "sLengthMenu": "اظهر _MENU_ ",
  "sZeroRecords": "لا يوجد ما تبحث عنه",
  "sInfo": " _START_ - _END_ من _TOTAL_ ",
  "sInfoEmpty": " 0 - 0 من 0 "
}
    });


    // Full row selection
    $('.datatable-row-full').DataTable({
        
        rowReorder: {
           selector: 'td.reorderTd'
        },
        //responsive: true,
        columnDefs: [

            { 
            bSort : false,
            orderable: false,
            width: '100px',
            targets: [1]
        },          { 
            bSort : false,
            orderable: false,
            targets: [0,2,3,4,5]
        },
                     { 
            bSort : false,
            orderable: false,
            width: '200px',
            targets: [-1]
        }
        ]
    });


    // Responsive integration
    $('.datatable-row-responsive').DataTable({
        rowReorder: {
            selector: 'td:nth-child(2)'
        },
        responsive: true
    });


    // Reorder events
    var table = $('.datatable-row-events').DataTable({
        rowReorder: true
    });
 
    // Setup event
    table.on('row-reorder', function (e, diff, edit) {
        var result = 'Reorder started on row: '+edit.triggerRow.data()[1]+'<br>';
 
        for (var i=0, ien=diff.length ; i<ien ; i++) {
            var rowData = table.row( diff[i].node ).data();
 
            result += rowData[1]+' updated to be in position '+
                diff[i].newData+' (was '+diff[i].oldData+')<br>';
        }
 
        $('#event-result').html('Event result:<br>'+result);
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
    
});

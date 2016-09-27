/* ------------------------------------------------------------------------------
*
*  # Basic datatables
*
*  Specific JS code additions for datatable_basic.html page
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
            order: [[1, 'desc']],
        columnDefs: [{ 
            orderable: false,
            width: '100px'
        }],

        dom: '<"datatable-header"fl><"datatable-scroll"t><"datatable-footer"ip>',
        language: {
            search: '<span>بحث:</span> _INPUT_',
            lengthMenu: '<span>اظهار:</span> _MENU_',
            paginate: { 'first': 'First', 'last': 'Last', 'next': '&larr;', 'previous': '&rarr;' }
        },
        drawCallback: function () {
            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').addClass('dropup');
        },
        preDrawCallback: function() {
            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').removeClass('dropup');
        }
    });


    // Basic datatable
    $('.datatable-basic').DataTable({
        "oLanguage": {
  "sLengthMenu": "اظهر _MENU_ ",
  "sZeroRecords": "لا يوجد ما تبحث عنه",
  "sInfo": " _START_ - _END_ من _TOTAL_ ",
  "sInfoEmpty": " 0 - 0 من 0 "
}
    });
    
     
    // Alternative pagination
    $('.datatable-pagination').DataTable({
        pagingType: "simple",
        language: {
            paginate: {'next': 'Next &larr;', 'previous': '&rarr; Prev'}
        }
    });


    // Datatable with saving state
    $('.datatable-save-state').DataTable({
        stateSave: true
    });


    // Scrollable datatable
    $('.datatable-scroll-y').DataTable({
"oLanguage": {
  "sLengthMenu": "اظهر _MENU_ ",
  "sZeroRecords": "لا يوجد ما تبحث عنه",
  "sInfo": " _START_ الي _END_ من _TOTAL_ صف",
  "sInfoEmpty": "اظهر 0 الي 0 من 0 صف"
},
        autoWidth: true,
        scrollY: 400
    });



    // External table additions
    // ------------------------------

    // Add placeholder to the datatable filter option
    $('.dataTables_filter input[type=search]').attr('placeholder','بحث...');


    // Enable Select2 select for the length option
    $('.dataTables_length select').select2({
        minimumResultsForSearch: Infinity,
        width: 'auto'
    });
    
});

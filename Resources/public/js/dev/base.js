function blockPage() {
    $('div.dev-panel-flat').block({
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
    $('div.dev-panel-flat').unblock();
}

$(document).ready(function () {
    $('input.open-datetimepicker').each(function () {
        var $this = $(this);
        var dateTimePickerOptions = {'format': 'MM/DD/YYYY HH:mm', 'useCurrent': false};
        if ($this.attr('data-min-date')) {
            dateTimePickerOptions['minDate'] = $this.attr('data-min-date');
        }
        $this.datetimepicker(dateTimePickerOptions);
        $this.parent().find('.input-group-addon').on('click', function () {
            $this.data("DateTimePicker").show();
        });
    });
});
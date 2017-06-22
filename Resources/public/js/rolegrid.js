function correctGroupModuleInputValue($this) {
    $this.parents('tbody').find('.dev-module-group-input').prop('checked', $this.parents('tbody').find('.dev-permission-input').length === $this.parents('tbody').find('.dev-permission-input:checked').length);
}
function correctGridModuleGroupSelectdPermissionsCount($this) {
    $this.parents('tbody').find('.dev-module-group-selected-inputs-count').html($this.parents('tbody').find('.dev-permission-input:checked').length);
}
function initializeGrid() {
    if (typeof jQuery === 'undefined') {
        setTimeout(initializeGrid, 500);
    } else {
        $(document).ready(function () {
            $('body').on('change', '.dev-module-group-input', function () {
                var $this = $(this);
                $this.parents('tbody').find('.dev-permission-input, .dev-module-input').prop('checked', $this.is(':checked'));
                correctGridModuleGroupSelectdPermissionsCount($this);
                $('.dev-page-main-form').valid();
            });
            $('body').on('change', '.dev-module-input', function () {
                var $this = $(this);
                $this.parents('tr').find('.dev-permission-input').prop('checked', $this.is(':checked'));
                correctGroupModuleInputValue($this);
                correctGridModuleGroupSelectdPermissionsCount($this);
                $('.dev-page-main-form').valid();
            });
            $('body').on('change', '.dev-permission-input', function () {
                var $this = $(this);
                correctGridModuleGroupSelectdPermissionsCount($this);
                correctGroupModuleInputValue($this);
                $this.parents('tr').find('.dev-module-input').prop('checked', $this.parents('tr').find('.dev-permission-input').length === $this.parents('tr').find('.dev-permission-input:checked').length);
            });
            $('.tabbable').removeClass('hide');
            // compute the selected inputs counts after the page loads
            $('.dev-permission-input').trigger('change');
        });
    }
}
initializeGrid();
/**
 * Global DataTable pagination defaults — Material Icons styled.
 * Sets pagingType to 'full_numbers' and clears paginate text labels
 * so CSS ::after pseudo-elements can render Material Symbols icons.
 * Load this AFTER jQuery + DataTables JS but BEFORE any table init.
 */
(function () {
    if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) return;
    jQuery.extend(true, jQuery.fn.dataTable.defaults, {
        pagingType: 'full_numbers',
        language: {
            paginate: {
                first: '',
                previous: '',
                next: '',
                last: ''
            }
        }
    });
})();

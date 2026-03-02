document.addEventListener('DOMContentLoaded', function() {
    if (typeof Choices === 'undefined') return;

    var container = document.querySelector('.programme-edit');
    if (!container) return;

    var selects = container.querySelectorAll('select.choices-select');
    selects.forEach(function(select) {
        new Choices(select, {
            searchEnabled: true,
            placeholderValue: 'Select...',
            searchPlaceholderValue: 'Search...',
            itemSelectText: '',
            shouldSort: true
        });
    });
});

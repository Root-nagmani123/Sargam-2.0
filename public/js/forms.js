// public/js/forms.js
function previewImage(event, input) {
    const fileList = input.files;
    const previewContainer = document.getElementById(`image-preview-${input.id}`);

    previewContainer.innerHTML = '';

    if (fileList.length > 0) {
        Array.from(fileList).forEach(file => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.maxWidth = '100px';
            img.style.margin = '5px';
            img.style.display = 'inline-block';
            previewContainer.appendChild(img);
        });
    }
}

function replicateRow(event) {
    event.preventDefault();
    var table = event.target.closest('table').getElementsByTagName('tbody')[0];

    if (table.rows.length === 0) {
        addNewRow(table, 0);
    } else {
        var lastRow = table.rows[table.rows.length - 1];
        var newRow = lastRow.cloneNode(true);

        const isDuplicate = checkDropdownDuplicates(newRow);

        if (isDuplicate) {
            resetRowInputs(lastRow);
        } else {
            var newRowIndex = table.rows.length;
            newRow.id = 'row-' + newRowIndex;

            var inputs = newRow.querySelectorAll('input, select, textarea');
            inputs.forEach(function(input) {
                input.name = input.name.replace(/\d+$/, newRowIndex);
                input.id = input.id.replace(/\d+$/, newRowIndex);
            });

            resetRowInputs(newRow);
            table.appendChild(newRow);
        }
    }
}

function resetRowInputs(row) {
    const inputs = row.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = false;
        } else {
            input.value = '';
        }
    });
}

function checkDropdownDuplicates(row) {
    const dropdowns = document.querySelectorAll('.dynamic-table tbody tr td:nth-child(1) select');
    const selectedValues = [];
    let isDuplicate = false;

    dropdowns.forEach(dropdown => {
        const selectedValue = dropdown.value;
        const selectedText = dropdown.options[dropdown.selectedIndex].text;

        if (selectedValue && selectedValues.includes(selectedValue)) {
            alert(selectedValue + ' [' + selectedText + '] is already entered');
            isDuplicate = true;
        } else {
            selectedValues.push(selectedValue);
        }
    });

    return isDuplicate;
}

function removeRow(event) {
    event.preventDefault();
    var table = event.target.closest('table').getElementsByTagName('tbody')[0];

    if (table.rows.length === 1) {
        // alert('You cannot remove the last row!');
    } else if (table.rows.length > 0) {
        table.deleteRow(table.rows.length - 1);
    }
}

function addNewRow(table, rowIndex) {
    var newRow = table.insertRow(rowIndex);
    // Add cells and inputs as needed
}

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
});
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function addRow(containerId, template) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const div = document.createElement('div');
        div.className = 'row g-2 align-items-end mb-2 chart-row';
        div.setAttribute('data-chart', containerId.replace('_rows', ''));
        div.innerHTML = template;
        container.appendChild(div);
        div.querySelectorAll('.remove-row').forEach(btn => btn.addEventListener('click', function() { this.closest('.chart-row').remove(); }));
    }

    function bindRemove() {
        document.querySelectorAll('.remove-row').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('.chart-row');
                if (row && row.parentElement.querySelectorAll('.chart-row').length > 1) row.remove();
            });
        });
    }
    bindRemove();

    document.getElementById('add_social_row')?.addEventListener('click', function() {
        addRow('social_rows', `
            <div class="col-md-4"><input type="text" name="social_groups[label][]" class="form-control" placeholder="Category"></div>
            <div class="col-md-3"><input type="number" name="social_groups[female_count][]" class="form-control" placeholder="Female" min="0" value="0"></div>
            <div class="col-md-3"><input type="number" name="social_groups[male_count][]" class="form-control" placeholder="Male" min="0" value="0"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
        `);
    });
    document.getElementById('add_gender_row')?.addEventListener('click', function() {
        addRow('gender_rows', `
            <div class="col-md-6"><input type="text" name="gender[label][]" class="form-control" placeholder="Label"></div>
            <div class="col-md-4"><input type="number" step="0.1" name="gender[value][]" class="form-control" placeholder="%" min="0" max="100" value="0"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
        `);
    });
    document.getElementById('add_age_row')?.addEventListener('click', function() {
        addRow('age_rows', `
            <div class="col-md-4"><input type="text" name="age[label][]" class="form-control" placeholder="Age group"></div>
            <div class="col-md-3"><input type="number" name="age[female_count][]" class="form-control" placeholder="Female" min="0" value="0"></div>
            <div class="col-md-3"><input type="number" name="age[male_count][]" class="form-control" placeholder="Male" min="0" value="0"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
        `);
    });
    document.getElementById('add_stream_row')?.addEventListener('click', function() {
        addRow('stream_rows', `
            <div class="col-md-6"><input type="text" name="stream[label][]" class="form-control" placeholder="Stream"></div>
            <div class="col-md-4"><input type="number" name="stream[value][]" class="form-control" placeholder="Count" min="0" value="0"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
        `);
    });
    document.getElementById('add_cadre_row')?.addEventListener('click', function() {
        addRow('cadre_rows', `
            <div class="col-md-4"><input type="text" name="cadre[label][]" class="form-control" placeholder="Cadre"></div>
            <div class="col-md-3"><input type="number" name="cadre[female_count][]" class="form-control" placeholder="Female" min="0" value="0"></div>
            <div class="col-md-3"><input type="number" name="cadre[male_count][]" class="form-control" placeholder="Male" min="0" value="0"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
        `);
    });
    document.getElementById('add_domicile_row')?.addEventListener('click', function() {
        const container = document.getElementById('domicile_rows');
        const div = document.createElement('div');
        div.className = 'row g-2 align-items-end mb-2 chart-row';
        div.setAttribute('data-chart', 'domicile');
        div.innerHTML = `
            <div class="col-md-6"><input type="text" name="domicile[label][]" class="form-control" placeholder="State / UT"></div>
            <div class="col-md-4"><input type="number" name="domicile[value][]" class="form-control" placeholder="Count" min="0" value="0"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
        `;
        container.appendChild(div);
        div.querySelector('.remove-row').addEventListener('click', function() { this.closest('.chart-row').remove(); });
    });
});
</script>
@endpush

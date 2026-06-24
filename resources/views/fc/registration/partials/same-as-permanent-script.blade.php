<script>
document.addEventListener('DOMContentLoaded', function () {
    const cb = document.querySelector('input[type="checkbox"][name="same_as_permanent"]');
    if (!cb) return;

    const form = cb.closest('form');
    if (!form) return;

    // Ordered so dependent location selects copy in cascade order: country -> state -> district.
    const presFields = ['pres_address_line1', 'pres_address_line2', 'pres_country_id', 'pres_state_id', 'pres_district', 'pres_city', 'pres_city_name', 'pres_pincode'];
    const permFields = ['perm_address_line1', 'perm_address_line2', 'perm_country_id', 'perm_state_id', 'perm_district', 'perm_city', 'perm_city_name', 'perm_pincode'];

    function field(name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function syncPresentFromPermanent() {
        if (cb.checked) {
            permFields.forEach(function (perm, i) {
                const src = field(perm);
                const dst = field(presFields[i]);
                if (src && dst) {
                    dst.value = src.value;
                    dst.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        presFields.forEach(function (name) {
            const el = field(name);
            if (!el) return;
            el.disabled = cb.checked;
            const wrap = el.closest('[class*="col-md"]') || el.closest('.col-12') || el.parentElement;
            if (wrap) wrap.style.opacity = cb.checked ? '0.65' : '1';
        });
    }

    cb.addEventListener('change', syncPresentFromPermanent);

    permFields.forEach(function (name) {
        const el = field(name);
        if (!el) return;
        ['change', 'input'].forEach(function (ev) {
            el.addEventListener(ev, function () {
                if (cb.checked) syncPresentFromPermanent();
            });
        });
    });

    form.addEventListener('submit', function () {
        if (!cb.checked) return;
        presFields.forEach(function (name) {
            const el = field(name);
            if (el) el.disabled = false;
        });
    });

    syncPresentFromPermanent();
});
</script>

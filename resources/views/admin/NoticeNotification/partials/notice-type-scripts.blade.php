    var subcategoriesUrl = @json(route('admin.notice.getSubcategories'));
    var preselectedSubcategory = @json($selectedSubcategoryPk ?? null);

    function resetSubTypeSelect(message) {
        $('#noticeSubType').empty().append('<option value="">' + (message || 'Select the sub type') + '</option>');
    }

    function loadSubcategories(categoryPk, preselect) {
        if (!categoryPk) {
            $('#noticeSubTypeBox').addClass('d-none');
            resetSubTypeSelect();
            return;
        }

        $.ajax({
            url: subcategoriesUrl,
            type: 'GET',
            data: { notice_category_master_pk: categoryPk },
            success: function (res) {
                resetSubTypeSelect();

                if (!res.data || res.data.length === 0) {
                    $('#noticeSubTypeBox').addClass('d-none');
                    $('#noticeSubType').prop('required', false);
                    return;
                }

                $('#noticeSubTypeBox').removeClass('d-none');
                $('#noticeSubType').prop('required', true);

                $.each(res.data, function (index, item) {
                    var selected = (preselect && String(preselect) === String(item.pk)) ? 'selected' : '';
                    $('#noticeSubType').append(
                        '<option value="' + item.pk + '" ' + selected + '>' + item.name + '</option>'
                    );
                });
            },
            error: function () {
                $('#noticeSubTypeBox').addClass('d-none');
                resetSubTypeSelect('Unable to load sub types');
            }
        });
    }

    $('#noticeType').on('change', function () {
        loadSubcategories($(this).val(), null);
    });

    var initialCategory = $('#noticeType').val();
    if (initialCategory) {
        loadSubcategories(initialCategory, preselectedSubcategory);
    }

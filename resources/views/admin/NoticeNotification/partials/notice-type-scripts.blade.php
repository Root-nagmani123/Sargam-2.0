    var subcategoriesBaseUrl = @json(url('admin/notice/subcategories'));
    var preselectedSubcategory = @json(old('notice_subcategory_master_pk', optional($notice ?? null)->notice_subcategory_master_pk));

    function resetSubTypeSelect(message) {
        $('#noticeSubcategory').empty().append('<option value="">' + (message || 'Select the sub type') + '</option>');
    }

    function setNoticeTypeRowLayout(compactThreeCol) {
        var $title = $('#noticeTitleCol');
        var $type = $('#noticeTypeCol');
        var $sub = $('#noticeSubTypeBox');

        if (compactThreeCol) {
            $title.removeClass('col-6').addClass('col-4');
            $type.removeClass('col-6').addClass('col-4');
            $sub.removeClass('d-none col-6').addClass('col-4');
        } else {
            $title.removeClass('col-4').addClass('col-6');
            $type.removeClass('col-4').addClass('col-6');
            $sub.addClass('d-none').removeClass('col-4 col-6');
        }
    }

    function hideNoticeSubType() {
        setNoticeTypeRowLayout(false);
        $('#noticeSubcategory').prop('required', false).val('');
        resetSubTypeSelect();
    }

    function showNoticeSubType() {
        setNoticeTypeRowLayout(true);
        $('#noticeSubcategory').prop('required', true);
    }

    function loadSubcategories(categoryPk, preselect) {
        if (!categoryPk) {
            hideNoticeSubType();
            return;
        }

        hideNoticeSubType();

        $.get(subcategoriesBaseUrl + '/' + encodeURIComponent(categoryPk), function (res) {
            resetSubTypeSelect();

            if (!res.status || !res.data || res.data.length === 0) {
                hideNoticeSubType();
                return;
            }

            showNoticeSubType();

            $.each(res.data, function (_, item) {
                var selected = (preselect && String(preselect) === String(item.pk)) ? 'selected' : '';
                $('#noticeSubcategory').append(
                    '<option value="' + item.pk + '" ' + selected + '>' + item.name + '</option>'
                );
            });
        }).fail(function () {
            hideNoticeSubType();
            resetSubTypeSelect('Unable to load sub types');
        });
    }

    $('#noticeCategory').on('change', function () {
        loadSubcategories($(this).val(), null);
    });

    var initialCategory = $('#noticeCategory').val();
    if (initialCategory) {
        loadSubcategories(initialCategory, preselectedSubcategory);
    }

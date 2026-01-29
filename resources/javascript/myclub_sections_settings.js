jQuery(document).ready(function($) {
    function addNotice(text, type) {
        var $notice = $( '<div></div>' )
            .attr( 'role', 'alert' )
            .attr( 'tabindex', '-1' )
            .addClass( 'is-dismissible notice notice-' + type )
            .append( $( '<p></p>' ).html( '<strong>' + text + '</strong>' ) )
            .append(
                $( '<button></button>' )
                    .attr( 'type', 'button' )
                    .addClass( 'notice-dismiss myclub-dismiss' )
                    .append( $( '<span></span>' ).addClass( 'screen-reader-text' ).text( wp.i18n.__( 'Dismiss this notice.' ) ) )
            );

        $('.myclub-dismiss').parent().hide();
        $("#myclub-sections-settings-form").find("h2").first().after($notice);
    }

    function setSortableItems() {
        const $sortList = $("#myclub_sections_show_items_order");
        const $items = $sortList.find("li input");
        const $selectedItems = $(".sort-item-setter");
        const sortedItems = $items.map((_, item) => $(item).val()).get();

        $selectedItems.each(function(_, item) {
            const $checkbox = $(item);
            const name = $checkbox.data('name');
            const displayName = $checkbox.data('display-name');

            if (!sortedItems.includes(name) && $checkbox.is(':checked')) {
                $sortList.append(`<li><input type="hidden" name="myclub_sections_show_items_order[]" value="${name}">${displayName}</li>`);
            }
        });

        sortedItems.forEach(value => {
            const $checkbox = $selectedItems.filter(`[data-name="${value}"]`);

            if ($checkbox.length === 0 || !$checkbox.is(':checked')) {
                $sortList.find('li input').filter((_, item) => $(item).val() === value).parent().remove();
            }
        });

        try {
            $sortList.sortable("refresh");
        } catch(e) {
            $sortList.sortable();
        }
    }

    function syncDefaultValue(name) {
        const $disabled_fields = $(document).find(`input[name="${name}"]:disabled`);
        const $default_select = $(document).find(`select[name="${name.replace('[]', '')}_default"]`);
        $default_select.find('option').prop('disabled', false);

        $disabled_fields.each((index, field) => {
            $default_select.find(`option[value="${field.value}"]`).prop('disabled', true);
        });
    }

    $("#myclub-reload-sections-button").on("click", function() {
        addNotice(wp.i18n.__('Reloading sections', 'myclub-sections'), 'success');
        $("#myclub_sections_last_sections_sync").html(wp.i18n.__('The sections update task is currently running', 'myclub-sections'));

        $.ajax({
            url: ajaxurl,
            data: {
                _ajax_nonce: myclub_sections_settings.nonce,
                "action": "myclub_reload_sections"
            },
            success: function(returned_data) {
                addNotice(returned_data.data.message, returned_data.success ? 'success' : 'error');
            },
            error: function(errorThrown) {
                addNotice(wp.i18n.__('Unable to reload sections', 'myclub-sections'), 'error');
                console.log(errorThrown);
            }
        });
    });

    $("#myclub-reload-news-button").on("click", function() {
        addNotice(wp.i18n.__('Reloading news', 'myclub-sections'), 'success');
        $("#myclub_sections_last_news_sync").html(wp.i18n.__('The news update task is currently running', 'myclub-sections'));

        $.ajax({
            url: ajaxurl,
            data: {
                _ajax_nonce: myclub_sections_settings.nonce,
                "action": "myclub_reload_section_news"
            },
            success: function(returned_data) {
                addNotice(returned_data.data.message, returned_data.success ? 'success' : 'error');
            },
            error: function(errorThrown) {
                addNotice(wp.i18n.__('Unable to reload news', 'myclub-sections'), 'error');
                console.log(errorThrown);
            }
        });
    });

    $("#myclub-sync-club-calendar-button").on("click", function() {
        addNotice(wp.i18n.__('Synchronizing club calendar', 'myclub-sections'), 'success');
        $("#myclub_sections_last_club_calendar_sync").html(wp.i18n.__('Calendar synchronization is currently running', 'myclub-sections'));

        $.ajax({
            url: ajaxurl,
            data: {
                _ajax_nonce: myclub_sections_settings.nonce,
                "action": "myclub_sync_section_club_calendar"
            },
            success: function(returned_data) {
                addNotice(returned_data.data.message, returned_data.success ? 'success' : 'error');
            },
            error: function(errorThrown) {
                addNotice(wp.i18n.__('Unable to synchronize club calendar', 'myclub-sections'), 'error');
                console.log(errorThrown);
            }
        })
    });

    $("#myclub-sections-settings-form").on("click", ".myclub-dismiss", function() {
        $(this).parent().hide();
    });

    $('.sort-item-setter').on('change', function() {
        setSortableItems();
    });

    setSortableItems();

    $('.myclub-sortable-calendar').sortable();

    $(document).on('change', '.myclub-calendar-enable', function () {
        const $li = $(this).closest('li');
        const $hidden = $li.find('input.myclub-calendar-value');

        if (this.checked) {
            $hidden.prop('disabled', false);
        } else {
            $hidden.prop('disabled', true);
        }

        syncDefaultValue($hidden.prop("name"));
    });

    const uniqueNames = [...new Set(
        $('input.myclub-calendar-value').map(function () {
            return this.name;
        }).get()
    )];

    uniqueNames.forEach(name => syncDefaultValue(name));
});
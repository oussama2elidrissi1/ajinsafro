/**
 * Ajinsafro Traveler Home — Admin JS
 *
 * - Media uploader for image fields
 * - Region repeater add / remove
 */
(function ($) {
    'use strict';

    /* ── Media Uploader ──────────────────────────────────────────── */
    $(document).on('click', '.ajth-upload-btn', function (e) {
        e.preventDefault();

        var btn     = $(this);
        var target  = $(btn.data('target'));
        var preview = $(btn.data('preview'));

        var frame = wp.media({
            title:    'Choisir une image',
            button:   { text: 'Utiliser cette image' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            target.val(attachment.id);
            var url = attachment.sizes && attachment.sizes.medium
                ? attachment.sizes.medium.url
                : attachment.url;
            preview.html('<img src="' + url + '" style="max-width:300px;height:auto;border-radius:6px;">');
        });

        frame.open();
    });

    /* ── Remove Image ────────────────────────────────────────────── */
    $(document).on('click', '.ajth-remove-btn', function (e) {
        e.preventDefault();
        var btn     = $(this);
        var target  = $(btn.data('target'));
        var preview = $(btn.data('preview'));
        target.val('');
        preview.html('');
    });

    /* ── Region Repeater — Add ───────────────────────────────────── */
    $('#ajth-add-region').on('click', function () {
        var wrap  = $('#ajth-regions-wrap');
        var index = wrap.children('.ajth-region-row').length;

        // Use WP JS template (underscore-style mustache)
        var tmplHtml = $('#tmpl-ajth-region-row').html();
        var html = tmplHtml
            .replace(/\{\{data\.index\}\}/g, index)
            .replace(/\{\{data\.index\+1\}\}/g, index + 1);

        wrap.append(html);
    });

    /* ── Region Repeater — Remove ────────────────────────────────── */
    $(document).on('click', '.ajth-remove-region', function (e) {
        e.preventDefault();
        $(this).closest('.ajth-region-row').remove();

        // Re-index name attributes
        $('#ajth-regions-wrap .ajth-region-row').each(function (i) {
            var $row = $(this);
            $row.attr('data-index', i);
            $row.find('.ajth-region-num').text(i + 1);
            $row.find('input, select, textarea').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/regions\[\d+\]/, 'regions[' + i + ']'));
                }
                var id = $(this).attr('id');
                if (id) {
                    $(this).attr('id', id.replace(/region-(image|preview)-\d+/, 'region-$1-' + i));
                }
            });
            $row.find('.ajth-upload-btn, .ajth-remove-btn').each(function () {
                var dt = $(this).data('target');
                if (dt) {
                    $(this).attr('data-target', dt.replace(/region-image-\d+/, 'region-image-' + i));
                }
                var dp = $(this).data('preview');
                if (dp) {
                    $(this).attr('data-preview', dp.replace(/region-preview-\d+/, 'region-preview-' + i));
                }
            });
            $row.find('.ajth-img-preview').each(function () {
                $(this).attr('id', 'ajth-region-preview-' + i);
            });
        });
    });

    function reindexThemeItems() {
        $('#ajth-theme-items-wrap .ajth-theme-item-row').each(function (i) {
            var $row = $(this);
            $row.attr('data-index', i);
            $row.find('.ajth-theme-item-num').text(i + 1);
            $row.find('input, textarea, select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/holiday_theme\]\[items\]\[\d+\]/, 'holiday_theme][items][' + i + ']'));
                }
            });
        });
    }

    $('#ajth-add-theme-item').on('click', function () {
        var wrap = $('#ajth-theme-items-wrap');
        var index = wrap.children('.ajth-theme-item-row').length;
        var tmplHtml = $('#tmpl-ajth-theme-item-row').html();
        var html = tmplHtml
            .replace(/\{\{data\.index\}\}/g, index)
            .replace(/\{\{data\.index\+1\}\}/g, index + 1);
        wrap.append(html);
    });

    $(document).on('click', '.ajth-remove-theme-item', function (e) {
        e.preventDefault();
        $(this).closest('.ajth-theme-item-row').remove();
        reindexThemeItems();
    });

    $(document).on('click', '.ajth-theme-move-up', function (e) {
        e.preventDefault();
        var row = $(this).closest('.ajth-theme-item-row');
        var prev = row.prev('.ajth-theme-item-row');
        if (prev.length) {
            row.insertBefore(prev);
            reindexThemeItems();
        }
    });

    $(document).on('click', '.ajth-theme-move-down', function (e) {
        e.preventDefault();
        var row = $(this).closest('.ajth-theme-item-row');
        var next = row.next('.ajth-theme-item-row');
        if (next.length) {
            row.insertAfter(next);
            reindexThemeItems();
        }
    });

})(jQuery);

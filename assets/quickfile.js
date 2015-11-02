// Set close and open
$(document).keydown(function (e) {

    /* ctrl + shift + f */
    //if (e.which === 70 && e.ctrlKey && e.shiftKey) {
    /* ctrl + space */
    if (e.which === 32 && e.ctrlKey) {
        $('#quickfilewrapper').fadeIn(400);
        $('#quickfilewrapper input').focus();
        var list = $('#quickfile #quickfilelist');
        if (!STUDIP.quickfile.init) {
            STUDIP.quickfile.init = true;
            STUDIP.quickfile.load();
        }
    }

    if (e.which === 27) {

        // Prevent mac fullscreen
        e.preventDefault();
        $('#quickfilewrapper').fadeOut(400);
    }
});

$('html').click(function (e) {
    if (e.target.id == "quickfile")
        return;
    //For descendants of menu_content being clicked, remove this check if you do not want to put constraint on descendants.
    if ($(e.target).closest('#quickfile').length)
        return;
    $('#quickfilewrapper').fadeOut(400);
});

// Quickfile loader
STUDIP.quickfile = {
    timeout: null,
    init: false,
    load: function () {
        var list = $('#quickfile #quickfilelist');
        $('#quickfileinput input').addClass('quickfile_ajax');
        $.ajax({
            method: "POST",
            url: STUDIP.URLHelper.getURL('plugins.php/QuickfilePlugin/find'),
            data: {search: $('#quickfileinput input').val()},
            dataType: "json"
        }).done(function (data) {
            list.children().remove();
            $.each(data, function (key, val) {
                list.append($('<li>')
                        .append($('<a>', {
                            html: val.name,
                            'href': STUDIP.URLHelper.getURL('sendfile.php?type=0&file_id=' + val.id + '&file_name=' + val.filename)
                        })
                            .append($('<p>', {html: val.course}))
                            .append($('<div>', {class: 'quickfiledate', text: val.date})))
                        .mouseenter(function (e) {
                            list.children().removeClass('selected');
                            $(e.target).closest('li').addClass('selected');
                        })
                );
            });
            list.children().first().addClass('selected');
            $('#quickfileinput input').removeClass('quickfile_ajax');
        });
    }
};

//Up and down keys
$(document).ready(function () {
    $('#quickfilewrapper').keydown(function (e) {

        var list = $('#quickfile #quickfilelist');
        switch (e.which) {
            case 27:
                $('#quickfilewrapper').fadeOut(400);
                break;

            case 13: // enter
                window.location.href = list.children('.selected').find('a').attr('href');
                break;

            case 38: // up
                e.preventDefault();
                var elem = list.children('.selected');
                if (elem.prev().length > 0) {
                    elem.removeClass('selected').prev().addClass('selected');
                }
                break;

            case 40: // down
                e.preventDefault();
                var elem = list.children('.selected');
                if (elem.next().length > 0) {
                    elem.removeClass('selected').next().addClass('selected');
                }
                break;

            default:
                clearTimeout(STUDIP.quickfile.timeout);
                STUDIP.quickfile.timeout = setTimeout(function () {
                    STUDIP.quickfile.load();
                }, 600);
        }
    });
});

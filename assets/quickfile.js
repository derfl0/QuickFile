// Set close and open
$(document).keydown(function (e) {
    if (e.which === 70 && e.ctrlKey) {
        $('#quickfilewrapper').fadeIn(400);
        $('#quickfilewrapper input').focus();
        var list = $('#quickfile #quickfilelist');
        STUDIP.quickfile.load();
    }

    if (e.which === 27) {
        $('#quickfilewrapper').fadeOut(400);
    }
});

// Quickfile loader
STUDIP.quickfile = {
    timeout: null,
    load: function () {
        var list = $('#quickfile #quickfilelist');
        $.ajax({
            method: "POST",
            url: STUDIP.URLHelper.getURL('plugins.php/QuickfilePlugin/find'),
            data: {search: $('#quickfileinput input').val()},
            dataType: "json"
        }).done(function (data) {
            list.children().remove();
            $.each(data, function (key, val) {
                list.append($('<li>', {text: val.name, 'data-id': val.id, 'data-filename': val.filename})
                        .append($('<p>', {text: val.course}))
                        .append($('<div>', {class: 'quickfiledate', text: val.date}))
                        .mouseenter(function (e) {
                            list.children().removeClass('selected');
                            $(e.target).addClass('selected');
                        })
                        .click(function () {
                            window.location.href = STUDIP.URLHelper.getURL('sendfile.php?type=0&file_id=' + val.id + '&file_name=' + val.filename);
                        })
                        );
            });
            list.children().first().addClass('selected');
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

            case 13:
                var id = list.children('.selected').data().id;
                var name = list.children('.selected').data().filename;
                window.location.href = STUDIP.URLHelper.getURL('sendfile.php?type=0&file_id=' + id + '&file_name=' + name);
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

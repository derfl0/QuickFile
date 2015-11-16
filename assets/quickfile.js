// Set close and open
$(document).keydown(function (e) {

    /* ctrl + shift + f */
    //if (e.which === 70 && e.ctrlKey && e.shiftKey) {
    /* ctrl + space */
    if (e.which === 32 && e.ctrlKey) {
        e.preventDefault();
        STUDIP.quickfile.open();
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
    cache: [],
    open: function () {
        $('#quickfilewrapper').fadeIn(400);
        $('#quickfilewrapper input').focus();
        STUDIP.quickfile.load();
    },
    load: function () {

        // Get typed value
        var val = $('#quickfileinput input').val();
        if (STUDIP.quickfile.cache[val] != undefined) {

            // Load from cache
            STUDIP.quickfile.display(STUDIP.quickfile.cache[val]);
        } else {
            $('#quickfileinput input').addClass('quickfile_ajax');
            $.ajax({
                method: "POST",
                url: STUDIP.URLHelper.getURL('plugins.php/QuickfilePlugin/find'),
                data: {search: val},
                dataType: "json"
            }).done(function (data) {

                // Cache result
                STUDIP.quickfile.cache[val] = data;

                // Display
                STUDIP.quickfile.display(data);
                $('#quickfileinput input').removeClass('quickfile_ajax');
            });
        }
    },
    display: function (items) {
        var list = $('#quickfile #quickfilelist');
        list.children().remove();

        // Append all result groups
        $.each(items, function (key, val) {
            var result = $('<li>');
            list.append(result);
            result.append($('<p>', {text:val.name}));
            var resultlist = $('<ul>');
            result.append(resultlist);

            $.each(val.content, function (mykey, hit) {
                resultlist.append($('<li>')
                        .append($('<a>', {
                            html: hit.name,
                            href: hit.url
                        })
                            .append($('<p>', {html: hit.additional}))
                            .append($('<div>', {class: 'quickfiledate', text: hit.date})))
                        .mouseenter(function (e) {
                            list.find('.selected').removeClass('selected');
                            $(e.target).closest('a').addClass('selected');
                        })
                );
            });
        });
        list.find('a').first().addClass('selected');
    },
    init: false,
};

//Up and down keys
$(document).ready(function () {
    $('#quickfilewrapper').keydown(function (e) {

        var list = $('#quickfile #quickfilelist');
        var resultList = list.find('a');
        var selectedItem = list.find('.selected');
        var currentIndex = resultList.index(selectedItem);
        switch (e.which) {
            case 27:
                $('#quickfilewrapper').fadeOut(400);
                break;

            case 13: // enter
                if (list.children('.selected').find('a').length > 0) {
                    window.location.href = list.children('.selected').find('a').attr('href');
                }
                break;

            case 38: // up
                e.preventDefault();
                if (currentIndex > 0) {
                    $(resultList).removeClass('selected');
                    $(resultList[currentIndex - 1]).addClass('selected');
                }
                break;

            case 40: // down
                e.preventDefault();
                if (resultList.size() - 1 > currentIndex) {
                    $(resultList).removeClass('selected');
                    $(resultList[currentIndex + 1]).addClass('selected');
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

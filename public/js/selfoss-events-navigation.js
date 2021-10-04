/**
 * initialize navigation events
 */
selfoss.events.navigation = function () {

    // // init colorpicker
    // $(".color").spectrum({
    //     showPaletteOnly: true,
    //     color: 'blanchedalmond',
    //     palette: [
    //         ['#ffccc9', '#ffce93', '#fffc9e', '#ffffc7', '#9aff99', '#96fffb', '#cdffff' , '#cbcefb', '#fffe65', '#cfcfcf', '#fd6864', '#fe996b','#fcff2f', '#67fd9a', '#38fff8', '#68fdff', '#9698ed', '#c0c0c0', '#fe0000', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd', '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc', '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9', '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498', '#000000', '#330001', '#643403', '#663234', '#343300', '#013300', '#003532', '#010066', '#340096']
    //     ],
    //     change: function(color) {
    //         $(this).css('backgroundColor', color.toHexString());

    //         $.ajax({
    //             url: $('base').attr('href') + 'tags/color',
    //             type: 'POST',
    //             data: {
    //                 tag: $(this).parent().find('.tag').html(),
    //                 color: color.toHexString()
    //             },
    //             success: function() {
    //                 selfoss.reloadList();
    //             },
    //             error: function(jqXHR, textStatus, errorThrown) {
    //                 selfoss.showError('Can not save new color: '+
    //                                   textStatus+' '+errorThrown);
    //             }
    //         });

    //     }
    // });

    // filter
    $('#nav-filter > li').unbind('click').click(function () {
        if ($(this).hasClass('nav-filter-newest'))
            selfoss.filter.type = 'newest';
        else if ($(this).hasClass('nav-filter-unread'))
            selfoss.filter.type = 'unread';
        else if ($(this).hasClass('nav-filter-starred'))
            selfoss.filter.type = 'starred';

        $('#nav-filter > li').removeClass('active');
        $(this).addClass('active');

        selfoss.filter.offset = 0;
        selfoss.reloadList();

        if (selfoss.isSmartphone())
            $('#nav-mobile-settings').click();
    });

    // init mobile filter view
    switch (selfoss.filter.type) {
        case 'unread':
            $('#nav-mobile-filter').html($('#nav-filter-unread').html());
            break;
        case 'starred':
            $('#nav-mobile-filter').html($('#nav-filter-starred').html());
            break;
        default:
            $('#nav-mobile-filter').html($('#nav-filter-newest').html());
            break;
    }

    // tag
    $('#nav-tags > li').unbind('click').click(function () {
        $('#nav-tags > li').removeClass('active');
        $('#nav-sources > li').removeClass('active');
        $(this).addClass('active');

        selfoss.filter.source = '';
        selfoss.filter.tag = '';
        if ($(this).hasClass('nav-tags-all') == false)
            selfoss.filter.tag = $(this).find('span').html();

        selfoss.filter.offset = 0;
        selfoss.reloadList();

        if (selfoss.isSmartphone())
            $('#nav-mobile-settings').click();
    });

    // hide/show tags
    $('#nav-tags-title').unbind('click').click(function () {
        $('#nav-tags').slideToggle("slow");
        $('#nav-tags-title').toggleClass("nav-tags-collapsed nav-tags-expanded");
        $('#nav-tags-title').attr('aria-expanded', function (i, attr) {
            return attr == 'true' ? 'false' : 'true';
        });
    });

    // source
    $('#nav-sources > li').unbind('click').click(function () {
        $('#nav-tags > li').removeClass('active');
        $('#nav-sources > li').removeClass('active');
        $(this).addClass('active');

        selfoss.filter.tag = '';
        selfoss.filter.source = $(this).attr('id').substr(6);

        selfoss.filter.offset = 0;
        selfoss.reloadList();

        if (selfoss.isSmartphone())
            $('#nav-mobile-settings').click();
    });

    // hide/show sources
    $('#nav-sources-title').unbind('click').click(function () {
        var toggle = function () {
            $('#nav-sources').slideToggle("slow");
            $('#nav-sources-title').toggleClass("nav-sources-collapsed nav-sources-expanded");
            $('#nav-sources-title').attr('aria-expanded', function (i, attr) {
                return attr == 'true' ? 'false' : 'true';
            });
        }

        selfoss.filter.sourcesNav = $('#nav-sources-title').hasClass("nav-sources-collapsed");
        if (selfoss.filter.sourcesNav && !selfoss.sourcesNavLoaded) {
            $.ajax({
                url: $('base').attr('href') + 'sources/statslist',
                type: 'GET',
                success: function (data) {
                    selfoss.refreshSources(data.sources);
                    selfoss.sourcesNavLoaded = true;
                    toggle();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    selfoss.showError('Can not load nav stats: ' +
                        textStatus + ' ' + errorThrown);
                }
            });
        } else {
            toggle();
        }
    });

    // emulate clicking when using keyboard
    $('#nav-filter > li, #nav-tags > li, #nav-tags-title, #nav-sources > li, #nav-sources-title').unbind('keypress').keypress(function (e) {
        if (e.keyCode === 13) {
            $(this).click();
        }
    });

    // show hide navigation for mobile version
    $('#nav-mobile-settings').unbind('click').click(function () {
        var nav = $('#nav');

        // show
        if (nav.is(':visible') == false) {
            nav.slideDown(400, function () {
                location.hash = "nav";
                $(window).scrollTop(0);
            });

            // hide mobile filter view
            $('#nav-mobile-filter').hide();

            // hide
        } else {
            nav.slideUp(400, function () {
                if (location.hash == "#nav") {
                    location.hash = "";
                }
                $(window).scrollTop(0);
            });

            // show mobile filter view
            $('#nav-mobile-filter').show();
        }

    });

    // updates sources
    $('#nav-refresh').unbind('click').click(function () {
        // show loading
        var content = $('#content');
        var articleList = content.html();
        $('#content').addClass('loading').html("");

        $.ajax({
            url: $('base').attr('href') + 'update',
            type: 'GET',
            dataType: 'text',
            data: {},
            success: function (response) {
                // hide nav on smartphone
                if (selfoss.isSmartphone())
                    $('#nav-mobile-settings').click();

                // refresh list
                selfoss.reloadList();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                content.html(articleList);
                $('#content').removeClass('loading');
                alert('Can not refresh sources: ' + errorThrown);
            }
        });
    });

    // login
    $('#nav-login').unbind('click').click(function () {
        window.location.href = $('base').attr('href') + "?login=1";
    });

    // only loggedin users
    if ($('body').hasClass('loggedin') == true) {
        //      $('#nav-mark').unbind('click').click(selfoss.markVisibleRead);

        // show sources
        $('#nav-settings').unbind('click').click(function () {
            location.hash = "sources";

            if (selfoss.isSmartphone())
                $('#nav-mobile-settings').click();
        });


        // logout
        $('#nav-logout').unbind('click').click(function () {
            window.location.href = $('base').attr('href') + "?logout=1";
        });
    }
};
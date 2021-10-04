selfoss.shortcuts = {

    /**
     * init shortcuts
     */
    init: function () {
        // 'space': next article
        $(document).bind('keydown', 'space', function (e) {
            var selected = $('.entry.selected');
            if (selected.length > 0 && selected.find('.entry-content').is(':visible') == false) {
                selected.find('.entry-title').click();
            } else {
                selfoss.shortcuts.nextprev('next', true);
            }
            e.preventDefault();
            return false;
        });

        // 'n': next article
        $(document).bind('keydown', 'n', function (e) {
            selfoss.shortcuts.nextprev('next', false);
            e.preventDefault();
            return false;
        });

        // 'right cursor': next article
        $(document).bind('keydown', 'right', function (e) {
            selfoss.shortcuts.entrynav('next');
            e.preventDefault();
            return false;
        });

        // 'j': next article
        $(document).bind('keydown', 'j', function (e) {
            selfoss.shortcuts.nextprev('next', true);
            e.preventDefault();
            return false;
        });

        // 'shift+space': previous article
        $(document).bind('keydown', 'shift+space', function (e) {
            selfoss.shortcuts.nextprev('prev', true);
            e.preventDefault();
            return false;
        });

        // 'p': previous article
        $(document).bind('keydown', 'p', function (e) {
            selfoss.shortcuts.nextprev('prev', false);
            e.preventDefault();
            return false;
        });

        // 'left': previous article
        $(document).bind('keydown', 'left', function (e) {
            selfoss.shortcuts.entrynav('prev');
            e.preventDefault();
            return false;
        });

        // 'k': previous article
        $(document).bind('keydown', 'k', function (e) {
            selfoss.shortcuts.nextprev('prev', true);
            e.preventDefault();
            return false;
        });

        // 's': star/unstar
        $(document).bind('keydown', 's', function (e) {
            selfoss.events.entriesToolbar($('.entry.selected'));
            $('.entry.selected .entry-starr').click();
            e.preventDefault();
            return false;
        });

        // 'm': mark/unmark
        $(document).bind('keydown', 'm', function (e) {
            selfoss.events.entriesToolbar($('.entry.selected'));
            $('.entry.selected .entry-unread').click();
            e.preventDefault();
            return false;
        });

        // 'o': open/close entry
        $(document).bind('keydown', 'o', function (e) {
            $('.entry.selected').find('h2').click();
            e.preventDefault();
            return false;
        });

        // 'Shift + o': close open entries
        $(document).bind('keydown', 'Shift+o', function (e) {
            e.preventDefault();
            $('.entry-content, .entry-toolbar').hide();
        });

        // 'v': open target
        $(document).bind('keydown', 'v', function (e) {
            $('.entry.selected .entry-newwindow:eq(0)').click();
            e.preventDefault();
            return false;
        });

        // 'Shift + v': open target and mark read
        $(document).bind('keydown', 'Shift+v', function (e) {
            e.preventDefault();

            selfoss.events.entriesToolbar($('.entry.selected'));

            // mark item as read
            if ($('.entry.selected .entry-unread').hasClass('active')) {
                $('.entry.selected .entry-unread').click();
            }

            // open target
            $('.entry.selected .entry-newwindow:eq(0)').click();
        });

        // 'r': Reload the current view
        $(document).bind('keydown', 'r', function (e) {
            e.preventDefault();
            $('#nav-filter-unread').click();
        });

        // 'Shift + r': Refresh sources
        $(document).bind('keydown', 'Shift+r', function (e) {
            e.preventDefault();
            $('#nav-refresh').click();
        });

        // 'Ctrl+m': mark all as read
        $(document).bind('keydown', 'ctrl+m', function (e) {
            //          $('#nav-mark').click();
            selfoss.markVisibleRead();
            e.preventDefault();
            return false;
        });

        // 't': throw (mark as read & open next)
        $(document).bind('keydown', 't', function (e) {
            $('.entry.selected.unread .entry-unread').click();
            selfoss.shortcuts.nextprev('next', true);
            return false;
        });

        // throw (mark as read & open previous)
        $(document).bind('keydown', 'Shift+t', function (e) {
            $('.entry.selected.unread .entry-unread').click();
            selfoss.shortcuts.nextprev('prev', true);
            e.preventDefault();
            return false;
        });

        // 'Shift+n': switch to newest items overview / menu item
        $(document).bind('keydown', 'Shift+n', function (e) {
            e.preventDefault();
            $('#nav-filter-newest').click();
        });

        // 'Shift+u': switch to unread items overview / menu item
        $(document).bind('keydown', 'Shift+u', function (e) {
            e.preventDefault();
            $('#nav-filter-unread').click();
        });

        // 'Shift+s': switch to starred items overview / menu item
        $(document).bind('keydown', 'Shift+s', function (e) {
            e.preventDefault();
            $('#nav-filter-starred').click();
        });
    },


    /**
     * get next/prev item
     * @param direction
     */
    nextprev: function (direction, open) {
        if (typeof direction == "undefined" || (direction != "next" && direction != "prev"))
            direction = "next";

        // helper functions
        //      var scroll = function(value) {
        //          // scroll down (negative value) and up (positive value)
        //          $('#content').scrollTop($('#content').scrollTop()+value);
        //      };
        // select current
        var old = $('.entry.selected');

        // select next/prev and save it to "current"
        if (direction == "next") {
            if (old.length == 0) {
                current = $('.entry:eq(0)');
            } else {
                current = old.next().length == 0 ? old : old.next();
            }
        } else {
            if (old.length == 0) {
                return;
            }
            else {
                current = old.prev().length == 0 ? old : old.prev();
            }
        }

        // remove active
        old.removeClass('selected');
        old.find('.entry-content').hide();
        old.find('.entry-toolbar').hide();

        if (current.length == 0) { return; }

        // mark as read
        if (current.hasClass('entry-batchtool')) {
            current = ("next" == direction && 0 < current.next().length) ? current.next() : current.prev();
        }

        current.addClass('selected');

        // load more
        if (current.hasClass('stream-more')) {
            current.click().removeClass('selected').prev().addClass('selected');
        }

        // open?
        if (open) {
            var content = current.find('.entry-content');
            if (0 < content.length) {
                // load images
                if ($('#config').data('auto_load_images') == "1") {
                    content.lazyLoadImages();
                    //                  current.next().find('.entry-content').lazyLoadImages();
                }

                // anonymize
                selfoss.anonymize(content);
                content.show();
                current.find('.entry-toolbar').show();
                selfoss.events.entriesToolbar(current);

                // automark as read
                if ($('#config').data('auto_mark_as_read') == "1" && current.hasClass('unread')) {
                    current.find('.entry-unread').click();
                }

                // setup fancyBox image viewer
                selfoss.setupFancyBox(content, content.parent().attr('id').substr(5));
            }
        }

        // scroll to element
        selfoss.shortcuts.autoscroll(current);

        // focus the title for better keyboard navigation
        current.find('.entry-title').focus();
    },


    /**
     * autoscroll
     */
    autoscroll: function (next) {
        var viewportHeight = $(window).height();
        var viewportScrollTop = $(window).scrollTop();

        // scroll down
        if (viewportScrollTop + viewportHeight < next.position().top + next.height() + 80) {
            if (next.height() > viewportHeight) {
                $(window).scrollTop(next.position().top);
            } else {
                var marginTop = (viewportHeight - next.height()) / 2;
                var scrollTop = next.position().top - marginTop;
                $(window).scrollTop(scrollTop);
            }
        }

        // scroll up
        if (next.position().top <= viewportScrollTop) {
            $(window).scrollTop(next.position().top);
        }
    },


    /**
     * entry navigation (next/prev) with keys
     * @param direction
     */
    entrynav: function (direction) {
        if (typeof direction == "undefined" || (direction != "next" && direction != "prev"))
            direction = "next";

        var content = $('.entry-content').is(':visible');
        selfoss.shortcuts.nextprev(direction, content);
    }
};
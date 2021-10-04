/**
 * initialize search events
 */
selfoss.events.search = function () {

    var splitTerm = function (term) {
        if (term == '') return [];
        var words = term.match(/"[^"]+"|\S+/g);
        for (var i = 0; i < words.length; i++)
            words[i] = words[i].replace(/"/g, '');
        return words;
    };

    var joinTerm = function (words) {
        if (!words || words.length <= 0) { return ''; }
        for (var i = 0; i < words.length; i++) {
            if (words[i].indexOf(' ') >= 0) {
                words[i] = '"' + words[i] + '"';
            }
        }
        return words.join(' ');
    };

    var setFilter = function (filter, words) {
        filter.offset = 0;
        filter.items = parseInt($('#config').data('items_perpage'), 10);
        filter.search = '';
        filter.date = '';

        if (!words) { return; }

        var searchWords = [];

        //search operator (special search keyword)
        for (var i = 0; i < words.length; i++) {
            if (0 == words[i].indexOf('items:')) { // items
                filter.items = parseInt(words[i].substr(6), 10);
            }
            else if (0 == words[i].indexOf('date:')) { // date
                filter.date = words[i].substr(5);
            }
            else { // keyword
                searchWords.push(words[i]);
            }
        }

        //search term
        filter.search = joinTerm(searchWords);
    };

    var isFilter = function (filter) {
        return ('' != filter.search || '' != filter.date);
    }

    var executeSearch = function (term) {
        // show words in top of the page
        var words = splitTerm(term);
        $('#search-list').html('');
        var itemId = 0;
        $.each(words, function (index, item) {
            $('#search-list').append('<li id="search-item-' + itemId + '"></li>');
            $('#search-item-' + itemId).text(item);
            itemId++;
        });

        // execute search
        $('#search').removeClass('active');
        setFilter(selfoss.filter, words);
        selfoss.reloadList();

        if (term == '') {
            $('#search-list').hide();
        }
        else {
            $('#search-list').show();
        }
    };

    var datepickerOptions = {
        monthNames: ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
        monthNamesShort: ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
        dateFormat: 'yy-mm-dd',
        firstDay: 1,
        showOn: 'button',
        buttonImage: 'images/calendar.png',
        buttonImageOnly: true,
        buttonText: 'Select date',
        changeMonth: true,
        changeYear: true,
        minDate: null,
        maxDate: 0,
        autoSize: true,
    };
    $('#date-from').datepicker(datepickerOptions);
    $('#date-from').datepicker('option', 'onClose', function (date) {
        $('#date-to').datepicker('option', 'minDate', date);
    });
    $('#date-to').datepicker(datepickerOptions);
    $('#date-to').datepicker('option', 'onClose', function (date) {
        $('#date-from').datepicker('option', 'maxDate', date);
    });

    var inputDate = function () {
        var dfm = $('#date-from').val();
        var dto = $('#date-to').val();
        if (0 >= dfm.length && 0 >= dto.length) { return null; }

        var dOpr = 'date:';
        if (0 < dfm.length) {
            dOpr = dOpr + dfm;
        }
        if (0 < dto.length) {
            dOpr = dOpr + '_' + dto;
        }

        var reKywd = function (dstr, kstr) {
            return dstr + ' ' + kstr.replace(/date:[0-9\-_]+\S*/g, '').trim();
        }

        $('#search-term').val(reKywd(dOpr, $('#search-term').val()));
        $('#nav-search-term').val(reKywd(dOpr, $('#nav-search-term').val()));
        return dOpr;
    };

    var initDateDialog = function (dlgObj) {
        $('#date-from').val('');
        $('#date-from').datepicker('option', 'maxDate', 0);
        $('#date-to').val('');
        $('#date-to').datepicker('option', 'minDate', null);
        dlgObj.dialog('option', 'ex.action', null);
    }

    var dateDialog = $('#date-dialog').dialog({
        dialogClass: 'date-dialog',
        autoOpen: false,
        width: 200,
        modal: true,
        buttons: {
            Ok: function () {
                if (null != inputDate()) {
                    $(this).dialog('option', 'ex.action', 'accept');
                }
                $(this).dialog('close');
            },
            Cancel: function () {
                $(this).dialog('close');
            }
        },
        close: function (event, ui) {
            var action = $(this).dialog('option', 'ex.action');
            initDateDialog($(this));
            $($(this).dialog('option', 'ex.input')).focus();
            if ('accept' == action) {
                $($(this).dialog('option', 'ex.button')).click();
            }
        }
    });
    $('#search-calendar').unbind('click').click(function () {
        dateDialog.dialog('option', 'ex.input', '#search-term');
        dateDialog.dialog('option', 'ex.button', '#search-button');
        dateDialog.dialog('option', 'position',
            { my: 'left top', at: 'left bottom', of: '#search-calendar' });
        dateDialog.dialog('open');
    });
    $('#nav-search-calendar').unbind('click').click(function () {
        dateDialog.dialog('option', 'ex.input', '#nav-search-term');
        dateDialog.dialog('option', 'ex.button', '#nav-search-button');
        dateDialog.dialog('option', 'position',
            { my: 'right bottom', at: 'right top', of: '#nav-search-calendar' });
        dateDialog.dialog('open');
    });

    // search button shows search input or executes search
    $('#search-button').unbind('click').click(function () {
        if ($('#search').hasClass('active') == false) {
            $('#search').addClass('active');
            $('#search-term').focus().select();
            return;
        }
        $('#nav-search-term').val($('#search-term').val()); // sync
        executeSearch($('#search-term').val());
        $('#search-term').blur();
    });

    // navigation search button for mobile navigation
    $('#nav-search-button').unbind('click').click(function () {
        $('#search-term').val($('#nav-search-term').val()); // sync
        executeSearch($('#nav-search-term').val());
        $('#nav-mobile-settings').click();
    });

    // keypress enter in search inputfield
    // memo:
    //  IME On : [keydown] > [keyup]
    //  IME Off: [keydown] > [keypress] > [keyup]
    $('#search-term').unbind('keypress').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#search-button').click();
        }
        if (e.keyCode == 27) {
            $('#search-remove').click();
        }
    });
    $('#nav-search-term').unbind('keypress').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#nav-search-button').click();
        }
        if (e.keyCode == 27) {
            $('#search-remove').click();
            $('#nav-mobile-settings').click();
        }
    });

    // search term list in top of the page
    $('#search-list li').unbind('click').click(function () {
        var termArray = splitTerm($('#search-term').val());
        termId = $(this).attr('id').replace('search-item-', '');
        termArray.splice(termId, 1);
        var newterm = joinTerm(termArray);
        $('#search-term').val(newterm);
        $('#nav-search-term').val(newterm);
        executeSearch($('#search-term').val());
    });

    // remove button of search
    $('#search-remove').unbind('click').click(function () {
        $('#search-list').hide();
        $('#search-list').html('');
        $('#search').removeClass('active');
        $('#search-term').val('');
        $('#search-term').blur();
        $('#nav-search-term').val('');

        if (isFilter(selfoss.filter)) {
            setFilter(selfoss.filter, null);
            selfoss.reloadList();
        }
    });
};

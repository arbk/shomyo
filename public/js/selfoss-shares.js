selfoss.shares = {
    initialized: false,
    enabledShares: null,
    names: {},
    images: {},
    openInNewWindows: {},
    urlBuilders: {},

    init: function (configShare) {
        this.initialized = true;

        if (!configShare) { return; }

        this.enabledShares = configShare.split(',');

        this.register('twtr', 'Twitter', 'share-twtr.png', true, function (url, title) {
            return "https://twitter.com/intent/tweet?source=webclient&text=" + encodeURIComponent(title) + " " + encodeURIComponent(url);
        });
        this.register('fcbk', 'Facebook', 'share-fcbk.png', true, function (url, title) {
            return "https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(url) + "&t=" + encodeURIComponent(title);
        });
        this.register('pket', 'Pocket', 'share-pket.png', true, function (url, title) {
            return "https://getpocket.com/save?url=" + encodeURIComponent(url) + "&title=" + encodeURIComponent(title);
        });
        this.register('rbly', 'Readability', 'share-rbly.png', true, function (url, title) {
            return "https://www.readability.com/save?url=" + encodeURIComponent(url);
        });
        this.register('gglp', 'Google+', 'share-gglp.png', true, function (url, title) {
            return "https://plus.google.com/share?url=" + encodeURIComponent(url);
        });
        this.register('deli', 'Delicious', 'share-deli.png', true, function (url, title) {
            return "https://delicious.com/save?url=" + encodeURIComponent(url) + "&title=" + encodeURIComponent(title);
        });

        this.register('wllg', 'wallabag', 'share-wllg.png', true, function (url, title) {
            return $('#config').data('wallabag') + '/?action=add&url=' + btoa(url);
        });
        this.register('wrdp', 'WordPress', 'share-wrdp.png', true, function (url, title) {
            return $('#config').data('wordpress') + '/wp-admin/press-this.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title);
        });

        this.register('mail', 'Mail', 'share-mail.png', false, function (url, title) {
            return "mailto:?body=" + encodeURIComponent(url) + "&subject=" + encodeURIComponent(title);
        });
    },

    register: function (id, name, image, openInNewWindow, urlBuilder) {
        if (!this.initialized) {
            return false;
        }
        this.names[id] = name;
        this.images[id] = image;
        this.openInNewWindows[id] = openInNewWindow;
        this.urlBuilders[id] = urlBuilder;
        return true;
    },

    getAll: function () {
        if (this.enabledShares && 0 < this.enabledShares.length) {
            return this.enabledShares.concat();
        }
        return null;
    },

    share: function (id, url, title) {
        var url = this.urlBuilders[id](url, title);
        if (this.openInNewWindows[id]) {
            window.open(url);
        } else {
            document.location.href = url;
        }
    },

    buildLinks: function (shares, linkBuilder) {
        var links = '';
        if (shares != null) {
            for (var i = 0; i < shares.length; i++) {
                var id = shares[i];
                links += linkBuilder(id, this.names[id], this.images[id]);
            }
        }
        return links;
    }
};
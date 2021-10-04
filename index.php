<?php

//init session settings
session_name("WSRVSID");

// callback for just before output
header_register_callback(function () {
    header_remove("X-Powered-By");
});

require __DIR__ . '/common.php';

// language settings
$lang = $f3->get('language');
if ($lang!='' && $lang!='0') {
  // load custom language
    $f3->set('LANGUAGE', $lang);
} else {
  // set default language ('LANGUAGE' is 'Accept-Language')
    $langs = explode(',', $f3->get('LANGUAGE'));
    $f3->set('language', $langs[0]);
}

// init authentication
$f3->set('auth', new \helpers\Authentication());

// define js files
$js=array(
  'public/js/jquery-3.6.0.min.js',
  'public/js/jquery-migrate-3.3.2.min.js',
  'public/js/jquery-ui.min.js',
  'public/js/jquery.mCustomScrollbar.min.js',
  'public/js/jquery.fancybox.pack.js',
  'public/js/jquery.mousewheel.min.js',
  'public/js/lazy-image-loader.js',
  'public/js/jquery.hotkeys.js',
  'public/js/selfoss-base.js',
  'public/js/selfoss-shares.js',
  'public/js/selfoss-events.js',
  'public/js/selfoss-events-navigation.js',
  'public/js/selfoss-events-search.js',
  'public/js/selfoss-events-entries.js',
  'public/js/selfoss-events-entriestoolbar.js',
  'public/js/selfoss-events-sources.js',
  'public/js/selfoss-shortcuts.js',
);
if (file_exists("user.js")) {
    $js[] = "user.js";
}
$f3->set('js', $js);

// define css files
$css = array(
  'public/css/jquery-ui.min.css',
  'public/css/jquery.mCustomScrollbar.css',
  'public/css/jquery.fancybox.css',
  'public/css/reset.css',
  'public/css/style.css'
);
if (file_exists("user.css")) {
    $css[] = "user.css";
}
$f3->set('css', $css);


// define routes

// all users
$f3->route('GET /', 'controllers\Index->home');              // html
$f3->route('POST /', 'controllers\Index->home');              // html
$f3->route('GET /password', 'controllers\Index->password');          // html
$f3->route('POST /password', 'controllers\Index->password');          // html
$f3->route('GET /login', 'controllers\Index->login');             // json
$f3->route('GET /logout', 'controllers\Index->logout');            // json
$f3->route('GET /update', 'controllers\Index->update');            // text
$f3->route('GET /optimize', 'controllers\Index->optimize');          // text
$f3->route('GET /badge', 'controllers\Index->badge');             // xml
$f3->route('GET /win8notifs', 'controllers\Index->win8Notifications'); // xml

// only for loggedin users or on public mode
$f3->route('GET /rss', 'controllers\Rss->rss');              // rss
$f3->route('GET /feed', 'controllers\Rss->rss');              // rss
$f3->route('GET /items', 'controllers\Items->listItems');      // json
$f3->route('GET /tags', 'controllers\Tags->listTags');        // json
$f3->route('GET /tagslist', 'controllers\Tags->tagslist');        // html
$f3->route('GET /stats', 'controllers\Items->stats');          // json
$f3->route('GET /sources/stats', 'controllers\Sources->stats');        // json
$f3->route('GET /sources/statslist', 'controllers\Sources->sourcesStats'); // json (html)

// only loggedin users
$f3->route('POST /mark/@item', 'controllers\Items->mark');    // json
$f3->route('POST /mark', 'controllers\Items->mark');    // json [mark for multiple]
$f3->route('POST /unmark/@item', 'controllers\Items->unmark');  // json
$f3->route('POST /starr/@item', 'controllers\Items->starr');   // json
$f3->route('POST /unstarr/@item', 'controllers\Items->unstarr'); // json
$f3->route('POST /unstarr', 'controllers\Items->unstarr'); // json [unstarr for multiple]

$f3->route('GET    /source/params', 'controllers\Sources->params');      // html
$f3->route('GET    /sources', 'controllers\Sources->show');        // html
$f3->route('GET    /source', 'controllers\Sources->add');         // html
$f3->route('GET    /sources/list', 'controllers\Sources->listSources'); // json
$f3->route('POST   /source/@id', 'controllers\Sources->write');       // json
$f3->route('POST   /source', 'controllers\Sources->write');       // json
$f3->route('DELETE /source/@id', 'controllers\Sources->remove');      // json
$f3->route('POST   /source/delete/@id', 'controllers\Sources->remove');      // json
$f3->route('GET    /sources/spouts', 'controllers\Sources->spouts');      // json

//$f3->route('POST /tags/color', 'controllers\Tags->color'); // json

$f3->route('GET  /opml', 'controllers\Opml->show');   // html
$f3->route('POST /opml', 'controllers\Opml->add');    // html
$f3->route('GET  /opmlexport', 'controllers\Opml->export'); // xml

$f3->route('GET  /export', 'controllers\Export->export'); // text,html,csv,etc.

if (\F3::get('DEBUG')!=0) {
    $f3->route('GET  /develop/info', 'controllers\Develop->info'); // html
}

// dispatch
$f3->run();

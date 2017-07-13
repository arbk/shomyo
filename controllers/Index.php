<?PHP

namespace controllers;

/**
 * Controller for root
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     arbk (https://aruo.net/)
 */
class Index extends BaseController {

    /**
     * home site
     * html
     *
     * @return void
     */
    public function home() {
        // check login
        $this->authentication();

        // parse params
        $options = array();

        // use ajax given params?
        if(count($_GET)>0)
            $options = $_GET;

        // type
        if( !isset($options['type']) ){
            $options['type'] = \F3::get('homepage');
        }
        if( !in_array($options['type'], array('unread','newest','starred'), true) ){
            $options['type'] = 'unread';
        }
        if( $options['type']==='unread' && \F3::get('auth')->isLoggedin()!==true ){
            $options['type'] = 'newest';
        }
        $this->view->homepage = $options['type'];

        // get search param
        if(isset($options['search']) && strlen($options['search'])>0)
            $this->view->search = $options['search'];

        // get date param
        if(isset($options['date']) && strlen($options['date'])>0){
            $this->view->date = $options['date'];
        }

        // load tags
        $tagsDao = new \daos\Tags();
        $tags = $tagsDao->getWithUnread();

        // load items
        $itemsHtml = $this->loadItems($options, $tags);
        $this->view->content = $itemsHtml;

        // load stats
        $itemsDao = new \daos\Items();
        $stats = $itemsDao->stats();
        $this->view->statsAll = $stats['total'];
        $this->view->statsUnread = $stats['unread'];
        $this->view->statsStarred = $stats['starred'];

        if ($tagsDao->hasTag("#")) {
		foreach ($tags as $tag) {
			if (strcmp($tag["tag"], "#") !== 0) {
				continue;
			}
			$this->view->statsUnread -= $tag["unread"];
		}
	}

        // prepare tags display list
        $tagsController = new \controllers\Tags();
        $this->view->tags = $tagsController->renderTags($tags);

        if(isset($options['sourcesNav']) && $options['sourcesNav'] == 'true' ) {
          // prepare sources display list
          $sourcesDao = new \daos\Sources();
          $sources = $sourcesDao->getWithUnread();
          $sourcesController = new \controllers\Sources();
          $this->view->sources = $sourcesController->renderSources($sources);
        }
        else{
          $this->view->sources = '';
        }

        // ajax call = only send entries and statistics not full template
        if(isset($options['ajax'])) {
            $this->view->jsonSuccess(array(
                "entries"  => $this->view->content,
                "all"      => $this->view->statsAll,
                "unread"   => $this->view->statsUnread,
                "starred"  => $this->view->statsStarred,
                "tags"     => $this->view->tags,
                "sources"  => $this->view->sources
            ));
        }

        // show as full html page
        $this->view->publicMode = \F3::get('auth')->isLoggedin()!==true && \F3::get('public')==1;
        $this->view->loggedin = \F3::get('auth')->isLoggedin()===true;
        $this->view->loginInvalidate = \F3::get('login_invalidate')==1;
        echo $this->view->render('templates/home.phtml');
    }


    /**
     * password hash generator
     * html
     *
     * @return void
     */
    public function password() {
        $this->loginInvalid();

        $this->view = new \helpers\View();
        $this->view->password = true;
        if(isset($_POST['password']))
            $this->view->hash = hash("sha512", \F3::get('salt') . $_POST['password']);
        echo $this->view->render('templates/login.phtml');
    }


    /**
     * check and show login/logout
     * html
     *
     * @return void
     */
    private function authentication() {
        // logout
        if(isset($_GET['logout'])) {
            \F3::get('auth')->logout();
            \F3::reroute($this->view->base);
        }

        // login
        if(
            isset($_GET['login']) || (\F3::get('auth')->isLoggedin()!==true && \F3::get('public')!=1)
           ) {
             $this->loginInvalid();

            // authenticate?
            if(count($_POST)>0) {
                if(!isset($_POST['username']))
                    $this->view->error = 'no username given';
                else if(!isset($_POST['password']))
                    $this->view->error = 'no password given';
                else {
                    if(\F3::get('auth')->login($_POST['username'], $_POST['password'])===false)
                        $this->view->error = 'invalid username/password';
                }
            }

            // show login
            if(count($_POST)==0 || isset($this->view->error))
                die($this->view->render('templates/login.phtml'));
            else
                \F3::reroute($this->view->base);
        }
    }


    /**
     * login for api json access
     * json
     *
     * @return void
     */
    public function login() {
        $this->loginInvalid();

        $view = new \helpers\View();
        $username = isset($_REQUEST["username"]) ? $_REQUEST["username"] : '';
        $password = isset($_REQUEST["password"]) ? $_REQUEST["password"] : '';

        if(\F3::get('auth')->login($username,$password)==true)
            $view->jsonSuccess(array(
                'success' => true
            ));

        $view->jsonSuccess(array(
            'success' => false
        ));
    }


    /**
     * logout for api json access
     * json
     *
     * @return void
     */
    public function logout() {
        $view = new \helpers\View();
        \F3::get('auth')->logout();
        $view->jsonSuccess(array(
            'success' => true
        ));
    }


    /**
     * update feeds
     * text
     *
     * @return void
     */
    public function update() {
        // only allow access for localhost and loggedin users
        if (\F3::get('allow_public_update_access')!=1
                && $_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR']
                && $_SERVER['REMOTE_ADDR'] !== "127.0.0.1"
                && \F3::get('auth')->isLoggedin() != 1)
            die("unallowed access");

        // update feeds
        $loader = new \helpers\ContentLoader();
        $loader->update();

        echo "finished";
    }


    /**
     * optimize feed data.
     * text
     *
     * @return void
     */
    public function optimize() {
      // only allow access for localhost and loggedin users
      if (\F3::get('allow_public_update_access')!=1
          && $_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR']
          && $_SERVER['REMOTE_ADDR'] !== "127.0.0.1"
          && \F3::get('auth')->isLoggedin() != 1)
            die("unallowed access");

          // optimize feed data
          $loader = new \helpers\ContentLoader();
          $loader->optimize();

          echo "finished";
    }


    /*
    * get the unread number of items for a windows 8 badge
    * notification.
    */
    public function badge() {
        // load stats
        $itemsDao = new \daos\Items();
        $this->view->statsUnread = $itemsDao->numberOfUnread();
        echo $this->view->render('templates/badge.phtml');
    }

    public function win8Notifications() {
        echo $this->view->render('templates/win8-notifications.phtml');
    }


    /**
     * load items
     *
     * @return html with items
     */
    private function loadItems($options, $tags) {
        $tagColors = $this->convertTagsToAssocArray($tags);

        $itemDao = new \daos\Items();
        $itemsHtml = "";
        $lastItemId = "";
        foreach($itemDao->get($options) as $item) {

            // parse tags and assign tag colors
            $itemsTags = explode(",",$item['tags']);
            $item['tags'] = array();
            foreach($itemsTags as $tag) {
                $tag = trim($tag);
                if(strlen($tag)>0 && isset($tagColors[$tag]))
                    $item['tags'][$tag] = $tagColors[$tag];
            }

            $this->view->item = $item;
            $itemsHtml .= $this->view->render('templates/item.phtml');
            $lastItemId = $item['id'];
        }

        if(strlen($itemsHtml)==0) {
            $itemsHtml = '<div class="stream-empty">'. \F3::get('lang_no_entries').'</div>';
        } else {
            if(\F3::get('auth')->isLoggedin()===true){
                $itemsHtml .= '<div class="entry-batchtool"><div id="entry-markread'.$lastItemId.'" data-itemid="'.$lastItemId.'" class="entry-markread">'.\F3::get('lang_markread').'</div>';
                if('starred'===$options['type']){
                  $itemsHtml .= '<div id="entry-unstarr'.$lastItemId.'" data-itemid="'.$lastItemId.'" class="entry-unstarr">'.\F3::get('lang_unstar').'</div>';
                }
                $itemsHtml .= '</div>';
            }

            if($itemDao->hasMore()) {
                $itemsHtml .= '<div class="stream-more"><span>'. \F3::get('lang_more').'</span></div>';
            }
        }

        return $itemsHtml;
    }


    /**
     * return tag => color array
     *
     * @return tag color array
     * @param array $tags
     */
    private function convertTagsToAssocArray($tags) {
        $assocTags = array();
        foreach($tags as $tag) {
            $assocTags[$tag['tag']]['backColor'] = $tag['color'];
            $assocTags[$tag['tag']]['foreColor'] = \helpers\Color::colorByBrightness($tag['color']);
        }
        return $assocTags;
    }
}
<?PHP

namespace controllers;

/**
 * Controller for item handling
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     arbk (http://aruo.net/)
 */
class Items extends BaseController {

    /**
     * mark items as read. Allows one id or an array of ids
     * json
     *
     * @return void
     */
    public function mark() {
      $this->updateItemStatus('mark');
    }


    /**
     * mark item as unread
     * json
     *
     * @return void
     */
    public function unmark() {
      $this->updateItemStatus('unmark');
    }


    /**
     * starr item
     * json
     *
     * @return void
     */
    public function starr() {
      $this->updateItemStatus('starr');
    }


    /**
     * unstarr item
     * json
     *
     * @return void
     */
    public function unstarr() {
      $this->updateItemStatus('unstarr');
    }


    /**
     * update item status
     * json
     *
     * @return void
     * @param string $type ('mark' or 'unmark' or 'starr' or 'unstarr')
     */
    private function updateItemStatus($type) {
      $this->needsLoggedIn();

      $id = null;
      if( null!=\F3::get('PARAMS.item') ){ $id = \F3::get('PARAMS.item'); }  // id: numeric
      else if( isset($_POST['ids']) ){ $id = $_POST['ids']; }               // id: array

      $itemDao = new \daos\Items();

      if( !$itemDao->isValid('id', $id) ){ $this->view->error('invalid id'); }

      $success = true;
      switch( $type ){
        case 'mark':
          $itemDao->mark($id);
          break;
        case 'unmark':
          $itemDao->unmark($id);
          break;
        case 'starr':
          $itemDao->starr($id);
          break;
        case 'unstarr':
          $itemDao->unstarr($id);
          break;
        default:
          $success = false;
          break;
      }

      $this->view->jsonSuccess( array('success' => $success) );
    }


    /**
     * returns items as json string
     * json
     *
     * @return void
     */
    public function listItems() {
        $this->needsLoggedInOrPublicMode();

        // parse params
        $options = array();
        if(count($_GET)>0)
            $options = $_GET;

        // get items
        $itemDao = new \daos\Items();
        $items = $itemDao->get($options);

        $this->view->jsonSuccess($items);
    }


    /**
     * returns current basic stats
     * json
     *
     * @return void
     */
    public function stats() {
        $this->needsLoggedInOrPublicMode();

        $itemsDao = new \daos\Items();
        $stats = $itemsDao->stats();

        $stats['unread'] -= $itemsDao->numberOfUnreadForTag("#");
        $tagsDao = new \daos\Tags();
        $tags = $tagsDao->getWithUnread();
        if( $tagsDao->hasTag("#") ){
            foreach( $tags as $tag ){
                if(strcmp($tag["tag"], "#") !== 0) {
                    continue;
                }
                $stats['unread'] -= $tag["unread"];
            }
        }

        if( array_key_exists('tags', $_GET) && $_GET['tags'] == 'true' ) {
            $tagsDao = new \daos\Tags();
            $tagsController = new \controllers\Tags();
            $stats['tagshtml'] = $tagsController->renderTags($tagsDao->getWithUnread());
        }
        if( array_key_exists('sources', $_GET) && $_GET['sources'] == 'true' ) {
            $sourcesDao = new \daos\Sources();
            $sourcesController = new \controllers\Sources();
            $stats['sourceshtml'] = $sourcesController->renderSources($sourcesDao->getWithUnread());
        }

        $this->view->jsonSuccess($stats);
    }
}
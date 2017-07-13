<?PHP

namespace controllers;

/**
 * Controller for sources handling
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     arbk (https://aruo.net/)
 */
class Sources extends BaseController {

    /**
     * list all available sources
     * html
     *
     * @return void
     */
    public function show() {
        $this->needsLoggedIn();

        // get available spouts
        $spoutLoader = new \helpers\SpoutLoader();
        $this->view->spouts = $spoutLoader->all();

        // load sources
        $sourcesDao = new \daos\Sources();
        echo '<button class="source-add">' . \F3::get('lang_source_add') . '</button>' .
             '<a rel="nofollow" class="source-export" href="opmlexport">' . \F3::get('lang_source_export') . '</a>' .
             '<a rel="nofollow" class="source-opml" href="opml">' . \F3::get('lang_source_opml');
        $sourcesHtml = '</a>';
        $i=0;

        foreach($sourcesDao->getWithIcon() as $source) {
            $this->view->source = $source;
            $sourcesHtml .= $this->view->render('templates/source.phtml');
        }

        echo $sourcesHtml;
    }


    /**
     * add new source
     * html
     *
     * @return void
     */
    public function add() {
        $this->needsLoggedIn();

        $spoutLoader = new \helpers\SpoutLoader();
        $this->view->spouts = $spoutLoader->all();
        echo $this->view->render('templates/source.phtml');
    }


    /**
     * render spouts params
     * html
     *
     * @return void
     */
    public function params() {
        $this->needsLoggedIn();

        if(!isset($_GET['spout']))
            $this->view->error('no spout type given');

        $spoutLoader = new \helpers\SpoutLoader();

        $spout = str_replace("_", "\\", $_GET['spout']);
        $this->view->spout = $spoutLoader->get($spout);

        if($this->view->spout===false)
            $this->view->error('invalid spout type given');

        if($this->view->spout->params!==false){
            $this->view->idAttr = 'new-' . rand();
            echo $this->view->render('templates/source_params.phtml');
        }
    }


    /**
     * return all Sources suitable for navigation panel
     * html
     *
     * @return htmltext
     */
    public function renderSources($sources) {
        $html = "";
//      $itemsDao = new \daos\Items();
        foreach($sources as $source) {
            $this->view->source = $source['title'];
            $this->view->sourceid = $source['id'];
            $this->view->unread = $source['unread'];
            $html .= $this->view->render('templates/source-nav.phtml');
        }

        return $html;
    }


    /**
     * load all available sources and return all Sources suitable
     * for navigation panel
     * html
     *
     * @return htmltext
     */
    public function sourcesListAsString() {
        $sourcesDao = new \daos\Sources();
        $sources = $sourcesDao->getWithUnread();
        return $this->renderSources($sources);
    }


    /**
     * render spouts params
     * json
     *
     * @return void
     */
    public function write() {
        $this->needsLoggedIn();

        $sourcesDao = new \daos\Sources();

        // read data
        parse_str(\F3::get('BODY'),$data);

        if(!isset($data['title']))
            $this->view->jsonError(array('title' => 'no data for title given'));
        if(!isset($data['spout']))
            $this->view->jsonError(array('spout' => 'no data for spout given'));

        // clean up title and tag data to prevent XSS
        $title = htmlspecialchars($data['title']);
        $tags = htmlspecialchars($data['tags']);
        $spout = $data['spout'];
        $filter = $data['filter'];
        $isAjax = isset($data['ajax']);

        unset($data['title']);
        unset($data['spout']);
        unset($data['filter']);
        unset($data['tags']);
        unset($data['ajax']);

        $spout = str_replace("_", "\\", $spout);

        // check if source already exists
        $id = \F3::get('PARAMS.id');
        $sourceExists = $sourcesDao->isValid('id', $id);

        // load password value if not changed for spouts containing passwords
        if ($sourceExists) {
            $spoutLoader = new \helpers\SpoutLoader();
            $spoutInstance = $spoutLoader->get($spout);

            foreach($spoutInstance->params as $spoutParamName => $spoutParam)
            {
                if ($spoutParam['type'] == 'password'
                    && empty($data[$spoutParamName])) {
                    if (!isset($oldSource)) {
                        $oldSource = $sourcesDao->get($id);
                        $oldParams = json_decode(html_entity_decode(
                                                   $oldSource['params']), true);
                    }
                    $data[$spoutParamName] = $oldParams[$spoutParamName];
                }
            }
        }

        $validation = $sourcesDao->validate($title, $spout, $data);
        if($validation!==true)
            $this->view->error( json_encode($validation) );

        // add/edit source
        if (!$sourceExists)
            $id = $sourcesDao->add($title, $tags, $filter, $spout, $data);
        else
            $sourcesDao->edit($id, $title, $tags, $filter, $spout, $data);

        // autocolor tags
        $tagsDao = new \daos\Tags();
        $tags = explode(",",$tags);
        foreach($tags as $tag)
            $tagsDao->autocolorTag(trim($tag));

        // cleanup tags
        $tagsDao->cleanup($sourcesDao->getAllTags());

        $return = array(
            'success' => true,
            'id'      => $id
        );

        // only for shomyo ui (update stats in navigation)
        if($isAjax) {
            // get new tag list with updated count values
            $tagController = new \controllers\Tags();
            $return['tags'] = $tagController->tagsListAsString();

            // get new sources list
            $sourcesController = new \controllers\Sources();
            $return['sources'] = $sourcesController->sourcesListAsString();
        }

        $this->view->jsonSuccess($return);
    }


    /**
     * return source stats in HTML for nav update
     * json
     *
     * @return void
     */
    public function sourcesStats() {
//      $this->needsLoggedIn();
        $this->needsLoggedInOrPublicMode();

        $this->view->jsonSuccess(array(
            'success' => true,
            'sources' => $this->sourcesListAsString()
        ));
    }


    /**
     * delete source
     * json
     *
     * @return void
     */
    public function remove() {
        $this->needsLoggedIn();

        $id = \F3::get('PARAMS.id');

        $sourceDao = new \daos\Sources();

        if (!$sourceDao->isValid('id', $id))
            $this->view->error('invalid id given');

        $sourceDao->delete($id);

        // cleanup tags
        $tagsDao = new \daos\Tags();
        $allTags = $sourceDao->getAllTags();
        $tagsDao->cleanup($allTags);

        $this->view->jsonSuccess(array(
            'success' => true
        ));
    }


    /**
     * returns all available sources
     * json
     *
     * @return void
     */
    public function listSources() {
        $this->needsLoggedIn();

        // load sources
        $sourcesDao = new \daos\Sources();
        $sources = $sourcesDao->getWithIcon();

        // get last icon
        for($i=0; $i<count($sources); $i++) {
            $sources[$i]['params'] = json_decode(html_entity_decode($sources[$i]['params']), true);
            $sources[$i]['error'] = $sources[$i]['error']==null ? '' : $sources[$i]['error'];
            unset($sources[$i]['spout_obj']);
        }

        $this->view->jsonSuccess($sources);
    }


    /**
     * returns all available spouts
     * json
     *
     * @return void
     */
    public function spouts() {
        $this->needsLoggedIn();

        $spoutLoader = new \helpers\SpoutLoader();
        $spouts = $spoutLoader->all();
        $this->view->jsonSuccess($spouts);
    }


    /**
     * returns all sources with unread items
     * json
     *
     * @return void
     */
    public function stats() {
        $this->needsLoggedInOrPublicMode();

//      $itemDao = new \daos\Items();

        // load sources
        $sourcesDao = new \daos\Sources();
        $sources = $sourcesDao->getWithUnread();

        $this->view->jsonSuccess($sources);
    }
}
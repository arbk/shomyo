<?PHP
namespace controllers;

/**
 * Controller for item export
 *
 * @package    controllers
 * @copyright  Copyright (c) arbk (https://aruo.net/)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     arbk (https://aruo.net/)
 */
class Export extends BaseController {

    /**
     * item export
     *
     * 'tmpl' option is ID to specify custom export template.
     * [a-z], [A-Z], '-', '_' can be used for this ID.
     * custom export template path is 'data/export/custom/%ID%.tpl.php'
     *
     * @return void
     */
    public function export() {
        \F3::get('logger')->log('start item export', \TRACE);

        $this->needsLoggedIn();

        $options = array();
        if(count($_GET)>0){ $options = $_GET; }

        $tmpl = 'data/export/default.tpl.php';
        if( isset($options['tmpl']) && strlen($options['tmpl'])>0 ){
            $custom = 'data/export/custom/'.$options['tmpl'].'.tpl.php';
            if( 1===preg_match('/^[a-zA-Z0-9_\-]+$/', $options['tmpl'])
                && file_exists($custom) ){ $tmpl = $custom; }
            else{ \F3::error(404); }
        }
        \F3::get('logger')->log('tmpl: "'.$tmpl.'"', \DEBUG);

        $itemDao = new \daos\Items();
        $this->view->items = $itemDao->get($options);
        $this->view->options = $options;
        echo $this->view->render($tmpl);

        \F3::get('logger')->log('finished item export', \TRACE);
    }
}
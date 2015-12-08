<?PHP
namespace controllers;

/**
 * Controller for development tools
 *
 * @package    controllers
 * @copyright  Copyright (c) arbk (http://aruo.net/)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     arbk (http://aruo.net/)
 */
class Develop extends BaseController {

    /**
     * view enviroment information
     *
     * @return void
     */
    public function info() {
        \F3::get('logger')->log('start view env info', \DEBUG);

        $this->needsLoggedIn();

        phpinfo();

        \F3::get('logger')->log('finished view env info', \DEBUG);
    }
}
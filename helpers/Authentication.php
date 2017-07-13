<?PHP

namespace helpers;

/**
 * Helper class for authenticate user
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     arbk (https://aruo.net/)
 */
class Authentication {

    /**
     * loggedin
     * @var bool
     */
    private $loggedin = false;


    /**
     * start session and check login
     */
    public function __construct() {
        if ($this->enabled()===false)
            return;

        // session cookie will be valid for one month.
        $cookie_expire = 3600*24*30;
        $cookie_secure = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=="off";
        $cookie_httponly = true;

        // check for SSL proxy and special cookie options
        if(isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && isset($_SERVER['HTTP_X_FORWARDED_HOST'])
           && ($_SERVER['HTTP_X_FORWARDED_SERVER']===$_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $cookie_path = '/'.$_SERVER['SERVER_NAME'].preg_replace('/\/[^\/]+$/','',$_SERVER['PHP_SELF']).'/';
            $cookie_domain = $_SERVER['HTTP_X_FORWARDED_SERVER'];
        } else {
            // cookie path is script dir.
            $cookie_path = dirname($_SERVER['SCRIPT_NAME'])==='/'?'/':dirname($_SERVER['SCRIPT_NAME']).'/';
            $cookie_domain = $_SERVER['SERVER_NAME'];
        }
        session_set_cookie_params($cookie_expire, $cookie_path, $cookie_domain,
                                  $cookie_secure, $cookie_httponly);
        \F3::get('logger')->log("set cookie on $cookie_domain$cookie_path expiring in $cookie_expire seconds", \DEBUG);

//        session_name();
        if(session_id()==""){
            $save_path = "/tmp/shomyo";
            if(!file_exists($save_path)){ mkdir($save_path, 0705); }
            session_save_path($save_path);
            ini_set('session.gc_maxlifetime', $cookie_expire);
            session_start();
            \F3::get('logger')->log("start session. [".session_name().":".session_id().", save_path:".session_save_path().", gc_maxlifetime:".ini_get('session.gc_maxlifetime')."]", \DEBUG);
        }

        if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']===true){
            $this->loggedin = true;
            \F3::get('logger')->log('logged in using valid session', \DEBUG);
        } else {
            \F3::get('logger')->log('session does not contain valid auth', \DEBUG);
        }

        // autologin if request contains unsername and password
        if($this->loggedin===false
            && isset($_REQUEST['username'])
            && isset($_REQUEST['password'])) {
            $this->login($_REQUEST['username'], $_REQUEST['password']);
        }
    }


    /**
     * login enabled
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function enabled() {
        return strlen(trim(\F3::get('username')))!=0 && strlen(trim(\F3::get('password')))!=0;
    }


    /**
     * login user
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function loginWithoutUser() {
        $this->loggedin = true;
    }


    /**
     * login user
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function login($username, $password) {
        if($this->enabled()) {
            if( \F3::get('login_invalidate')==1 ){ return false; }

            if(
                $username == \F3::get('username') &&  hash("sha512", \F3::get('salt') . $password) == \F3::get('password')
            ) {
                session_regenerate_id(true);
                $this->loggedin = true;
                $_SESSION['loggedin'] = true;
                \F3::get('logger')->log('logged in with supplied username and password', \DEBUG);
                return true;
            } else {
                \F3::get('logger')->log('failed to log in with supplied username and password', \DEBUG);
                return false;
            }
        }
        return true;
    }


    /**
     * isloggedin
     *
     * @return bool
     */
    public function isLoggedin() {
        if($this->enabled()===false)
            return true;
        return $this->loggedin;
    }


    /**
     * showPrivateTags
     *
     * @return bool
     */
    public function showPrivateTags() {
       return $this->isLoggedin();
     }

    
    /**
     * logout
     *
     * @return void
     */
    public function logout() {
        $this->loggedin = false;
        @session_start();
        $_SESSION = array();
        session_destroy();
        \F3::get('logger')->log('logged out and destroyed session', \DEBUG);
    }
}
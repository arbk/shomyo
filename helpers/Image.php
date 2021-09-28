<?PHP

namespace helpers;

use WideImage\WideImage;

/**
 * Helper class for loading images
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     arbk (https://aruo.net/)
 */
class Image {
    
    /**
     * url of last fetched favicon
     * @var string
     */
    private $faviconUrl = false;
    
    
    /**
     * fetch favicon
     *
     * @return bool
     * @param string $url source url
     */
    public function fetchFavicon($url, $isHtmlUrl=false, $width=false, $height=false) {
        // try given url
        if(false===$isHtmlUrl) {
            $faviconAsPng = $this->loadImage($url, 'png', $width, $height);
            if($faviconAsPng!==false) {
                $this->faviconUrl = $url;
                \F3::get('logger')->log('icon: faviconUrl: '.$this->faviconUrl, \DEBUG);
                return $faviconAsPng;
            }
        }

        $urlElements = parse_url($url);

        // search on base page for <link rel="shortcut icon" url...
        $html = null;
        try {
            $html = \helpers\WebClient::request($url);
        }catch( \exception $e ) {
            \F3::get('logger')->log('icon: failed to get html page: '.$url, \WARNING);
            \F3::get('logger')->log('icon: response: '.$e->getMessage(), \DEBUG);
        }

        $shortcutIcon = $this->parseShortcutIcon($html);
        if($shortcutIcon!==false) {
            if(substr($shortcutIcon,0,4)!='http') {
                if (substr($shortcutIcon, 0, 2)=='//')
                    $shortcutIcon = $urlElements['scheme'] . ':' . $shortcutIcon;
                elseif (substr($shortcutIcon, 0, 1)=='/')
                    $shortcutIcon = $urlElements['scheme'] . '://' . $urlElements['host'] . $shortcutIcon;
                else
                    $shortcutIcon = (strrpos($url, '/')===strlen($url)-1) ? $url . $shortcutIcon : $url . '/' . $shortcutIcon;
            }

            $faviconAsPng = $this->loadImage($shortcutIcon, 'png', $width, $height);
            if($faviconAsPng!==false) {
                $this->faviconUrl = $shortcutIcon;
                \F3::get('logger')->log('icon: faviconUrl: '.$this->faviconUrl, \DEBUG);
                return $faviconAsPng;
            }
        }
        
        // search domain/favicon.ico
        if(isset($urlElements['scheme']) && isset($urlElements['host'])) {
            $url = $urlElements['scheme'] . '://' . $urlElements['host'] . '/favicon.ico';
            $faviconAsPng = $this->loadImage($url, 'png', $width, $height);
            if($faviconAsPng!==false) {
                $this->faviconUrl = $url;
                \F3::get('logger')->log('icon: faviconUrl: '.$this->faviconUrl, \DEBUG);
                return $faviconAsPng;
            }
        }
        
        \F3::get('logger')->log('icon: faviconUrl: (none)', \DEBUG);
        return false;
    }
    
    
    /**
     * load image
     *
     * @return bool
     * @param string $url source url
     * @param string $extension file extension of output file
     * @param int $width
     * @param int $height
     */
    public function loadImage($url, $extension='png', $width=false, $height=false) {
        // load image
        try{
            $data = \helpers\WebClient::request($url);
        }
        catch ( \exception $e ) {
            \F3::get('logger')->log('icon: failed to retrieve image: '.$url, \WARNING);
            \F3::get('logger')->log('icon: response: ' . $e->getMessage(), \DEBUG);
            return false;
        }

        // get image type
        $tmp = \F3::get('cache') . '/' . md5($url);
        file_put_contents($tmp, $data);
        $imgInfo = mime_content_type($tmp);
        $type = null;
        
        if(false!==$imgInfo){
          $imgInfo = strtolower($imgInfo);
          if($imgInfo=='image/png')
              $type = 'png';
          elseif($imgInfo=='image/jpeg')
              $type = 'jpg';
          elseif($imgInfo=='image/vnd.microsoft.icon' || $imgInfo=='image/x-icon' || $imgInfo=='image/icon')
              $type = 'ico';
          elseif($imgInfo=='image/gif')
              $type = 'gif';
          elseif($imgInfo=='image/x-ms-bmp')
              $type = 'bmp';
        }
        
        if(null===$type){
            \F3::get('logger')->log('icon: unknown image type: '.$imgInfo.', '.$url, \WARNING);
            \F3::get('logger')->log('icon: cache: '.$tmp, \DEBUG);
            unlink($tmp);
            return false;
        }
        
        // convert ico to png
        if('ico'===$type) {
            $ico = new \floIcon();
            $ico->readICO($tmp);
            if(count($ico->images)==0) {
                \F3::get('logger')->log('icon: failed to read image data from ico: '.$imgInfo.', '.$url, \WARNING);
                \F3::get('logger')->log('icon: cache: '.$tmp, \DEBUG);
                unlink($tmp);
                return false;
            }
            ob_start();
            imagepng($ico->images[count($ico->images)-1]->getImageResource());
            $data = ob_get_contents();
            ob_end_clean();
        }
        unlink($tmp);
        
        // parse image for saving it later
        try {
            $wideImage = WideImage::load($data);
        } catch(\Exception $e) {
            \F3::get('logger')->log('icon: failed to load image data: '.$imgInfo.', '.$url, \WARNING);
            \F3::get('logger')->log('icon: err: '.$e->getMessage(), \DEBUG);
            return false;
        }
        
        // resize
        if($width!==false && $height!==false) {
            if(($height!==null && $wideImage->getHeight()>$height) ||
               ($width!==null && $wideImage->getWidth()>$width))
                $wideImage = $wideImage->resize($width, $height);
        }
        
        // return image as jpg or png
        if($extension=='jpg') {
            $data = $wideImage->asString('jpg', 75);
        }
        else {
            $data = $wideImage->asString('png', 4, PNG_NO_FILTER);
        }
        
        return $data;
    }
    
    
    /**
     * get favicon url
     *
     * @return string
     */
    public function getFaviconUrl() {
        return $this->faviconUrl;
    }
    
    
    /**
     * parse shortcut icon from given html
     * 
     * @return string favicon url
     * @param string $html
     */
    private function parseShortcutIcon($html) {
        $result = preg_match('/<link [^>]*rel=("|\')apple-touch-icon-precomposed\1[^>]*>/iU', $html, $match1);
        if($result!==1)
            $result = preg_match('/<link [^>]*rel=("|\')apple-touch-icon\1[^>]*>/iU', $html, $match1);
        if($result!==1)
            $result = preg_match('/<link [^>]*rel=("|\')icon\1[^>]*>/iU', $html, $match1);
        if($result!==1)
            $result = preg_match('/<link [^>]*rel=("|\')shortcut icon\1[^>]*>/iU', $html, $match1);
        if($result===1) {
            $result = preg_match('/href=("|\')(.+)\1/iU', $match1[0], $match2);
            if($result===1)
                return $match2[2];
        }
        
        return false;
    }
}
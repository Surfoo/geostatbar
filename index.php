<?php
/**
* Custom Geocaching Stat bar
*
* @author Surfoo <surfooo@gmail.com>
* @link https://github.com/Surfoo/geostatbar
* @license http://opensource.org/licenses/GPL-2.0
*/

define('USERNAME', 'your_username');
define('PASSWORD', 'your_password');

define('LINE1', 'Team Surfoo');
define('LINE2', 'Trouvées : %s Cachées : %s');
define('LINE3', 'Tous au Geocaching !');

define('STATBAR_FILENAME', 'statbar.png');
define('DURATION', 3600 * 24 * 2); // 48 heures
define('FONT', 'myfreefont.ttf');

define('URL_GEOCACHING', 'https://www.geocaching.com/');
define('URL_LOGIN',      URL_GEOCACHING . 'login/default.aspx');
define('URL_QUICKVIEW',  URL_GEOCACHING . 'my/');


/**
 * remove_cookie
 * @return void
 */
function remove_cookie() {
    foreach(glob('cookie_*') as $file) {
       @unlink($file);
    }
}

/**
 * doLogin
 * @param  string $username
 * @param  string $password
 * @param  string $cookie_filename
 * @return boolean
 */
function doLogin($username, $password, $cookie_filename) {
    $postdata = array('__EVENTTARGET'      => '',
                      '__EVENTARGUMENT'    => '',
                      'ctl00$tbUsername'   => $username,
                      'ctl00$tbPassword'   => $password,
                      'ctl00$cbRememberMe' => 'On',
                      'ctl00$btnSignIn'    => 'Login');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, URL_LOGIN);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_filename);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));

    $res = curl_exec($ch);

    if (!$res) {
        echo 'Request error: ' . curl_error($ch);
        return false;
    }

    curl_close($ch);

    if (!preg_match('/ctl00_ContentBody_lbUsername">.*<strong>(.*)<\/strong>/', $res, $username)) {
        echo 'Your username/password combination does not match. Make sure you entered your information correctly.';
        return false;
    }

    return true;
}

/**
 * getStats
 * @param  string $cookie_filename
 * @return array
 */
function getStats($cookie_filename) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, URL_QUICKVIEW);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_filename);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $content = curl_exec($ch);
    if (!$content) {
        echo 'Request error: ' . curl_error($ch);
        return false;
    }
    curl_close($ch);

    if(!preg_match_all('/<span class="statcount">\s+?(.+)\s+?<\/span>/msU', $content, $matches)) {
        return false;
    }

    $data['finds'] = str_replace(',', '', $matches[1][0]);
    $data['hides'] = str_replace(',', '', $matches[1][1]);

    return $data;
}

//Create the stat bar
if (!file_exists(STATBAR_FILENAME) || filemtime(STATBAR_FILENAME) < time() - DURATION) {

    $cookie_filename = sprintf('cookie_%s', sha1(mt_rand()));

    if(doLogin(USERNAME, PASSWORD, $cookie_filename) && $datas = getStats($cookie_filename)) {
        $dest       = imagecreatefrompng('background-example.png');
        $text_color = imagecolorallocate($dest, 0, 0, 0);
        $background = imagecolorallocate($dest, 240, 240, 240);

        imagefttext($dest, 11, 0, 58, 16, $text_color, FONT, LINE1);
        imagefttext($dest,  9, 0, 58, 31, $text_color, FONT, sprintf(LINE2, $datas['finds'], $datas['hides']));
        imagefttext($dest, 11, 0, 58, 46, $text_color, FONT, LINE3);

        imagepng($dest, STATBAR_FILENAME);
        imagedestroy($dest);
    } else {
        remove_cookie();
        die('problem');
    }
}

remove_cookie();
header('Content-Type: image/png');
echo file_get_contents(STATBAR_FILENAME);

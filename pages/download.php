<?php

#require_once( '../../../core.php' );
#require_once( '../../../api/form_api.php' );
#require_once( '../core/releases_api.php' );
#require_once( '../core/constant_api.php' );

# Prevent output of HTML in the content if errors occur
//define('DISABLE_INLINE_ERROR_REPORTING', true);

#$t_current_user_id = auth_get_current_user_id();
#$t_project_id = helper_get_current_project();

#form_security_validate( 'plugin_Releases_download' );
#auth_reauthenticate();
#access_ensure_project_level( plugin_config_get( 'download_threshold_level', PLUGINS_RELEASES_DOWNLOAD_THRESHOLD_LEVEL_DEFAULT ), $t_project_id, $t_current_user_id );

$g_bypass_headers = true; # suppress headers as we will send our own later
define('COMPRESSION_DISABLED', true);

// This page is called from direct link 
// (i.e. not as a parameter of plugin.php
// As the result it does not have include path
// set approptiately and thus we use relative path
// to core.php. Once core is loaded everething works fine
require_once('../../../core.php');
require_api('access_api.php');
require_api('authentication_api.php');
require_api('bug_api.php');
require_api('config_api.php');
require_api('constant_inc.php');
require_api('database_api.php');
require_api('file_api.php');
require_api('gpc_api.php');
require_api('http_api.php');
require_api('utility_api.php');

plugin_push_current('Releases');

$t_id = gpc_get_int('id');
$c_file_id = db_prepare_int($t_id);

$t_file_table = plugin_table('file');

$query = "SELECT *
			  FROM $t_file_table
			  WHERE id=" . db_param();
$result = db_query_bound($query, array((int)$t_id));
$row = db_fetch_array($result);

//error_log("DBG: 1");
if (!$row){
    trigger_error(ERROR_FILE_NOT_FOUND, ERROR);
}
//error_log("DBG: 2");

extract($row, EXTR_PREFIX_ALL, 'v');

$require_login = (bool) plugin_config_get('download_requires_login', null, false, NO_USER, $v_project_id);

if ($require_login)
{
// To ensure that only logged user will be able to download file:
	$t_current_user_id = auth_get_current_user_id();
// To ensure that the user will be able to download file only if he/she has at least REPORTER rights to the project:
	access_ensure_project_level(REPORTER, $v_project_id, $t_current_user_id);
}
error_log('DBG: 1');

# throw away output buffer contents (and disable it) to protect download
while (@ob_end_clean());
error_log('DBG: ob_end_clean');

if (ini_get('zlib.output_compression') && function_exists('ini_set')) {
        ini_set('zlib.output_compression', false);
}
error_log('DBG: zlib done');

http_security_headers();
error_log('DBG: http_security_headers');

# Make sure that IE can download the attachments under https.
header('Pragma: public');

# To fix an IE bug which causes problems when downloading
# attached files via HTTPS, we disable the "Pragma: no-cache"
# command when IE is used over HTTPS.
global $g_allow_file_cache;
if ((isset($_SERVER["HTTPS"]) && ("on" == utf8_strtolower($_SERVER["HTTPS"]))) && is_browser_internet_explorer()) {
        # Suppress "Pragma: no-cache" header.
} else {
        if (!isset($g_allow_file_cache)) {
            header('Pragma: no-cache');
        }
}
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));

header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $v_date_added));

$t_filename = file_get_display_name($v_filename);
error_log('DBG: $t_filename' . $t_filename);

# For Internet Explorer 8 as per http://blogs.msdn.com/ie/archive/2008/07/02/ie8-security-part-v-comprehensive-protection.aspx
# Don't let IE second guess our content-type!
header('X-Content-Type-Options: nosniff');

//Would be better to use integrated function but it brakes filename of file
//http_content_disposition_header($t_filename);
$t_disposition = 'attachment;';
if (is_browser_internet_explorer() || is_browser_chrome()) {
        // Internet Explorer does not support RFC2231 however it does
        // incorrectly decode URL encoded filenames and we can use this to
        // get UTF8 filenames to work with the file download dialog. Chrome
        // behaves in the same was as Internet Explorer in this respect.
        // See http://greenbytes.de/tech/tc2231/#attwithfnrawpctenclong
        header('Content-Disposition:' . $t_disposition . ' filename="' . $t_filename . '"');
} else {
        // For most other browsers, we can use this technique:
        // http://greenbytes.de/tech/tc2231/#attfnboth2
        header('Content-Disposition:' . $t_disposition . ' filename*=UTF-8\'\'' . rawurlencode ($t_filename) . '; filename="' . $t_filename . '"');
}

header('Content-Length: ' . $v_filesize);

header('Content-Type: ' . $v_file_type);

error_log("DBG: just before download");

switch (plugin_config_get('upload_method', PLUGINS_RELEASES_UPLOAD_METHOD_DEFAULT)) {
  case DISK:
    if (file_exists($v_diskfile)) {
        readfile($v_diskfile);
    }
    break;
    /*
  case FTP:
    if (file_exists($v_diskfile)) {
        readfile($v_diskfile);
    } else {
        $ftp = plugins_releases_file_ftp_connect();
        file_ftp_get ($ftp, $v_diskfile, $v_diskfile);
        file_ftp_disconnect($ftp);
        readfile($v_diskfile);
    }
    break;
    */
  default:
    echo $v_content;
}

#form_security_purge( 'plugin_Releases_download' );

?>


<?php
// No php close tag to avoid any extra output
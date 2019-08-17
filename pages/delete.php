<?php

require_once('core.php');
require_once('bug_api.php');
require_once('releases_api.php');

$t_release = gpc_get_bool('release', false);
$t_id = gpc_get_int('id');

$t_current_user_id = auth_get_current_user_id();
$t_project_id = plugins_releases_file_get_field($t_id, 'project_id');

form_security_validate( 'plugin_Releases_delete' );
auth_reauthenticate();
access_ensure_project_level( plugin_config_get( 'upload_threshold_level', PLUGINS_RELEASES_UPLOAD_THRESHOLD_LEVEL_DEFAULT ), $t_project_id, $t_current_user_id );

if (!$t_release) {
    log_event(  LOG_PLUGIN, "Releases: Delete release file id '%d'", $t_id );
    plugins_releases_file_delete($t_id);  // t_id is a file id
}
else {
    log_event(  LOG_PLUGIN, "Releases: Delete release release id '%d'", $t_id );
    plugins_releases_file_delete_all($t_id); // t_id is a release id
    $query = "DELETE FROM " . plugin_table('release') . " WHERE id=" . $t_id;
    $result = db_query($query);
}

form_security_purge( 'plugin_Releases_delete' );

$t_redirect_url = plugin_page( 'releases', TRUE );
layout_page_header( null, $t_redirect_url );
layout_page_begin();
html_operation_successful( $t_redirect_url );
layout_page_end();

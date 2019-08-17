<?php

require_once( 'core.php' );
require_once( 'bug_api.php' );
require_once( 'releases_api.php' );

$t_current_user_id = auth_get_current_user_id();
$t_project_id = helper_get_current_project();

form_security_validate( 'plugin_Releases_enable' );
auth_reauthenticate();
access_ensure_project_level( plugin_config_get( 'upload_threshold_level', PLUGINS_RELEASES_UPLOAD_THRESHOLD_LEVEL_DEFAULT ), $t_project_id, $t_current_user_id );

$t_id = gpc_get_int( 'id' );
$t_enable = gpc_get_int( 'enbl' );

$t_current_user_id = auth_get_current_user_id();
$t_project_id = plugins_releases_file_get_field($t_id, 'project_id');

plugins_releases_file_enable( $t_id, $t_enable>0 );

form_security_purge( 'plugin_Releases_enable' );

$t_redirect_url = plugin_page( 'releases', TRUE );
layout_page_header( null, $t_redirect_url );
layout_page_begin();
html_operation_successful( $t_redirect_url );
layout_page_end();

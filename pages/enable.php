<?php

require_once( 'core.php' );
require_once( 'bug_api.php' );
require_once( 'releases_api.php' );

$t_id = gpc_get_int( 'id' );
$t_enable = gpc_get_int( 'enbl' );

$t_current_user_id = auth_get_current_user_id();
$t_project_id = plugins_releases_file_get_field($t_id, 'project_id');

// To ensure that the user will be able to download file only if he/she has at least the configured access level to the project:
access_ensure_project_level( plugin_config_get( 'upload_threshold_level', PLUGINS_RELEASES_UPLOAD_THRESHOLD_LEVEL_DEFAULT ), $t_project_id, $t_current_user_id );

plugins_releases_file_enable( $t_id, $t_enable>0 );

release_mgt_successful_redirect( 'releases' );

<?php

require_once( 'core.php' );
require_once( 'bug_api.php' );
require_once( 'releases_api.php' );
require_once( 'releases_email_api.php' );

$t_file_count = gpc_get_int( 'file_count' );
$t_file = array(  );
$t_description = array(  );
for( $i=0; $i<$t_file_count; $i++ ) {
    $t_file[$i] = gpc_get_file( 'file_' . $i );
    $t_description[$i] = gpc_get_string( 'description_' . $i, '' );
}
$t_version = gpc_get_string( 'version', '' );
$t_version_id = gpc_get_int( 'release', 0 );
$t_notes = gpc_get_string( 'description', '' );

$t_current_user_id = auth_get_current_user_id();
$t_current_user = user_get_username($t_current_user_id);
$t_project_id = helper_get_current_project();

form_security_validate( 'plugin_Releases_upload' );
access_ensure_project_level( plugin_config_get( 'upload_threshold_level', PLUGINS_RELEASES_UPLOAD_THRESHOLD_LEVEL_DEFAULT ), $t_project_id, $t_current_user_id );

log_event(  LOG_PLUGIN, "Releases: Upload/update release '%s'", $t_version  );

#
# Get the database table for 'release'
#
$dbTable = plugin_table( 'release' );

#
# Check to make sure a release for this version does not already exist
# If it doesnt, then create it, if it does, get the release id, we will still add assets
# to the existing release next
#
$query = "SELECT COUNT(*) FROM $dbTable WHERE version_id=".$t_version_id;
$result = db_query( $query );
$rowCount = db_result( $result );

#
# Create the release in database
#
if ( $rowCount < 1 )
{
    $t_date_fmt = date( "Y-m-d H:i:s" );
    $query = "INSERT INTO $dbTable ( project_id, version_id, title, description, date_created, user ) VALUES ( ?,?,'',?,?,? )";
    db_query( $query, array( $t_project_id, $t_version_id, $t_notes, $t_date_fmt, $t_current_user ) );
    $release_id = db_insert_id( $dbTable );
}
elseif ( !empty( $t_notes ) )
{
    $query = "UPDATE $dbTable SET description=? WHERE version_id=?";
    db_query( $query, array( $t_notes, $t_version_id ) );
}
    
for( $i=0; $i < $t_file_count; $i++ ) 
{
    log_event(  LOG_PLUGIN, "Releases: Processing upload file %d", $i  );

    if ( isset( $t_file[$i]['error'] ) && $t_file[$i]['error'] != "0" ) {
        log_event(  LOG_PLUGIN, "Releases: Error %s", $t_file[$i]['error']  );
    }
    else if ( isset( $t_file[$i] ) && isset( $t_file[$i]['tmp_name'] ) && ( !isset( $t_file[$i]['error'] ) || $t_file[$i]['error'] == "0" ) )
    {
        log_event(  LOG_PLUGIN, "Releases: File name %s", $t_file[$i]['name']  );
        $t_file_error[$i] = isset( $t_file[$i]['error'] ) ? $t_file[$i]['error'] : 0;
        $t_file_id[$i] = plugins_releases_file_add( $t_file[$i]['tmp_name'], $t_file[$i]['name'], $t_file[$i]['type'], $t_project_id, $t_version_id, 0, $t_description[$i], $t_file_error[$i] );
    }
}

if ( plugin_config_get( 'notification_enable', PLUGINS_RELEASES_NOTIFICATION_ENABLE_DEFAULT ) == ON ) {
    releases_plugin_send_email( $t_project_id, $t_version_id, $t_file, $t_description, $t_file_id );
}

form_security_purge( 'plugin_Releases_upload' );

$t_redirect_url = plugin_page( 'releases', TRUE );
layout_page_header( null, $t_redirect_url );
layout_page_begin();
html_operation_successful( $t_redirect_url );
layout_page_end();

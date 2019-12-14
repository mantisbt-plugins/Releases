<?php

require_once( __DIR__ . '/../../../../vendor/erusev/parsedown/Parsedown.php' );
require_once( 'bug_api.php' );
require_once( 'constant_api.php' );
require_once( 'releases_api.php' );
require_once( 'releases_email_api.php' );

$g_app->group( '/releases', function() use ( $g_app ) 
{
	$g_app->get( '/{project}/changelog/{version}', 'releases_get_changelog' );
	$g_app->get( '/{project}/changelog/{version}/', 'releases_get_changelog' );

	$g_app->get( '/{project}', 'releases_get' );
	$g_app->get( '/{project}/', 'releases_get' );

	$g_app->post( '/{project}', 'release_add' );
	$g_app->post( '/{project}/', 'release_add' );

	$g_app->put( '/{project}', 'release_update' );
	$g_app->put( '/{project}/', 'release_update' );

	$g_app->delete( '/{project}', 'release_delete' );
	$g_app->delete( '/{project}/', 'release_delete' );
});


function releases_get(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$t_project_id = null;
	#
	# Ensure valid project was provided by client
	#
	$p_project = isset( $p_args['project']) ? $p_args['project'] : $p_request->getParam( 'project' );
	if (is_blank( $p_project) ) {
		return $p_response->withStatus( HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project_id' is missing." );
	} 
	else {
		$t_project_id = project_get_id_by_name( $p_project, false );
		if ( $t_project_id == null) {
			return $p_response->withStatus( HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid." );
		}
	}

	$t_user_id = auth_get_current_user_id();
	$t_lang = mci_get_user_lang( $t_user_id);

	$t_project_ids = user_get_all_accessible_projects( $t_user_id, $t_project_id);
	$t_projects = array();

	foreach( $t_project_ids as $t_project_id) {
		$t_project = mci_project_get( $t_project_id, $t_lang, /* detail */ true);
		$t_subproject_ids = user_get_accessible_subprojects( $t_user_id, $t_project_id);
		if (!empty( $t_subproject_ids) ) {
			$t_subprojects = array();
			foreach( $t_subproject_ids as $t_subproject_id) {
				$t_subprojects[] = mci_project_as_array_by_id( $t_subproject_id);
			}

			$t_project['subProjects'] = $t_subprojects;
		}

		$t_projects[] = $t_project;
	}

	$t_result = array( 'projects' => $t_projects);

	return $p_response->withStatus(HTTP_STATUS_SUCCESS)->withJson( $t_result);
}


function releases_get_changelog(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$t_project_id = null;
	#
	# Ensure valid project was provided by client
	#
	$p_project = isset( $p_args['project']) ? $p_args['project'] : $p_request->getParam( 'project' );
	if (is_blank( $p_project) ) {
		return $p_response->withStatus( HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project_id' is missing." );
	} 
	else {
		$t_project_id = project_get_id_by_name( $p_project, false );
		if ( $t_project_id == null) {
			return $p_response->withStatus( HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid." );
		}
	}

	#
	# Get version provided by client
	#
	$p_version = isset( $p_args['version']) ? $p_args['version'] : $p_request->getParam( 'version' );
	if (is_blank( $p_version) ) {
		return $p_response->withStatus( HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project_id' is missing." );
	} 
	else {
		$t_version_id = version_get_id( $p_version, $t_project_id );
		if ( $t_version_id == null) {
			return $p_response->withStatus( HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid." );
		}
	}

	#
	# Get the database table for 'release'
	#
	$dbTable = plugin_table( 'release' );

	#
	# Check to make sure a release for this version does not already exist
	# If it doesnt, then create it, if it does, get the release id, we will still add assets
	# to the existing release next
	#
	$query = "SELECT description FROM $dbTable WHERE version_id=".$t_version_id;
    $result = db_query( $query);
	$t_changelog = db_result( $result );

	$t_result = array( 'changelog' => $t_changelog);

	return $p_response->withStatus( HTTP_STATUS_SUCCESS )->withJson( $t_result );
}


function getTempDir() 
{
	if (!function_exists( 'sys_get_temp_dir' ) )
	{
		function sys_get_temp_dir() 
		{
			if ( ( $tmp = getenv( 'TMPDIR' ) ) || ( $tmp = getenv( 'TMP' ) ) ||
				( $tmp = getenv( 'TEMP' ) ) || ( $tmp = ini_get( 'upload_tmp_dir' ) ))
				return $tmp;
			$tmp = tempnam(__FILE__, '' );
			if (file_exists( $tmp) ) {
				unlink( $tmp);
				return dirname( $tmp);
			}
			return null;
		}
	}
	return sys_get_temp_dir();
}


function get_mime_type( $filename) 
{
    $idx = explode( '.', $filename );
    $count_explode = count( $idx);
    $idx = strtolower( $idx[$count_explode-1]);

	$mimet = array( 
        'txt' => 'text/plain',
        'md' => 'text/markdown',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );

    if (isset( $mimet[$idx] ) ) {
     return $mimet[$idx];
    } else {
     return 'application/octet-stream';
    }
}


function release_add(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$rtnMessage = '';
	$t_project_id = null;

	log_event(LOG_PLUGIN, "Release add");

	#
	# Ensure valid project was provided by client
	#
	$p_project = isset( $p_args['project']) ? $p_args['project'] : $p_request->getParam( 'project' );
	if (is_blank( $p_project) ) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project' is missing." );
	} 
	else {
		$t_project_id = project_get_id_by_name( $p_project, false);
		if ( $t_project_id == null) {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid." );
		}
	}

	#
	# Get current user and check if upload access is granted
	#
	$t_current_user_id = auth_get_current_user_id();
	if ( !access_has_project_level( plugin_config_get( 'upload_threshold_level', PLUGINS_RELEASES_UPLOAD_THRESHOLD_LEVEL_DEFAULT), $t_project_id, $t_current_user_id ) ) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Denied, user does not have upload access" );
	}
	$t_current_user= user_get_username( $t_current_user_id);
	#
	# Parse payload
	#
	$p_payload = $p_request->getParsedBody();
	if ( $p_payload === null ) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Unable to parse body, specify content type" );
	}

	#
	# Dry run?
	#
	$p_dry_run = isset( $p_payload['dryrun'] ) && (bool)$p_payload['dryrun'];
	if ( !$p_dry_run ) {
		$rtnMessage = "Success:\n";
	}
	else {
		$rtnMessage = "Dry Run Results:\n";
	}

	#
	# Get version provided by client
	#
	if ( !isset( $p_payload['version'] ) ) {
		return $p_response->withStatus( HTTP_STATUS_BAD_REQUEST, "Mandatory field 'version' is missing in payload." );
	}
	$p_version = $p_payload['version'];

	#
	# Get tag provided by client
	#
	# $p_tag= "";
	# if ( isset( $p_payload['tag'] ) ) {
	# 	$p_tag= $p_payload['tag'];
	# }

	#
	# Get changelog/release description provided by client
	#
	if (!isset( $p_payload['notes']) ) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'notes' is missing in payload." );
	}
	$p_notes = $p_payload['notes'];

	#
	# Convert markdown changelog to HTML content for display, if specified by client
	#
	if ( isset( $p_payload['notesismd'] ) && (bool)$p_payload['notesismd'] ) {
		$Parsedown = new Parsedown();
		$p_notes = $Parsedown->text( $p_notes );
	}

	#
	# Convert ticket numbers to links
	# Must be in the form [fixes #24], [closes #3554,#3521], [references #436]
	#
	#$t_regex = "/\[(&nbsp;| )*(closes|fixes|resolves|fix|close|refs|references|ref|reference){1}(&nbsp;| )*#[0-9]+( (&nbsp;| )*,(&nbsp;| )*#[0-9]+){0,}(&nbsp;| )*\]/i";
	#preg_match_all( $t_regex, $p_notes, $t_matches_all);
	#foreach ( $t_matches_all[0] as $t_substring) 
	#{
	#	$value = $t_substring;
	#	$vi1 = strpos( $value, "#" );
	#	$vi2 = strpos( $value, "]" );
	#	$bugid = substr( $value, $vi1, $vi2 - $vi1);
	#	$bugids = explode( ",", $bugid);
	#	for ( $i = 0; $i < count( $bugids); $i++) {
	#		$bid = str_replace( "&nbsp;", "", $bugids[$i]); # with # i.e. #1919
	#		$bid_num = str_replace( "#", "", $bid); # w/o # i.e. 1919
	#		$value = str_replace( $bid, "<a href=\"view.php?id=$bid_num\">$bid</a>", $value);
	#	}
	#	# color resolved tags gray, create links to tickets
	#	$p_notes = str_replace( $t_substring, "<font style=\"color:gray\">$value</font>", $p_notes);
	#}

	#
	# Convert ticket numbers to links
	#
	$t_regex = "/(?:bugs?|issues?|refs?|references?|reports|fixe?d?s?|closed?s?|resolved?s?)+\s*:?\s+(?:#(?:\d+)[,\.\s]*)+/i";
	preg_match_all( $t_regex, $p_notes, $t_matches_all );
	foreach( $t_matches_all[0] as $t_substring ) 
	{
		$value = $t_substring;
		$vi1 = strpos( $value, "#" );
		$bugid = substr( $value, $vi1, strlen( $value ) - $vi1 );
		$bugids = explode( ",", $bugid );
		for ( $i = 0; $i < count( $bugids ); $i++) {
			$bid = str_replace( "&nbsp;", "", $bugids[$i] ); # with # i.e. #1919
			$bid_num = str_replace( "#", "", $bid ); # w/o # i.e. 1919
			$value = str_replace( $bid, "<a href=\"view.php?id=$bid_num\">$bid</a>", $value );
		}
		# color resolved tags gray, create links to tickets
		$p_notes = str_replace( $t_substring, "<font style=\"color:gray\">$value</font>", $p_notes );
	}

	#
	# Check version and get version id
	#
	# Create version if it doesnt exist, update 'released' to true if it does
	#
	$t_version_id = null;
	if ( version_get_id( $p_version, $t_project_id ) === false ) 
	{
		if ( !$p_dry_run ) {
			$t_version_id = version_add( $t_project_id, $p_version, VERSION_RELEASED );
		}
		$rtnMessage = $rtnMessage . "Version added, $p_version to 'released'\n";
	}
	else 
	{
		$t_version_id = version_get_id( $p_version, $t_project_id );
		$t_version_info = version_get( $t_version_id );
		$t_version_info->released = VERSION_RELEASED;
		$t_version_info->date_order = db_now();
		if ( !$p_dry_run ) {
			version_update( $t_version_info );
		}
		$rtnMessage = $rtnMessage . "Version $p_version updated to 'released'\n";
	}
	if ( $t_version_id === null) {
		return $p_response->withStatus(HTTP_STATUS_PRECONDITION_FAILED, "Could not create or open version" );
	}

	#
	# Clear cache since version_api doesnt do it (as of 2.21.1)
	#
	releases_clear_version_cache();

	#
	# Create next patch, minor, and major versions if they do not exist
	#
	if ( plugin_config_get( 'create_next_versions', 0, false, null, $t_project_id ) == 1 ) 
	{
		releases_create_next_versions( $p_version, $t_project_id, $p_dry_run, $rtnMessage );
	}

	#
	# Move unresolved issues with target version = to this released version to next minor version
	#
	if ( plugin_config_get( 'update_unresolved_issues_tgt', 0, false, null, $t_project_id ) == 1 ) 
	{
		releases_update_unresolved_issues_tgt( $p_version, $t_project_id, $p_dry_run, $rtnMessage );
	}

	#
	# Remove past unreleased versions based on version comparison
	#
	if ( plugin_config_get( 'remove_past_unreleased_versions', 0, false, null, $t_project_id ) == 1 ) 
	{
		releases_remove_past_unreleased_versions( $p_version, $t_project_id, $p_dry_run, $rtnMessage );
	}

	#
	# Go through and re-order versions and reset date timestamps
	#
	if ( plugin_config_get( 'sort_unreleased_versions', 0, false, null, $t_project_id ) == 1 ) 
	{
		releases_sort_unreleased_versions( $p_version, $t_project_id, $p_dry_run, $rtnMessage );
	}

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
    $result = db_query( $query);
	$rowCount = db_result( $result);
	
	$t_version_name = 'Version ' . $p_version;
	$t_date_fmt = date( "Y-m-d H:i:s" );

	#
	# Create the release in database
	#
	if ( $rowCount < 1)
	{
		$query = "INSERT INTO $dbTable (project_id, version_id, title, description, date_created, user) VALUES (?, ?, ?, ?, ?, ?)";
		if (!$p_dry_run) {
			db_query( $query, array( $t_project_id, $t_version_id, $t_version_name, $p_notes, $t_date_fmt, $t_current_user) );
			$release_id = db_insert_id( $dbTable);
		}
		else {
			$release_id = 0;
		}
		$query = "INSERT INTO $dbTable (project_id, version_id, title, description, date_created, user) VALUES ".
				 "( " . $t_project_id . ", " . $t_version_id . ", '" . $t_version_name . "', ?, '" . $t_date_fmt . "', '" . $t_current_user. "')";
		$rtnMessage = $rtnMessage . "SQL: " . $query . "\n";
		log_event(LOG_PLUGIN, "SQL: " . $query);
	}
	elseif (!empty( $p_notes ) )
	{
		$query = "SELECT id FROM $dbTable WHERE version_id=" . $t_version_id . " ORDER BY id DESC";
		if ( $p_dry_run) {
			$release_id = 0;
		}
		else {
			$result = db_query( $query);
			$release_id = db_result( $result);
		}
		$rtnMessage = $rtnMessage . "SQL: " . $query . "\n";
		log_event(LOG_PLUGIN, "SQL: " . $query);
		#
		# Update description, date_modified
		#
		$query = "UPDATE $dbTable SET description=? WHERE id=" . $release_id;
		if ( !$p_dry_run) {
			db_query( $query, array( $p_notes) );
		}
		$rtnMessage = $rtnMessage . "SQL: " . $query . "\n";
		log_event(LOG_PLUGIN, "SQL: " . $query);
	}

	#
	# Process files/assets
	#
	if (isset( $p_payload['assets']) && !empty( $p_payload['assets']) ) 
	{
		$fileCount = 0;

		$assets = $p_payload['assets'];
		foreach ( $assets as $asset)
		{
			$fileCount++;
			#
			# Check file mime type, set if not provided by client
			#
			if (!isset( $asset['type']) ) 
			{
				if (strpos( $asset['name'], "." ) == FALSE) {
					return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Field 'asset.type' is required for extensionless filenames." );
				}
				$asset['type'] = get_mime_type( $asset['name']);
			}
			if (strpos( $asset['type'], "/" ) == FALSE) {
				return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Field 'asset.type' is invalid for '".$asset['name']."'" );
			}
			#
			# Write data to temp file for a call to plugins_releases_file_add()
			# File data received from the client should be Base64 encoded
			#
			$fileName = getTempDir().'/'.$asset['name'];
			$fileData = base64_decode( $asset['data']);
			file_put_contents( $fileName, $fileData);

			#
			# Add asset to database
			# Code 1001 forces use of rename() instead of move_uploaded_file()
			#
			if (!$p_dry_run) {
				plugins_releases_file_add( $fileName, $asset['name'], $asset['type'], $t_project_id, $t_version_id, $release_id, $asset['desc'], 1001);
			}
			$rtnMessage = $rtnMessage . "Add asset " . $asset['name'] . " with mime type " . $asset['type'] . "\n";
			log_event(LOG_PLUGIN, "Add asset " . $asset['name'] . " with mime type " . $asset['type']);
		}

		#
		# TODO - Send email notification if required
		#
		#if (!$p_dry_run && $fileCount > 0 && plugin_config_get( 'notification_enable', PLUGINS_RELEASES_NOTIFICATION_ENABLE_DEFAULT) == ON) {
			# following fn needs to be re-worked as the orig function sent email "per" asset uploaded in the html form (huh?)
			# releases_plugin_send_email( $t_project_id, $p_version, $t_file, $cptDesc, $t_file_id );
		#}
	}

	$response = array(
		'id' => $release_id,
		'msg' => $rtnMessage
	);

	return $p_response->withStatus(HTTP_STATUS_CREATED, "Release created with id $release_id" )->withJson( $response);
}


function releases_clear_version_cache()
{
	global $g_cache_versions, $g_cache_versions_project;
	$g_cache_versions = array();
	$g_cache_versions_project  = array();
	#if ( isset( $g_cache_versions[$t_v_id] ) ) {
	#	unset( $g_cache_versions[$t_v_id] );
	#}
	#if ( isset( $g_cache_versions_project[$p_project_id] ) ) { # clear cache since version_api doesnt do it
	#	unset( $g_cache_versions_project[$p_project_id] );
	#}
}


function releases_get_last_released_version( $p_project_id )
{
	$t_version = null;
	$t_versions = version_get_all_rows( $p_project_id, VERSION_RELEASED );
	foreach ( $t_versions as $v ) 
	{
		if ( $t_version != null && strpos( $t_version, '.' ) != false && version_compare( $v['version'], $t_version ) > 0 ) {
			$t_version = $v['version'];
		}
		else if ( $t_version != null && strpos( $t_version, '.' ) == false && strcmp( $v['version'], $t_version ) > 0 ) {
			$t_version = $v['version'];
		}
		else if ( $t_version == null ) {
			$t_version = $v['version'];
		}
	}
	return $t_version;
}


function releases_get_new_target_version( $p_version, $p_project_id, &$rtnMessage = null )
{
	#
	# Break down the version so we can increment it as needed.  This will depend if the version
	# string is 'semantic' maj.min.patch or 'incremental' 100, 101, 102, etc
	#
	$t_version = $p_version;
	$t_version_parts = explode( ".", $t_version ); // array( "maj", "min", "patch" )
	if ( strpos( $t_version, '.' ) !== false )
	{
		$t_version_parts[2] = 0; // next minor
		++$t_version_parts[1];
		$t_version = implode( ".", $t_version_parts ); // implode array back to string
		$t_next_version_id = version_get_id( $t_version, $p_project_id );

		if ( $t_next_version_id === false ) // next patch
		{
			$t_version_parts = explode( ".", $t_version ); // array( "maj", "min", "patch" )
			++$t_version_parts[2];
			$t_version = implode( ".", $t_version_parts ); // implode array back to string
			$t_next_version_id = version_get_id( $t_version, $p_project_id );

			if ( $t_next_version_id === false ) // next major
			{
				$t_version_parts = explode( ".", $t_version ); // array( "maj", "min", "patch" )
				$t_version_parts[2] = 0;
				$t_version_parts[1] = 0;
				$t_version_parts[0]++;
				$t_version = implode( ".", $t_version_parts ); // implode array back to string
			}
		}
	}
	else
	{
		++$t_version_parts[0];
		$t_version = $t_version_parts[0].""; // back to string
	}

	if ( version_get_id( $t_version, $p_project_id ) === false ) # make sure version exists
	{
		log_event(LOG_PLUGIN, "Calculated next tgt version $t_version not found, return $p_version");
		if ( $rtnMessage !== null ) {
			$rtnMessage = $rtnMessage . "Calculated next tgt version $t_version not found, return $p_version\n";
		}
		return $p_version;
	}

	return $t_version;
}


function releases_create_next_versions( $p_version, $p_project_id, $p_dry_run = false, &$rtnMessage = null )
{
	$t_changed = false;
	$t_version = $p_version;
	$t_version_parts = explode( ".", $t_version ); // array( "maj", "min", "patch" )
		
	if ( strpos( $t_version, '.' ) !== false )
	{
		#
		# Create the next patch version if it doesnt exist
		#
		++$t_version_parts[2];
		$t_version = implode( ".", $t_version_parts ); // implode array back to string
		if ( version_get_id( $t_version, $p_project_id ) === false ) 
		{
			if ( !$p_dry_run ) {
				version_add( $p_project_id, $t_version, VERSION_FUTURE, '', db_now() + (3600 * 24 * 7) );
				$t_changed = true;
			}
			if ( $rtnMessage !== null ) {
				$rtnMessage = $rtnMessage . "Add next patch version $t_version\n";
			}
			log_event(LOG_PLUGIN, "Added next patch version $t_version");
		}
		#
		# Create the next minor version if it doesnt exist
		#
		$t_version_parts[2] = 0;
		++$t_version_parts[1];
		$t_version = implode( ".", $t_version_parts ); // implode array back to string
		if ( version_get_id( $t_version, $p_project_id ) === false ) 
		{
			if ( !$p_dry_run ) {
				version_add( $p_project_id, $t_version, VERSION_FUTURE, '', db_now() + (3600 * 24 * 30) );
				$t_changed = true;
			}
			if ( $rtnMessage !== null ) {
				$rtnMessage = $rtnMessage . "Add next minor version $t_version\n";
			}
			log_event(LOG_PLUGIN, "Added next minor version $t_version");
		}
		#
		# Create the next major version if it doesnt exist
		#
		$t_version_parts[2] = 0;
		$t_version_parts[1] = 0;
		++$t_version_parts[0];
		$t_version = implode( ".", $t_version_parts ); // implode array back to string
		if ( version_get_id( $t_version, $p_project_id ) === false ) 
		{
			if ( !$p_dry_run ) {
				version_add( $p_project_id, $t_version, VERSION_FUTURE, '', db_now() + (3600 * 24 * 90) );
				$t_changed = true;
			}
			if ( $rtnMessage !== null ) {
				$rtnMessage = $rtnMessage . "Add next major version $t_version\n";
			}
			log_event(LOG_PLUGIN, "Added next major version $t_version");
		}
	}
	else
	{
		# Create the next incremental version if it doesnt exist
		#
		++$t_version_parts[0];
		$t_version = $t_version_parts[0].""; // back to string
		if ( version_get_id( $t_version, $p_project_id ) === false ) 
		{
			if ( !$p_dry_run ) {
				version_add( $p_project_id, $t_version, VERSION_FUTURE, '', db_now() + (3600 * 24 * 90) );
				$t_changed = true;
			}
			if ( $rtnMessage !== null ) {
				$rtnMessage = $rtnMessage . "Add next version $t_version\n";
			}
			log_event(LOG_PLUGIN, "Added next version $t_version");
		}
	}

	#
	# Clear cache since version_api doesnt do it (as of 2.21.1), the added versions can not be accessed
	# since the version rows are chched and never cleared/adjusted
	#
	if ( $t_changed )
	{
		releases_clear_version_cache();
	}
}


function releases_remove_past_unreleased_versions( $p_version, $p_project_id, $p_dry_run = false, &$rtnMessage = null )
{
	$t_changed = false;

	$t_versions = version_get_all_rows( $p_project_id, VERSION_FUTURE );
	foreach ( $t_versions as $v ) 
	{
		$t_v_id = $v['id'];
		$t_v_name = $v['version'];
		$t_remove = false;

		if ( strpos( $p_version, '.' ) != false && version_compare( $t_v_name, $p_version ) < 0 ) 
		{
			$t_remove = true;
		}
		else if ( strpos( $p_version, '.' ) == false && strcmp( $t_v_name, $p_version ) < 0 ) 
		{
			$t_remove = true;
		}

		if ( $t_remove )
		{
			# Get all bugs for this project that have their target version set to the version we are 
			# about to remove.  Update these bugs to new tgt or 'fixed in' version depending on status.
			#
			$t_bug_page_number = 1;
			$t_bug_per_page = null;
			$t_bug_page_count = null;
			$t_bug_count = 0;
			$t_filter = filter_get_default();
			$t_filter[FILTER_PROPERTY_TARGET_VERSION] = array( '0' => $t_v_name );
			$t_filter = filter_ensure_valid_filter($t_filter);
			$t_new_target_version = releases_get_new_target_version( $p_version, $p_project_id, $rtnMessage );
			$t_bugs = filter_get_bug_rows( $t_bug_page_number, $t_bug_per_page, $t_bug_page_count, $t_bug_count, $t_filter, $p_project_id );
			if( $t_bugs !== false ) 
			{
				foreach ($t_bugs as $t_bug) 
				{
					if ( !$p_dry_run ) {
						bug_set_field( $t_bug->id, 'target_version', $t_bug->status < RESOLVED ? $t_new_target_version : $p_version );
					}
					if ( $rtnMessage !== null ) {
						$rtnMessage = $rtnMessage . "Reset bug #" . $t_bug->id . " target version from $t_v_name to " . ( $t_bug->status < RESOLVED ? $t_new_target_version : $p_version ) . "\n";
					}
					log_event(LOG_PLUGIN, "Reset bug #" . $t_bug->id . " target version from $t_v_name to " . ( $t_bug->status < RESOLVED ? $t_new_target_version : $p_version ) );
					
					if ( $t_bug->status >= RESOLVED )
					{
						if ( !$p_dry_run ) {
							bug_set_field( $t_bug->id, 'fixed_in_version', $p_version );
						}
						if ( $rtnMessage !== null ) {
							$rtnMessage = $rtnMessage . "Reset bug #" . $t_bug->id . " 'fixed in' version from $t_v_name to $p_version\n";
						}
						log_event(LOG_PLUGIN, "Reset bug #" . $t_bug->id . " 'fixed in' version from $t_v_name to $p_version");
					}
				}
			}

			#
			# Get all bugs for this project that have their 'fixed in' version set to the version we are 
			# about to remove.  Update these bugs to new tgt or 'fixed in' version depending on status.
			#
			$t_bug_page_number = 1;
			$t_bug_per_page = null;
			$t_bug_page_count = null;
			$t_bug_count = 0;
			$t_filter = filter_get_default();
			$t_filter[FILTER_PROPERTY_FIXED_IN_VERSION] = array( '0' => $t_v_name );
			$t_filter = filter_ensure_valid_filter($t_filter);
			$t_bugs = filter_get_bug_rows( $t_bug_page_number, $t_bug_per_page, $t_bug_page_count, $t_bug_count, $t_filter, $p_project_id );
			if( $t_bugs !== false ) 
			{
				foreach ($t_bugs as $t_bug) 
				{
					if ( !$p_dry_run ) {
						bug_set_field( $t_bug->id, 'fixed_in_version', $p_version );
					}
					if ( $rtnMessage !== null ) {
						$rtnMessage = $rtnMessage . "Reset bug #" . $t_bug->id . " 'fixed in' version from $t_v_name to $p_version\n";
					}
					log_event(LOG_PLUGIN, "Reset bug #" . $t_bug->id . " 'fixed in' version from $t_v_name to $p_version");
				}
			}

			#
			# Remove version
			#
			if ( !$p_dry_run ) 
			{
				version_remove( $t_v_id );
				$t_changed = true;
			}
			if ( $rtnMessage !== null ) {
				$rtnMessage = $rtnMessage . "Removed previous unreleased version $t_v_name\n";
			}
			log_event(LOG_PLUGIN, "Removed previous unreleased version $t_v_name");
		}
	}

	#
	# Clear cache since version_api doesnt do it (as of 2.21.1)
	#
	if ( $t_changed )
	{
		releases_clear_version_cache();
	}
}


function releases_sort_unreleased_versions( $p_version, $p_project_id, $p_dry_run = false, &$rtnMessage = null )
{
	$protect = $p_dry_run ? 1 : 10;
	while ( $protect > 0 && _releases_sort_unreleased_versions( $p_version, $p_project_id, $p_dry_run, $rtnMessage ) > 0 ) {
		$protect--;
	}
}


function _releases_sort_unreleased_versions( $p_version, $p_project_id, $p_dry_run = false, &$rtnMessage = null, &$ignore_versions = array() )
{
	$t_sorted = 0;
	$t_versions = version_get_all_rows( $p_project_id, VERSION_FUTURE );
	$t_order  = array_column($t_versions, 'date_order');
	array_multisort( $t_order, SORT_ASC, $t_versions );

	foreach ( $t_versions as $v ) 
	{
		if ( empty( $v['date_order'] ) ) {
			continue;
		}

		$t_v_id = $v['id'];
		$t_v_name = $v['version'];

		$t_versions2 = version_get_all_rows( $p_project_id, VERSION_FUTURE );
		//$t_order  = array_column($t_versions2, 'date_order');
		//array_multisort( $t_order, SORT_ASC, $t_versions2 );

		foreach ( $t_versions2 as $v2 ) 
		{
			$t_v_name2 = $v2['version'];

			if ( $t_v_name === $t_v_name2 ) {
				continue;
			}

			$t_update = false;

			if ( $v['date_order'] <= $v2['date_order'] )
			{
				if ( strpos( $p_version, '.' ) != false && version_compare( $v['version'], $v2['version'] ) > 0 ) 
				{
					$t_update = true;
				}
				else if ( strpos( $p_version, '.' ) == false && strcmp( $v['version'], $v2['version'] ) > 0 ) 
				{
					$t_update = true;
				}
			}

			if ( $t_update ) # bump v1 timestamp to be further into future than v2
			{
				++$t_sorted;
				$t_version_info = version_get( $t_v_id );
				$t_version_info->date_order = $v2['date_order'] + (3600 * 24 * 7 * $t_sorted);
				if ( !$p_dry_run ) {
					version_update( $t_version_info );
				}
				$fmt_date = date( "m.d.y.H:i:s", $t_version_info->date_order );
				if ( $rtnMessage !== null )
				{
					$rtnMessage = $rtnMessage . "Update version $t_v_name to new timestamp\n";
					$rtnMessage = $rtnMessage . "    Compared to: $t_v_name2\n";
					$rtnMessage = $rtnMessage . "    New timestamp: $fmt_date\n";
				}
				log_event(LOG_PLUGIN, "Update version $t_v_name to new timestamp");
			}
		}
	}

	#
	# Clear cache since version_api doesnt do it (as of 2.21.1)
	#
	if ( $t_sorted > 0 )
	{
		releases_clear_version_cache();
	}

	return $t_sorted;
}


function releases_update_unresolved_issues_tgt( $p_version, $p_project_id, $p_dry_run = false, &$rtnMessage = null )
{
	$t_version = releases_get_new_target_version( $p_version, $p_project_id, $rtnMessage );

	if ( $t_version != $p_version && version_get_id( $t_version, $p_project_id ) !== false ) 
	{
		# Get all unresolved bugs for this project that have theor target version set to the
		# version that is being released.  Update these bugs to new tgt version.
		#
		$t_bug_page_number = 1;
		$t_bug_per_page = null;
		$t_bug_page_count = null;
		$t_bug_count = 0;
		$t_filter = filter_get_default();
		$t_filter[FILTER_PROPERTY_TARGET_VERSION] = array( '0' => $p_version );
		$t_filter[FILTER_PROPERTY_HIDE_STATUS] = array( '0' => RESOLVED );
		$t_filter = filter_ensure_valid_filter($t_filter);
	
		$t_bugs = filter_get_bug_rows( $t_bug_page_number, $t_bug_per_page, $t_bug_page_count, $t_bug_count, $t_filter, $p_project_id );
		if( $t_bugs !== false ) 
		{
			foreach ($t_bugs as $t_bug) 
			{
				if ( !$p_dry_run ) {
					bug_set_field( $t_bug->id, 'target_version', $t_version );
				}
				if ( $rtnMessage !== null ) {
					$rtnMessage = $rtnMessage . "Reset bug #" . $t_bug->id . " target version from $p_version to $t_version\n";
				}
			}
		}
	}
}


function release_update(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	#
	# Ensure valid project was provided by client
	#
	$p_project = isset( $p_args['project']) ? $p_args['project'] : $p_request->getParam( 'project' );
	if (is_blank( $p_project) ) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project_id' is missing." );
	} 
	else {
		$t_project_id = project_get_id_by_name( $p_project, false);
		if ( $t_project_id == null) {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid." );
		}
	}

	$release_id = isset( $p_args['id']) ? $p_args['id'] : $p_request->getParam( 'id' );
	if (is_blank( $release_id) ) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'id' is missing." );
	}

	$release_id = (int)$release_id;
	if ( $release_id < 1) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Invalid release id." );
	}

	$p_payload = $p_request->getParsedBody();

	#
	# Dry run?
	#
	$p_dry_run = isset( $p_payload['dryrun']) && (bool)$p_payload['dryrun'];

	#if (isset( $p_payload['id']) && $p_payload['id'] != $t_project_id) {
	#	return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Project id mismatch" );
	#}

	#$t_user_id = auth_get_current_user_id();
	#$t_lang = mci_get_user_lang( $t_user_id);

	#$t_project = mci_project_get( $t_project_id, $t_lang, /* detail */ true);
	#$t_project = array_merge( $t_project, $p_payload);

	#$success = mc_project_update(/* username */ '', /* password */ '', $t_project_id, (object)$t_project);
	#ApiObjectFactory::throwIfFault( $success);

	return $p_response->withStatus(HTTP_STATUS_SUCCESS, "Release with id $release_id Updated" )
		->withJson(array( 'release' => $release_id) );
}


function release_delete(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	#
	# Ensure valid project was provided by client
	#
	$p_project = isset( $p_args['project']) ? $p_args['project'] : $p_request->getParam( 'project' );
	if (is_blank( $p_project) ) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project_id' is missing." );
	} 
	else {
		$t_project_id = project_get_id_by_name( $p_project, false);
		if ( $t_project_id == null) {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid." );
		}
	}

	$release_id = isset( $p_args['id']) ? $p_args['id'] : $p_request->getParam( 'id' );
	if (is_blank( $release_id) ) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'release_id' is missing." );
	}

	$release_id = (int)$release_id;
	if ( $release_id < 1) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Invalid release id." );
	}

	#$t_user_id = auth_get_current_user_id();
	#if (!project_exists( $t_project_id) || !access_has_project_level(config_get( 'delete_project_threshold', null, $t_user_id, $t_project_id), $t_project_id) ) {
	#	return $p_response->withStatus(HTTP_STATUS_FORBIDDEN, "Access denied for deleting project." );
	#}

	#project_delete( $t_project_id);

	return $p_response->withStatus(HTTP_STATUS_SUCCESS, "Release with id $release_id deleted." );
}

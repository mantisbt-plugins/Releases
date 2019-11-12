<?php

    require_once( 'releases_api.php' );

    form_security_validate( 'plugin_Releases_config_update' );

    auth_reauthenticate();
    access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

    $t_project_id = helper_get_current_project();

    $t_action = gpc_get_string( 'action', 'none' );
    if ( $t_action == 'update' ) {
        $t_view_access_level = gpc_get_int( 'view_access_level' );
        $t_download_access_level = gpc_get_int( 'download_access_level' );
        $t_upload_access_level = gpc_get_int( 'upload_access_level' );
        $t_upload_method = gpc_get_int( 'upload_method' );
        $t_disk_dir = gpc_get_string( 'disk_dir', PLUGINS_RELEASES_DISK_DIR_DEFAULT );
        $t_file_number = gpc_get_int( 'file_number', PLUGINS_RELEASES_FILE_NUMBER_DEFAULT );
        $t_download_requires_login = gpc_get_bool( 'download_requires_login' );
        $t_create_next_versions = gpc_get_bool( 'create_next_versions' );
        $t_remove_past_unreleased_versions = gpc_get_bool( 'remove_past_unreleased_versions' );
        $t_sort_unreleased_versions = gpc_get_bool( 'sort_unreleased_versions' );
        $t_update_unresolved_issues_tgt = gpc_get_bool( 'update_unresolved_issues_tgt' );
        plugin_config_set( 'view_threshold_level', $t_view_access_level, NO_USER, $t_project_id );
        plugin_config_set( 'download_threshold_level', $t_download_access_level, NO_USER, $t_project_id );
        plugin_config_set( 'upload_threshold_level', $t_upload_access_level, NO_USER, $t_project_id );
        plugin_config_set( 'upload_method', $t_upload_method, NO_USER, $t_project_id );
        plugin_config_set( 'disk_dir', $t_disk_dir, NO_USER, $t_project_id );
        plugin_config_set( 'file_number', $t_file_number, NO_USER, $t_project_id );
        plugin_config_set( 'download_requires_login', $t_download_requires_login, NO_USER, $t_project_id );
        plugin_config_set( 'create_next_versions', $t_create_next_versions, NO_USER, $t_project_id );
        plugin_config_set( 'remove_past_unreleased_versions', $t_remove_past_unreleased_versions, NO_USER, $t_project_id );
        plugin_config_set( 'sort_unreleased_versions', $t_sort_unreleased_versions, NO_USER, $t_project_id );
        plugin_config_set( 'update_unresolved_issues_tgt', $t_update_unresolved_issues_tgt, NO_USER, $t_project_id );
    }
    if ( $t_action == 'delete' && $t_project_id != ALL_PROJECTS ) {
        plugin_config_delete( 'view_threshold_level', NO_USER, $t_project_id );
        plugin_config_delete( 'download_threshold_level', NO_USER, $t_project_id );
        plugin_config_delete( 'upload_threshold_level', NO_USER, $t_project_id );
        plugin_config_delete( 'upload_method', NO_USER, $t_project_id );
        plugin_config_delete( 'disk_dir', NO_USER, $t_project_id );
        plugin_config_delete( 'file_number', NO_USER, $t_project_id );
        plugin_config_delete( 'download_requires_login', NO_USER, $t_project_id );
        plugin_config_delete( 'create_next_versions', NO_USER, $t_project_id );
        plugin_config_delete( 'remove_past_unreleased_versions', NO_USER, $t_project_id );
        plugin_config_delete( 'sort_unreleased_versions', NO_USER, $t_project_id );
        plugin_config_delete( 'update_unresolved_issues_tgt', NO_USER, $t_project_id );
    }

    form_security_purge( 'plugin_Releases_config_update' );

    $t_redirect_url = plugin_page('config', TRUE);
    
    layout_page_header( null, $t_redirect_url );
    layout_page_begin();
    html_operation_successful( $t_redirect_url );
    layout_page_end();

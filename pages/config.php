<?php

require_once( 'constant_api.php' );
require_once( 'releases_api.php' );

auth_reauthenticate(  );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

layout_page_header( plugin_lang_get( 'configuration_page_title' ) );

layout_page_begin( 'manage_overview_page.php' );
print_manage_menu( 'manage_plugin_page.php' );

$t_project_id = helper_get_current_project();

?>

<br />
<!-- div align="center" -->
<?php

  //echo str_replace( '%%project%%', '<b>' . project_get_name( helper_get_current_project() ) . '</b>',plugin_lang_get( 'configuration_for_project' ) );
  releases_plugin_config_section_title( 
      str_replace( '%%project%%', '<b>' . project_get_name( helper_get_current_project() ) . '</b>', plugin_lang_get( 'configuration_for_project' ) ), 
      'fa-file-o'
  );
?>
  <br />
  <form name="plugins_releases" method="post" action="<?php echo plugin_page( 'config_update' ) ?>">
    <?php echo form_security_field( 'plugin_Releases_config_update' ) ?>
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="project_id" value="<?php echo $t_project_id ?>" />
    <input type="hidden" name="plugin" value="releases" />
<!--    <table class="width75" cellspacing="1"> -->
    <table class="width100 table table-striped table-bordered table-condensed" cellspacing="1">

      <tr><td colspan="2">
        <?php echo releases_plugin_config_section_subtitle( plugin_lang_get( 'user_access' ), 'fa-user', 'releases_user_access_config' ); ?>
      </td></tr>

      <!-- View access level - notes -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo plugin_lang_get( 'view_access_level' ); ?>
        </td>
        <td width="70%">
          <select name="view_access_level">
            <?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'view_threshold_level', PLUGINS_RELEASES_VIEW_THRESHOLD_LEVEL_DEFAULT ) ); ?>
          </select>
        </td>
      </tr>

      <!-- View access level - files -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo plugin_lang_get( 'download_access_level' ); ?>
        </td>
        <td width="70%">
          <select name="download_access_level">
            <?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'download_threshold_level', PLUGINS_RELEASES_VIEW_THRESHOLD_LEVEL_DEFAULT ) ); ?>
          </select>
        </td>
      </tr>

      <!-- Upload access level -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo plugin_lang_get( 'upload_access_level' ); ?>
        </td>
        <td width="70%">
          <select name="upload_access_level">
            <?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'upload_threshold_level', PLUGINS_RELEASES_UPLOAD_THRESHOLD_LEVEL_DEFAULT ) ); ?>
          </select>
        </td>
      </tr>

      <!-- Download access level -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo plugin_lang_get( 'download_requires_login' ); ?>
        </td>
        <td width="70%">
            <input type="checkbox" name="download_requires_login"<?php if ( plugin_config_get( 'download_requires_login' )) echo ' checked="checked"' ?> />
        </td>
      </tr>

      <!-- subsection title -->
      <tr><td colspan="2">
        <?php echo releases_plugin_config_section_subtitle( plugin_lang_get( 'file_upload' ), 'fa-upload', 'releases_file_upload_config' ); ?>
      </td></tr>

      <!-- file number -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo plugin_lang_get( 'file_count' ); ?>
        </td>
        <td width="70%">
          <input type="text" name="file_number" value="<?php echo plugin_config_get( 'file_number', PLUGINS_RELEASES_FILE_NUMBER_DEFAULT ); ?>" size="3" maxlength="1" />
        </td>
      </tr>

      <!-- Upload method -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo plugin_lang_get( 'upload_method' ); ?>
          <br /><span class="small">
<?php
        $t_max_size = releases_max_upload_size();
        echo lang_get( 'max_file_size_label' ) . ' '. number_format( $t_max_size[0]/1000 ) . '&nbsp;kB (limited by ' . plugin_lang_get( $t_max_size[1] ) . ')';
?>
          </span>
        </td>
        <td width="70%">
          <select name="upload_method">
            <?php
             /**
              * @todo Database file storage is not yet converted - so function is disabled
                <option value="<?php echo DATABASE ?>"<?php if ( plugin_config_get( 'upload_method', PLUGINS_RELEASES_UPLOAD_METHOD_DEFAULT ) == DATABASE ) echo ' selected="selected"'; ?>><?php echo plugin_lang_get( 'method_database' ) ?></option>
              */
            ?>
            <option value="<?php echo DISK ?>"<?php if ( plugin_config_get( 'upload_method', PLUGINS_RELEASES_UPLOAD_METHOD_DEFAULT ) == DISK ) echo ' selected="selected"'; ?>><?php echo plugin_lang_get( 'method_disk' ) ?></option>
            <?php
             /**
              * @todo FTP file storage is not yet converted - so function is disabled
                <option value="<?php echo FTP ?>"<?php if ( plugin_config_get( 'upload_method', PLUGINS_RELEASES_UPLOAD_METHOD_DEFAULT ) == FTP ) echo ' selected="selected"'; ?>><?php echo plugin_lang_get( 'method_ftp' ) ?></option>
              */
            ?>
          </select>
        </td>
      </tr>

      <!-- Disk parameter -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo plugin_lang_get( 'disk_path' ); ?>
        </td>
        <td width="70%">
          <input type="text" name="disk_dir" size="60" value="<?php echo plugin_config_get( 'disk_dir', PLUGINS_RELEASES_DISK_DIR_DEFAULT ); ?>" />
        </td>
      </tr>

      <tr>
        <td class="left">
          <span class="required"> * <?php echo lang_get( 'required' ) ?></span>
        </td>
        <td class="center">
        </td>
      </tr>

      <!-- subsection title -->
      <tr><td colspan="2">
        <?php echo releases_plugin_config_section_subtitle( plugin_lang_get( 'actions_on_release' ), 'fa-cog', 'releases_actions_on_release_config' ); ?>
      </td></tr>

      <!-- Create next versions on release -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <?php echo plugin_lang_get( 'create_next_versions' ); ?>
        </td>
        <td width="70%">
          <input type="checkbox" name="create_next_versions"<?php if ( plugin_config_get( 'create_next_versions', OFF ) == ON ) echo ' checked="checked"' ?> />
        </td>
      </tr>

      <!-- Remove past unreleased versions on release -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <?php echo plugin_lang_get( 'remove_past_unreleased_versions' ); ?>
        </td>
        <td width="70%">
          <input type="checkbox" name="remove_past_unreleased_versions"<?php if ( plugin_config_get( 'remove_past_unreleased_versions', OFF ) == ON ) echo ' checked="checked"' ?> />
        </td>
      </tr>

      <!-- Resort unreleased versions on release -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <?php echo plugin_lang_get( 'sort_unreleased_versions' ); ?>
        </td>
        <td width="70%">
          <input type="checkbox" name="sort_unreleased_versions"<?php if ( plugin_config_get( 'sort_unreleased_versions', OFF ) == ON ) echo ' checked="checked"' ?> />
        </td>
      </tr>

      <!-- Update target versions for unresolved bugs that have target version released -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <?php echo plugin_lang_get( 'update_unresolved_issues_tgt' ); ?>
        </td>
        <td width="70%">
          <input type="checkbox" name="update_unresolved_issues_tgt"<?php if ( plugin_config_get( 'update_unresolved_issues_tgt', OFF ) == ON ) echo ' checked="checked"' ?> />
        </td>
      </tr>

    </table>

    <!-- Submit Button -->
    <table><tr><td colspan="2"> &nbsp;
      <input tabindex="4" type="submit" class="button" value="<?php echo lang_get( 'submit_button' ) ?>" />
      <?php if ( $t_project_id != ALL_PROJECTS ) { ?><input type="button" class="button" value="<?php echo lang_get( 'revert_to_all_project' ) ?>" onclick="document.forms.plugins_releases.action.value='delete';document.forms.plugins_releases.submit();" /><?php } ?>
    </td></tr></table>

  </form>

</div>
</div>
</div>


<?php
    layout_page_end();
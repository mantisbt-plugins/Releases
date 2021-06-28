# RELEASES CHANGE LOG

## Version 1.3.2 (June 27th, 2021)

### Documentation

- **README:** add section link for jenkins integration
- **README:** remove personal mantisbt bugs page ref

### Bug Fixes

- **Dry Run:** dry run should return an id.  Return 0.

## Version 1.3.1 (December 14th, 2019)

### Bug Fixes

- the 'get releases' endpoint is broken

## Version 1.3.0 (December 13th, 2019)

### Documentation

- **Readme:** add configuration section

### Features

- add new endpoint for retrieving a version changelog.

	The following endpoint has been added to achieve this functionality:

	    /releases/{project}/{changelog/{version}

## Version 1.2.0 (November 17th, 2019)

### Features

- add support for several post-release actions:

	create_next_versions
	remove_past_unreleased_versions
	sort_unreleased_versions
	update_unresolved_issues_tgt

## Version 1.1.9 (August 17th, 2019)

### Bug Fixes

- can no longer delete releases as of v1.1.8
- when manually uploading assets, the user id is shown as the 'released by' user instead of the username/avatar
- **Releases Page:** manually uploaded releases differ in title to those uploaded via rest api

### Refactoring

- remove reauthorization for requests coming from releases page itself

## Version 1.1.8 (August 17th, 2019)

### Bug Fixes

- fix security vulnerabilities with delete/enable/upload actions on release page
- gantt chart buttons no longer appear after remaing of GanttChart2 plugin to GanttChart

### Visual Enhancements

- convert download links to buttons
- show assets to users with view_threshhold_level but not download button

## Version 1.1.7 (August 15th, 2019)

### Refactoring

- use query binding for all database inserts/updates

## Version 1.1.6 (August 14th, 2019)

### Bug Fixes

- **API:** an existing version should have its date_order updated when a release request is made for that version

## Version 1.1.5 (August 10th, 2019)

### Documentation

- **readme:** udpate issues submit section

### Refactoring

- if a project does not have any released versions, dont display the Releases link in the navigation bar.

## Version 1.1.4 (August 8th, 2019)

### Documentation

- **readme:** move screenshots to bottom to follow convention

### Bug Fixes

- php include path is being filled with mutliple entries for core_path
- tgz release package does not contain the plugin directory as the top level

## Version 1.1.3 (August 3rd, 2019)

### Build System

- **ap:** add gzip tarball to mantisbt and github release assets

## Version 1.1.2 (August 3rd, 2019)

### Features

- show the success redirect when saving config settings

## Version 1.1.1 (July 29th, 2019)

### Documentation

- **README:** update info

### Bug Fixes

- **releases:** error encountered when clicking Edit to edit a release if changelog exceeds 8192 bytes (Apache specific).

	The changelog was previously encoded and included as a GET parameter.This param has been eliminated.

## Version 1.1.0 (July 29th, 2019)

### Build System

- **app-publisher:** correct mantisbt release url to https

### Documentation

- **readme:** update version badges

### Features

- **releases:** add support for editing a released version changelog

## Version 1.0.5 (July 27th, 2019)

### Bug Fixes

- cannot save config settings - missing parameter download_threshhold

## Version 1.0.4 (July 27th, 2019)

### Code Refactoring

- update to MIT license

## Version 1.0.3 (July 27th, 2019)

### Build System

- **app-publisher:** set interactive flag to N for non-interactive setting of new version during publish run (compliments of ap v1.10.4 update)

### Miscellaneous

- Update license to GPLv3

## Version 1.0.2 (July 26th, 2019)

### Documentation

- **readme:** update installation section and issues badge links

### Bug Fixes

- automatically generated bug links in changelog are missing the last digit in the bug id.

## Version 1.0.1 (July 25th, 2019)

### Bug Fixes

- gantt chart button is no longer displayed by patched files in roadmap and changelog page.  GanttChart plugin renamed to GanttChart2 causes the break.

## Version 1.0.0 (July 25th, 2019)

### Chores

- Initial release

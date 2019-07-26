# Release Management MantisBT Plugin

[![app-type](https://img.shields.io/badge/category-mantisbt%20plugins-blue.svg)](https://github.com/spmeesseman)
[![app-lang](https://img.shields.io/badge/language-php-blue.svg)](https://github.com/spmeesseman)
[![app-publisher](https://img.shields.io/badge/%20%20%F0%9F%93%A6%F0%9F%9A%80-app--publisher-e10000.svg)](https://github.com/spmeesseman/app-publisher)

[![authors](https://img.shields.io/badge/authors-scott%20meesseman%20--%20vincent%20debout--%20jiri%20hron-6F02B5.svg?logo=visual%20studio%20code)](https://github.com/spmeesseman)
[![GitHub issues open](https://img.shields.io/github/issues-raw/spmeesseman/Releases.svg?maxAge=2592000&logo=github)](https://github.com/spmeesseman/Releases/issues)
[![GitHub issues closed](https://img.shields.io/github/issues-closed-raw/spmeesseman/Releases.svg?maxAge=2592000&logo=github)](https://github.com/spmeesseman/Releases/issues)

- [Release Management MantisBT Plugin](#Release-Management-MantisBT-Plugin)
  - [Description](#Description)
  - [Installation](#Installation)
  - [Screenshots](#Screenshots)
    - [Plugin Releases Screen](#Plugin-Releases-Screen)
  - [REST API](#REST-API)
    - [GET: /plugins/Releases/api/releases/{project}/{id}](#GET-pluginsReleasesapireleasesprojectid)
    - [POST: /plugins/Releases/api/releases/{project}](#POST-pluginsReleasesapireleasesproject)
    - [PUT: /plugins/Releases/api/releases/{project}/{id}](#PUT-pluginsReleasesapireleasesprojectid)
    - [DELETE: /plugins/Releases/api/releases/{project}/{id}](#DELETE-pluginsReleasesapireleasesprojectid)
  - [Future Maybes](#Future-Maybes)
  - [Authors of Original Code Base](#Authors-of-Original-Code-Base)

## Description

This plugin is a continuation of the legacy `releasemgt` plugin.  It allows user to store releases composed of a changelog and assets (file downloads).  There have been several visual improvements and bug fixes, as well as a new REST API to create releases and upload assets.

This plugin was developed and tested on MantisBT 2.21.1.

## Installation

Extract the release archive to the MantisBT installations plugins folder:

    cd /var/www/mantisbt/plugins
    wget -O Releases.zip https://github.com/spmeesseman/Releases/releases/download/v1.0.1/Releases.zip
    unzip Releases.zip
    rm -f Releases.zip

Ensure to use the latest released version number in the download url.

Install the plugin using the default installation procedure for a MantisBT plugin in `Manage -> Plugins`.

For Apache configuration, see the example Location directive found in api/apache2-site-config

## Screenshots

### Plugin Releases Screen

![Release Page](res/releases.png "Plugin releases screen")

## REST API

The `Authorization` header value must be set to the API token for authentication in all requests.  The token can be sreated in User Preferences for the user that will be used to make the requests under.

For example:

    Authorization: DvhKlx9_g5dNkBEI4jqVmwAxaN9a1y3P

The following endpoints are available to automatically create/update releases with assets/files:

### GET: /plugins/Releases/api/releases/{project}/{id}

Example Response Body

    {
        "id" 1,
        "title": "ProjectName - Version 1.4.3",
        "notes": ".......",
        "assets": [
        {
            "name": "package.json",
            "data": "VGVzdCB0ZXN....0IHRlc3QgdGVzdA=="
        }]
    }

### POST: /plugins/Releases/api/releases/{project}

Creates the specified version if it does not already exists.  Assets are attached to created or pre-existing releases.  The "release" is unique to the "version", each release can have only one version and vice versa.  "{project}" is the MantisBT project name, case sensitive.

Example Request Body

    {
        "version": "1.4.3",
        "notes": ".......",
        "assets": [
        {
            "name": "package.json",
            "data": "VGVzdCB0ZXN....0IHRlc3QgdGVzdA=="
        }]
    }

Example Response Body

    {
        "id": 1432,
        "url": "https://my.domain.com/mantisbt/plugin.php?page=Releases/releases#1.4.3
    }

### PUT: /plugins/Releases/api/releases/{project}/{id}

Not supported in v1.0.0-alpha

### DELETE: /plugins/Releases/api/releases/{project}/{id}

Not supported in v1.0.0-alpha

## Future Maybes

- Edit Asset / addt button
- Delete Version / addtl button
- Source control / commits integration via source-integration plugin

## Authors of Original Code Base

- Vincent Debout <deboutv@free.fr>
  [http://deboutv.free.fr/mantis/plugin.php?plugin=ReleaseMgt](http://deboutv.free.fr/mantis/plugin.php?plugin=ReleaseMgt)
- Jiri Hron <jirka.hron@gmail.com>
  [http://code.google.com/p/mantis-releasemgt/](http://code.google.com/p/mantis-releasemgt/)

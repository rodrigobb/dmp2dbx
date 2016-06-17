# Dmp2Dbx

Shortened from "dump to Dropbox". A simple tool to backup a db dump to Dropbox

## Setting up

### Requirements

* PHP 5.4
* Composer installed (https://getcomposer.org/download/)

### Installation

* Clone project
* run composer install

### Configuration
```html
  //TODO
```
### Usage
```sh
Usage:
  dmp2dbx:upload [options] [--] <source>

Arguments:
  source                           file to upload

Options:
  -c, --configFile[=CONFIGFILE]    Configuration file path [default: "/Users/rodrigo/Sites/dmp2dbx/src/RBBusiness/Dmp2Dbx/Command/../Resources/config/config.json"]
  -t, --accessToken[=ACCESSTOKEN]  Dropbox app access token
      --no-update                  If set, configuration values won't be updated to config file
  -h, --help                       Display this help message
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
 Uploads DB dump file to Dropbox
```

## TO DO

* Make own DB dump
* Upload folder
* Upload progress
* Update version option
* Remove -q option
* Remove -n option
* tests

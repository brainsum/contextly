CONTEXTLY
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This Contextly module integrates a Drupal site with Contextly. 

contextly.com is the most powerful content recommendation system for publishers.
Free 21 days trial account is available.

 * For a full description on Contextly services visit:
  https://contextly.com

 * For a full description of the module visit:
  https://www.drupal.org/project/contextly

 * To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/contextly


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the contextly module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.

Drupal 8.x-2.x
1. Download contextly kit from http://contextlysitescripts.contextly.com/kit/releases/contextly-kit-2.7.tar.gz
2. Unpack it.
3. Create contextly folder in Drupal root vendor folder.
4. Move or copy config and server folders to above created folder.
5. Add classmap to Drupal root composer.json:
```json
    "autoload": {
        "classmap": [
            "vendor/contextly/server/"
        ]
    },
```

If not empty the autoload section just add the "vendor/contextly/server/" line:
```json
    "autoload": {
        "classmap": [
            "scripts/composer/ScriptHandler.php",
            "vendor/contextly/server/"
        ],
        "files": ["load.environment.php"]
    },
```
6. Run composer command: composer dumpautoload
7. Create contextly folder in document root libraries folder.
8. Move or copy client and console folders to above created contextly folder.

CONFIGURATION
--------------

    1. Navigate to Administration > Extend and enable the Conextly module.
    2. Navigate to Administration > Configuration > Content > Contextly.
    3. Enter your Contextly api key.
    4. Navigate to Structure > Block layout.
    5. Place block to region where you need Contextly recommendation.
    6. Select the properly widget on block settings form.
    7. Save block.


MAINTAINERS
-----------

The 8.x branches were created by:

 * dj1999 (Brainsum Kft.) - https://www.drupal.org/u/dj1999


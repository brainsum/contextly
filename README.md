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

Drupal 8.x-2.x with composer:
1. Add this code to repositories section to Drupal root composer.json:
```json
       {
            "type": "package",
            "package": {
                 "name": "contextly/contextly-kit",
                 "version": "0.1.0",
                 "type": "drupal-library",
                 "dist": {
                      "url": "http://contextlysitescripts.contextly.com/kit/releases/contextly-kit-2.7.tar.gz",
                      "type": "tar"
                 },
                 "autoload": {
                      "classmap": ["server/"]
                 }
            }
        }
```

2. Add secure http disable to config section to Drupal root composer.json:
```json
    "secure-http": false
```
3. Run composer command: composer require brainsum/contextly

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


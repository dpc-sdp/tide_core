# Tide Jira

CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Troubleshooting
* FAQ
* Maintainers

INTRODUCTION
------------
The Tide Jira provides an integration layer between content moderation workflows and the Jira Service Management platform.

When a piece of content is set to one of the "review" states (either Needs Review or Archive Pending) a ticket is automatically added to the queue.

During a cron run, the queue worker sends items in the queue off to Jira, creating a ticket.


REQUIREMENTS
------------
* [PHP 7.4 or above]
* [JIRA Rest] (https://www.drupal.org/project/jira_rest)
* [php-jira-rest-client] (https://github.com/lesstif/php-jira-rest-client)


INSTALLATION
------------
This module must be installed as part of tide_core and be managed via Composer.


CONFIGURATION
------------
* A general configuration page is available at /admin/config/development/tide_jira.
* During installation, a default JIRA endpoint is configured. The username and password are retrieved from the environment variables `JIRA_USERNAME` and `JIRA_PASSWORD`. Please make sure these are configured in the PHP environment.


MAINTAINERS
-----------

Current maintainers:
* Single Digital Presence -
  https://github.com/dpc-sdp

# Tide Jira

CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Implementation
* Configuration
* Troubleshooting
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

IMPLEMENTATION
------------
1) Content that is set to Needs Review or Archived Pending triggers the `tide_jira_node_presave` hook.
2) This hook calls generateJiraRequest in the TideJiraAPI class. This handles deriving metadata about the revision, such as the author.
3) The revision metadata is used to instantiate a TideJiraTicketModel object.
4) This object is then serialised and stored in the `tide_jira` queue.
5) During cron, the TideJiraProcessorBase queue worker takes these objects off the queue sequentially. It then performs a series of lookups to determine the user's JIRA account ID and JIRA field UUIDs, then appends the information to the TideJiraTicketModel object.
6) An attempt is made to create a request via the JIRA REST API using data from the TideJiraTicketModel object. If successful, the object is destroyed and the queue worker processes the next item in the queue. If the JIRA API returns an error, the queue worker will be suspended until the next cron run. It will attempt to contact the JIRA API 3 times before giving up and destroying the request object.

CONFIGURATION
------------
* A general configuration page is available at /admin/config/development/tide_jira.
* During installation, a default JIRA endpoint is configured. The username and password are retrieved from the environment variables `JIRA_USERNAME` and `JIRA_PASSWORD` using the Key module. Please make sure these are configured in the PHP environment.


TROUBLESHOOTING
-----------

Q: No requests are being sent to JIRA?

A: Please check that the Jira Username and Password are configured correctly in the Key module. Please also check that the default JIRA endpoint is correct.

Q: No requests are being sent to JIRA for a specific user?

A: Please check that the user account has an email address configured, and that they are assigned to a Department with a JIRA project configured.

MAINTAINERS
-----------

Current maintainers:
* Single Digital Presence -
  https://github.com/dpc-sdp

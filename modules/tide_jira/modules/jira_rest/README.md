CONTENTS OF THIS FILE
---------------------

* Introduction
* Installation
* Usage examples


INTRODUCTION
------------

Providing methods to modify JIRA issues
out of Drupal via REST.

Requirements (via composer.json):
Library: https://github.com/lesstif/php-jira-rest-client

REQUIREMENTS
------------

This module requires the following modules:

* Key (https://www.drupal.org/project/key)

INSTALLATION
------------

Please use composer to download it into your D8 project, e.g. install current 8.x-4.x-dev by running the
following command in the drupal root folder/where your composer.json resides:

composer require drupal/jira_rest:4.x-dev

or current stable release 8.x-3.0 with:

composer require drupal/jira_rest:3.0

Enable the module by navigating to "Extend" (admin/modules) or via drush

CONFIGURATION
-------------

Setup your JIRA instance & parameters under this route:
admin/config/services/jira_rest

Previously in 3.x and older, there was one JIRA setting stored within simple settings. JIRA Rest now uses config
entities to allow for multiple connections to JIRA.

***NOTE*** If you use more than one endpoint, you must make sure your code specifies it!
```
// Old Configuration Setting:
$issue = $jira_rest_wrapper_service->getIssueService()->get('PROJECT-KEYID');

// New Configuration Setting:
$issue = $jira_rest_wrapper_service->getIssueService('MY_JIRA_ENDPOINT_ID')->get('PROJECT-KEYID');
```


USAGE EXAMPLES
-------------
To see more examples, you can read them here: https://github.com/lesstif/php-jira-rest-client/blob/master/README.md

These are probably most common ones:

Try if you can reach the route /jira_rest/test from your admin account.

Quick Examples
(load issue by key)
```
$jira_rest_wrapper_service = new JiraRestWrapperService();
$issue = $jira_rest_wrapper_service->getIssueService('MY_JIRA_ENDPOINT_ID')->get('PROJECT-KEYID');
```
(search)
```
// search for existing open issues
$search = $jiraRestWrapperService->getIssueService('MY_JIRA_ENDPOINT_ID')->search("status = Open");

foreach ($search->getIssues() as $i){
$issue = $i;
break;
}

$issuekey = $issue->key
```

(create issue and subissue)

In Summary, you need to create an issue_field to store the issue metadata, then create the issue
using the JIRA REST API module
```
$issue_field = new JiraRestApi\Issue\Issuefield()
$jiraRestWrapperService->getIssueService('MY_JIRA_ENDPOINT_ID')->create($issuefield);
```

Usage:
```
$issue_field->(methodname)(value);
Example Fields:
      'summary' => 'setSummary',
      'assignee_name' => 'setAssigneeName',
      'priority_name' => 'setPriorityName',
      'issue_type' => 'setIssueType',
      'description' => 'setDescription',
      'version' => 'addVersion',
      'components' => 'addComponents',
      'security_id' => 'setSecurityId',
      'due_date' => 'setDueDate'
```
Adding custom fields:
```
$issue_field->addCustomField($customfieldid, $value);
```

Adding Labels
```
//set a label
$labels[] = utf8_encode('Urgent');
$issue_field->setLabels($labels);
```

Adding Parent issue:
```
$issue_field->setParentKeyOrId($value)
```

Unassign an issue:
```
$issue_field->setAssigneeToUnassigned();
```

Set assignee to default:
```
$issue_field->setAssigneeToDefault();
```

Adding Attachments requires the issue to already exist.
***NOTE*** This is changed from 3.x. The Wrapper no longer has an attachments method!
```
$issue = $jira_rest_wrapper_service->getIssueService('MY_JIRA_ENDPOINT_ID')->get('PROJECT-KEYID');
$jira_rest_wrapper_service->getIssueService('MY_JIRA_ENDPOINT_ID')->addAttachments($issue->id, $file_path);
```

Adding Comments:
```
$comment = new JiraRestApi\Issue\Comment();
$body = "My comment here";
$comment->setBody($body);
$jira_rest_wrapper_service->getIssueService()->addComment('ISSUE-KEY', $comment);
```

Getting Comments:
```
$comment = $jira_rest_wrapper_service->getIssueService()->getComments('ISSUE-KEY');
```

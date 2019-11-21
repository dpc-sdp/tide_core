# Tide Workflow Notification
Provides functionality to send notifications upon workflow state changes.

## Notification recipients
* Draft to Needs Review: all Approvers.
* Draft to Published: all Approvers.
* Needs Review to Draft: the last author.
* Needs Review to Published: all Approvers and the last author.
* Published to Archived: all Approvers and the last author.

## Hooks
* `hook_tide_workflow_notification_get_node_approvers_alter`
    Modify the approvers of a node. See more in [tide_workflow_notification.api.php](tide_workflow_notification.api.php)

Feature: Module management permissions

  Ensure that modules management permissions are not set for roles.

  @api
  Scenario Outline: Users have no access to modules management page
    Given I am logged in as a user with the "<role>" role
    When I go to "<path>"
    Then I should get a "<response>" HTTP response
    Examples:
      | role               | path                     | response |
      | administrator      | admin/modules            | 404      |
      | site_admin         | admin/modules            | 404      |
      | editor             | admin/modules            | 404      |
      | approver           | admin/modules            | 404      |
      | previewer          | admin/modules            | 404      |
      | administrator      | admin/modules/uninstall  | 404      |
      | site_admin         | admin/modules/uninstall  | 404      |
      | editor             | admin/modules/uninstall  | 404      |
      | approver           | admin/modules/uninstall  | 404      |
      | previewer          | admin/modules/uninstall  | 404      |

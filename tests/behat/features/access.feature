@tide
Feature: Access permissions

  Ensure that configuration permissions are set correctly for designated roles.

  @api
  Scenario Outline: Users have access to administrate menus
    Given I am logged in as a user with the "<role>" role
    When I go to "<path>"
    Then I should get a "<response>" HTTP response
    Examples:
      | role               | path                  | response |
      # Blocks.
      | authenticated user | admin/structure/block | 404      |
      | administrator      | admin/structure/block | 200      |
      | site_admin         | admin/structure/block | 200      |
      | editor             | admin/structure/block | 200      |
      | approver           | admin/structure/block | 200      |
      | previewer          | admin/structure/block | 404      |
      # Menu.
      | authenticated user | admin/structure/menu  | 404      |
      | administrator      | admin/structure/menu  | 200      |
      | site_admin         | admin/structure/menu  | 200      |
      | editor             | admin/structure/menu  | 200      |
      | approver           | admin/structure/menu  | 200      |
      | previewer          | admin/structure/menu  | 404      |
      # User management.
      | authenticated user | admin/people          | 404      |
      | administrator      | admin/people          | 200      |
      | site_admin         | admin/people          | 200      |
      | editor             | admin/people          | 404      |
      | approver           | admin/people          | 404      |
      | previewer          | admin/people          | 404      |

  @api
  Scenario: Site Admin role has access to assign roles.
    Given I am logged in as a user with the "Site Admin" role
    When I go to "admin/people"
    Then I select "Add the Site Admin role to the selected user(s)" from "edit-action"
    And I select "Add the Previewer role to the selected user(s)" from "edit-action"
    And I select "Add the Editor role to the selected user(s)" from "edit-action"
    And I select "Add the Approver role to the selected user(s)" from "edit-action"
    And I select "Remove the Site Admin role from the selected user(s)" from "edit-action"
    And I select "Remove the Previewer role from the selected user(s)" from "edit-action"
    And I select "Remove the Editor role from the selected user(s)" from "edit-action"
    And I select "Remove the Approver role from the selected user(s)" from "edit-action"

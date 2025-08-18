@jsonapi
Feature: Access restrictions

  Ensure the roles has expected restrictions.

  @api
  Scenario: Approver plus role should not have access to create new user.
    Given I am logged in as a user with the "approver_plus" role
    When I go to "admin/people/create"
    Then I should get a 404 HTTP response



  @api @trait:LinkTrait
  Scenario: approver_plus user can see but not edit admin and site_admin users.
    Given users:
      | name            | status | uid    | mail                        | pass         | roles           |
      | test.admin      | 1      | 900001 | admin@example.com           | Pass123!      | administrator   |
      | test.siteadmin  | 1      | 900002 | siteadmin@example.com       | Pass123!      | site_admin      |
      | test.editor     | 1      | 900003 | editor@example.com          | Pass123!      | editor          |
      | test.approver   | 1      | 900004 | approver@example.com        | Pass123!      | approver        |
      | test.approver+  | 1      | 900005 | approverplus@example.com    | Pass123!      | approver_plus   |

    When I am logged in as "test.approver+"
    And I go to "/admin/people"
    Then I should get a 200 HTTP response
    And I should see "test.admin"
    And I should see "test.siteadmin"
    And I should see "test.editor"
    And I should see "test.approver"

    # Approver+ should see edit links for approver and editor users
    Then I should see the link "Edit" with "/user/900003/edit"
    Then I should see the link "Edit" with "/user/900004/edit" in "tr:contains('test.approver')"

    # Approver+ should NOT see edit links for admin and site_admin
    Then I should not see the link "Edit" with "/user/900001/edit"
    Then I should not see the link "Edit" with "/user/900002/edit"

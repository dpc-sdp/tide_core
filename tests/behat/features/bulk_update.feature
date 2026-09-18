@tide
Feature: Bulk update

  @api @javascript
  Scenario: Users with approver should be able to bulk publish content.
    Given test content:
    | title  | body   |
    | test_1 | demo 1 |
    | test_2 | demo 2 |
    Given I am logged in as a user with the "Approver" role
    When I visit "/admin/content"
    Then I check "edit-node-bulk-form-0"
    Then I check "edit-node-bulk-form-1"
    Then I select "Publish content" from "Action"
    Then I press "Apply to selected items"
    And I wait for 1 second
    Then I press "Confirm"
    And I wait for the batch process to finish for 180 seconds
    Then I should see the text "Selected content has been processed"

    # Load a user without the permission.
    Given I am logged in as a user with the "Contributor" role
    When I visit "/admin/content"
    Then I check "edit-node-bulk-form-0"
    Then I check "edit-node-bulk-form-1"
    Then I select "Publish content" from "Action"
    Then I press "Apply to selected items"
    And I wait for 1 second
    Then I should see the text "The requested page could not be found."

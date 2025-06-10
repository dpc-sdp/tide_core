@tide
Feature: Text formats

  Ensure that text formats are configured as expected.

  @api
  Scenario: Text formats are available
    Given I am logged in as a user with the "administer filters" permission
    When I go to "admin/config/content/formats"

    Then I should see the text "Administrator" in the "Admin Text" row
    And I should see the text "Approver" in the "Admin Text" row
    And I should see the text "CKEditor" in the "Admin Text" row

    And I should see the text "Administrator" in the "Rich Text" row
    And I should see the text "Approver" in the "Rich Text" row
    And I should see the text "Editor" in the "Rich Text" row
    And I should see the text "CKEditor" in the "Rich Text" row

    And I should see the text "This format is shown when no other formats are available" in the "Plain text" row
    And I should see the text "—" in the "Plain text" row

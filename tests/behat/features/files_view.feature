Feature: Change filtering options in the files view.

  Ensure page elements shows as expected.

  @api
  Scenario: The view page contains required elements.
    Given I am logged in as a user with the "site_admin" role
    When I go to "admin/content/files"
    Then the "#edit-file-type-filter" element should contain "PDF"
    Then the "#edit-file-type-filter" element should contain "PNG"
    Then the "#edit-file-type-filter" element should contain "TXT"
    Then I should see a "input#edit-uid" element
    And I should see a "th#view-name-table-column" element

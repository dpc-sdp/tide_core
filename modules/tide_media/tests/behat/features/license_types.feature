@tide
Feature: Check that license_types exist.

  Ensure License types exist.

  @api
  Scenario: License types exist.
    Given I am logged in as a user with the "administrator" role
    When I go to "admin/structure/taxonomy/manage/license_type/overview"
    Then I should see the link "Copyright"
    And I should see the link "Creative Commons Attribution 4.0"

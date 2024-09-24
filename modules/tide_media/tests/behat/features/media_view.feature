@tide
Feature: Media view filter addition

  As a site admin, I want to be able to filter media based on License.

  @api @javascript
  Scenario: Media view has Site filter
    Given I am logged in as an administrator
    When I visit "admin/content/media"
    And I should see "License Type" in the "label[for=edit-field-license-type-target-id]" element

  @api
  Scenario: views filter
    Given I am logged in as a user with the "editor" role
    And I go to "/admin/content/media"
    Then I should see an "select#edit-field-media-department-target-id" element

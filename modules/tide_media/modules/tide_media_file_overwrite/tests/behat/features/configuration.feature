@tide @install:tide_media_file_overwrite
# @TODO remove @skipped once the module is extracted to its own repo.
Feature: Configuration for File Upload Overwrite

  Ensure that the configuration page has the expected fields.

  @api @javascript
  Scenario: The the configuration page has the expected fields.
    Given I am logged in as a user with the "administrator" role
    When I visit "admin/config/tide_media_file_overwrite/settings"
    And save screenshot
    Then I see field "Overwrite upload file if the same file name exists?"
    And I should see an "input#edit-needs-overwritten" element
    And I see field "Media form field map"
    And I should see an "textarea#edit-media-form-field-map" element

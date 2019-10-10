@tide
Feature: ClamAV Anti-virus

  Ensure that ClamAV is enabled.

  @api @javascript
  Scenario: Upload EICAR test file to trigger virus detection.
    Given I am logged in as a user with the "administrator" role
    When I go to "media/add/testmedia"
    And I attach the file "clamtest.txt" to "files[field_media_file_test_0]"
    And I wait 80 seconds for AJAX to finish
    Then I should see the error message "The specified file clamtest.txt could not be uploaded."

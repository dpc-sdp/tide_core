@tide
Feature: Webform "Content Rating" exists.

  Ensure that the 'Content Rating' webform exists

  @api @nosuggest
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "administer webform" permission
    When I visit "admin/structure/webform"
    And save screenshot

    And I see the text "Was this page helpful"

  @api @nosuggest @javascript
  Scenario: Webform "Tide webform CAPTCHA" exists.
    Given I am logged in as a user with the "administrator" role
    And captcha_widgets terms:
      | name        | parent | tid   | field_captcha_type  | field_site_key |
      | Test Site 1 | 0      | 10001 | google_recaptcha_v3 | abcd          |
    When I visit "admin/structure/webform/manage/contact/settings"
    And I click on the detail "Third party settings"
    Then I see the text "Tide webform CAPTCHA"
    Then I see the text "Enable captcha"
    Then I see the text "Captcha type"
    Then I select "Google reCAPTCHA v3" from "Captcha type"
    And I wait for AJAX to finish
    Then I select "Test Site 1" from "Site key"
    Then I fill in "Score threshold (reCAPTCHA v3)?" with "0.1"
    Then I check "Enable captcha"
    And I press "Save"
    Then I visit "admin/structure/webform/manage/contact/export"
    And I see the text "captcha_type: google_recaptcha_v3"
    And I see the text "score_threshold: 0.1"
    And I see the text "site_key: abcd"
    And save screenshot

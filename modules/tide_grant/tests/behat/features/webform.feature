@tide
Feature: Webform "Grant Submission" exists.

  Ensure that the 'Grant Submission' webform exists

  @api @nosuggest
  Scenario: The form has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "administer webform" permission
    When I visit "admin/structure/webform"
    Then I should see the link "Grant Submission"

    When I click "Grant Submission"
    Then I see field "Name of grant or program"
    And I see field "Describe the grant or program"
    And I see field "Open date"
    And I see field "Close date"
    And I see field "Topic"
    And I see field "Who is the grant or program for?"
    And I see field "edit-funding-level-from"
    And I see field "edit-funding-level-to"
    And I see field "Website URL to apply for grant or program"
    And I see field "Website URL for grant or program information"
    And I see field "Contact person"
    And I see field "Department, agency or provider organisation"
    And I see field "Contact email address"
    And I see field "Contact telephone number"
    And I should see the button "Submit"

  @api @nosuggest
  Scenario: Check for required form fields.
    Given I am an anonymous user
    When I visit "form/tide-grant-submission"
    Then I should see the heading "Grant Submission" in the "header" region
    And I press "Submit" in the "content" region
    And save screenshot
    And I see the "#edit-name-of-grant-or-program" element with the "required" attribute set to "required" in the "content" region
    And I see the "#edit-describe-the-grant-or-program" element with the "required" attribute set to "required" in the "content" region
    And I see the "#edit-topic" element with the "required" attribute set to "required" in the "content" region
    And I see the "#edit-who-is-the-grant-or-program-for-" element with the "required" attribute set to "required" in the "content" region
    And I see the "#edit-website-url-to-apply-for-grant-or-program" element with the "required" attribute set to "required" in the "content" region
    And I see the "#edit-contact-person" element with the "required" attribute set to "required" in the "content" region
    And I see the "#edit-department-agency-or-provider-organisation" element with the "required" attribute set to "required" in the "content" region
    And I see the "#edit-contact-email-address" element with the "required" attribute set to "required" in the "content" region
    And I see the "#edit-contact-telephone-number" element with the "required" attribute set to "required" in the "content" region

  @api @nosuggest
  Scenario: Check form submission.
    Given an audience term with the name Test
    And a department term with the name Test
    And the cache has been cleared
    And I am an anonymous user
    When I visit "form/tide-grant-submission"
    And I fill in the following:
      | Name of grant or program | Test Grant |
      | Describe the grant or program  | This is the test Grant submission   |
      | Open date |  2026-08-06 |
      | Close date | 2036-08-06 |
      | edit-funding-level-from | 100000 |
      | edit-funding-level-to | 200000 |
      | Website URL to apply for grant or program | http://www.vic.gov.au |
      | Website URL for grant or program information | http://www.vic.gov.au |
      | Contact person | John Doe |
      | Contact email address | noreply@example.com |
      | Contact telephone number | 0412123123 |
    And I select "Arts" from "Topic"
    And I select "Test" from "Who is the grant or program for?"
    And I select "Test" from "Department, agency or provider organisation"
    And I check "I have read and understand how Department of Premier and Cabinet stores information."
    And I press "Submit"
    And I save screenshot
    Then I should see "We'll take a look at your grant before it's published live in the vic.gov.au grants database. We will let you know once your grant has been published. Alternatively, we'll be in touch for more information."

  @api @nosuggest
  Scenario: The Grant node is expected to be created from webform submission.
    Given I am logged in as a user with the "administrator" role
    When I visit "/admin/content?title=&type=grant&status=2&langcode=All"
    Then I should see "Test Grant"

@tide
Feature: Media browser

  As a privileged user, I want to use media browser.

  @api @javascript @skipped
  Scenario: Media Browser is available.
    Given I am logged in as a user with the "create test content, access media overview, access tide_media_browser entity browser pages, access tide_media_browser_iframe entity browser pages, use text format rich_text" permission
    When I visit "/node/add/test"
    And I save screenshot

    Then I see field "Title"
    And I should see a "#edit-body-0-value .ck-sticky-panel" element
    And I click "Media"
    And I wait for AJAX to finish
    Then I should see the text "Select media item to embed"
    And I press the "Close" button

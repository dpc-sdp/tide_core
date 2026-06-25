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

  @api @javascript
  Scenario: Media Browser allows embed with custom data attribute
    Given I am logged in as a user with the "create test content, access media overview, access tide_media_browser entity browser pages, access tide_media_browser_iframe entity browser pages, use text format rich_text" permission
    And media document entity:
      | name          | mid    |
      | Demo Document | 888666 |

    When I visit "/node/add/test"
    Then I press "Media"
    And I wait for AJAX to finish
    Then I should see the text "Select media item to embed"

    When I click on iFramed element ".views-field-entity-browser-select .form-checkbox" within "entity_browser_iframe_tide_media_browser_iframe"
    Then I click on iFramed element "#edit-submit" within "entity_browser_iframe_tide_media_browser_iframe"
    And I wait for AJAX to finish
    Then I should see the text "Embed media item"

    When I check "Display last update date"
    Then I click on the element ".ui-dialog-buttonpane .button--primary"
    And I wait for AJAX to finish
    And I wait for 1 seconds
    Then I should see a ".embedded-entity[data-show-last-updated='1']" element

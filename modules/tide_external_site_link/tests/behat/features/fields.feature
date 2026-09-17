@tide
Feature: Fields for external site link content type

  Ensure that external site link content has the expected fields.

  @api
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "administrator" role

    Given sites terms:
      | name            | tid   |
      | [TEST] Site ESL | 20091 |
    Given department terms:
      | name                  | tid    |
      | [TEST] Department ESL | 200091 |

    When I visit "node/add/external_site_link"
    And save screenshot

    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I see field "Summary"
    And I should see a "textarea#edit-field-landing-page-summary-0-value.required" element

    And I see field "Website URL"
    And I should see an "input#edit-field-node-link-0-uri.required" element

    And the "#edit-field-featured-image" element should contain "Select Images"
    And I should see an "input#edit-field-featured-image-entity-browser-entity-browser-open-modal" element

    And I see field "Keywords"
    And I should see an "input#edit-field-content-keywords-0-value" element
    And I should not see an "input#edit-field-content-keywords-0-value.required" element

    # Hidden elements
    Then I should not see the text "Menu settings" in the "content"
    And I should not see the text "Promotion options" in the "content"
    And I should not see the text "URL redirects" in the "content"
    And I should not see the text "URL alias" in the "content"
    And I should not see the text "Simple XML Sitemap" in the "content"

    And I should not see the text "Preview" in the "content"

    # Fill and submit
    Then I fill in "Title" with "My External Site"
    And I fill in "Summary" with "My External Site Summary"
    And I fill in "Website" with "https://my-external-site.vic.gov.au/"
    And I select "Arts" from "Topic"
    And I select "[TEST] Department ESL" from "Department"
    And I check "[TEST] Site ESL"
    And I select the radio button "[TEST] Site ESL"
    Then I press the "Save" button

    # Status message and lnk
    Then I should be on "/site-20091/external-my-external-site"
    And I should see the text "External site link My External Site has been updated."
    And I should not see the text "Share links"
    And I should not see the text "Create a share preview link"
    And I should not see the text "Click the links below to preview"

    # Revision previews
    When I click "Revisions"
    Then I should see the text "Current revision"
    And I should not see the text "Create a share preview link"

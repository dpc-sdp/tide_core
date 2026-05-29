@tide
Feature: Fields for external site link content type

  Ensure that external site link content has the expected fields.

  @api
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "administrator" role

    Given department terms:
      | name              | tid     |
      | [TEST] Department | 9087601 |

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
    Then I fill in "Summary" with "My External Site Summary"
    Then I fill in "Website" with "https://my-external-site.vic.gov.au/"
    And I select "Arts" from "Topic"
    And I select "[TEST] Department" from "Department"
    And I select "Published" from "Save as"
    Then I press the "Save" button

    # Status message and lnk
    Then I should see the text "External site link My External Site has been updated."
    And I should not see the text "Share links"
    And I should not see the text "Create a share preview link"
    And I should not see the text "Click the links below to preview"

    # Revision previews
    When I click "Revisions"
    Then I should see the text "Current revision"
    And I should not see the text "Create a share preview link"

@tide
Feature: Fields for News content type

  Ensure that News content has the expected fields.

  @api @javascript
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create news content" permission
    When I visit "node/add/news"
    And save screenshot
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I see field "Summary"
    And I should see an "textarea#edit-field-landing-page-summary-0-value" element
    And I should see an "textarea#edit-field-landing-page-summary-0-value.required" element

    And I see field "Introduction Text"
    And I should see an "textarea#edit-field-news-intro-text-0-value" element
    And I should not see an "textarea#edit-field-news-intro-text-0-value.required" element

    And the "#edit-field-featured-image" element should contain "Featured Image"
    And I should see an "input#edit-field-featured-image-entity-browser-entity-browser-open-modal" element

    And I scroll selector "#edit-group-content" into view
    And I click on the horizontal tab "News content"
    And I see field "Body"
    And I should see a "textarea#edit-body-0-value" element
    And I should see a "textarea#edit-body-0-value.required" element

    And I should see text matching "Date"

    And I scroll selector "#edit-group-author-detail" into view
    And I click on the horizontal tab "Author detail"
    And I see field "Location"

    And I see field "Department"

    And I see field "Tags"
    And I should see an "input#edit-field-tags-0-target-id" element
    And I should not see an "input#edit-field-tags-0-target-id.required" element

    And I see field "Topic"
    And I should see an "input#edit-field-topic-0-target-id" element
    And I should see an "input#edit-field-topic-0-target-id.required" element

    And I should see an "input#edit-field-show-topic-term-and-tags-value" element
    And I should not see an "input#edit-field-show-topic-term-and-tags-value.required" element

    And I scroll selector "#edit-group-sidebar" into view
    And I click on the horizontal tab "Sidebar"

    And I see field "Show Site-section navigation?"
    And I should see an "input#edit-field-show-site-section-nav-value" element
    And I should not see an "input#edit-field-show-site-section-nav-value.required" element
    And I should see an "input#edit-field-landing-page-nav-title-0-value" element

    And I click on the detail "Related links"
    And I see field "Show related links?"
    And I should see an "input#edit-field-show-related-content-value" element
    And I should not see an "input#edit-field-show-related-content-value.required" element
    And I should see text matching "Related links"
    And I should see the button "Add Related links" in the "content" region

    And I click on the detail "Contact"
    And I see field "Show contact details"
    And I should see an "input#edit-field-landing-page-show-contact-value" element
    And I should not see an "input#edit-field-landing-page-show-contact-value.required" element
    And I should see text matching "Contact us"
    And I should see text matching "No Contact Us block added yet."
    And I should see the button "Add Contact Us" in the "content" region

    And I should see text matching "Social sharing"

@tide
Feature: Fields for Event content type

  Ensure that Event content has the expected fields.

  @api
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create event content" permission
    When I visit "node/add/event"
    And save screenshot
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I see field "Body"
    And I should see a "textarea#edit-body-0-value" element
    And I should see a "textarea#edit-body-0-value.required" element

    And I see field "Topic"
    And I should see an "input#edit-field-topic-0-target-id" element
    And I should see an "input#edit-field-topic-0-target-id.required" element

    And I see field "Event Category"
    And I should see an "input#edit-field-event-category-0-target-id" element
    And I should see an "input#edit-field-event-category-0-target-id.required" element

    And I see field "Audience"
    And I should see an "input#edit-field-audience-0-target-id" element
    And I should not see an "input#edit-field-audience-0-target-id.required" element

    And I see field "Introduction Text"
    And I should see an "textarea#edit-field-news-intro-text-0-value" element
    And I should not see an "textarea#edit-field-news-intro-text-0-value.required" element

    And I see field "Summary"
    And I should see a "textarea#edit-field-landing-page-summary-0-value" element
    And I should see a "textarea#edit-field-landing-page-summary-0-value.required" element

    And I see field "Description"
    And I should see a "textarea#edit-field-event-description-0-value" element
    And I should not see a "textarea#edit-field-event-description-0-value.required" element

    And I see field "Show Related Content?"
    And I should see an "input#edit-field-show-related-content-value" element
    And I should not see an "input#edit-field-show-related-content-value.required" element

    And I should see text matching "Related links"
    And I should see the button "Add Related links" in the "content" region
    
    And I see field "Show Social Sharing?"
    And I should see an "input#edit-field-show-social-sharing-value" element
    And I should not see an "input#edit-field-show-social-sharing-value.required" element

    And I see field "Show Contact Us?"
    And I should see an "input#edit-field-landing-page-show-contact-value" element
    And I should not see an "input#edit-field-landing-page-show-contact-value.required" element

    And the "#edit-field-featured-image" element should contain "Featured Image"
    And I should see an "input#edit-field-featured-image-entity-browser-entity-browser-open-modal" element

    And I see field "Tags"
    And I should see an "input#edit-field-tags-0-target-id" element
    And I should not see an "input#edit-field-tags-0-target-id.required" element

    And I should see text matching "Contact Us"
    And I should see text matching "No Contact Us block added yet."
    And I should see the button "Add Contact Us" in the "content" region

    And I see field "Show Content Rating?"
    And I should see an "input#edit-field-show-content-rating-value" element
    And I should not see an "input#edit-field-show-content-rating-value.required" element

    And I should see text matching "Event Details"
    And I should see text matching "Book"
    And I should not see an "input#edit-field-event-details-0-subform-field-paragraph-link-0-uri.required" element
    And I should not see an "input#edit-field-event-details-0-subform-field-paragraph-link-0-title.required" element

    And I should see text matching "Website URL"
    And I should see an "input#edit-field-node-link-0-uri" element
    And I should not see an "input#edit-field-node-link-0-uri.required" element

    And I should see text matching "Full Name"
    And I should see an "input#edit-field-node-author-0-value" element
    And I should not see an "input#edit-field-node-author-0-value.required" element

    And I should see text matching "Email address"
    And I should see an "input#edit-field-node-email-0-value" element
    And I should not see an "input#edit-field-node-email-0-value.required" element

    And I should see text matching "Contact Phone Number"
    And I should see an "input#edit-field-node-phone-0-value" element
    And I should not see an "input#edit-field-node-phone-0-value.required" element

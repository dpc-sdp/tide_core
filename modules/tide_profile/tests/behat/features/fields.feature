@tide
Feature: Fields for Profile content type

  Ensure that Alert content has the expected fields.

  @api
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create profile content" permission
    When I visit "node/add/profile"
    And save screenshot
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I should see an "textarea#edit-field-profile-intro-text-0-value" element
    And I should see an "input#edit-field-life-span-0-value" element
    And I should see an "input#edit-field-life-span-0-value" element

    And I see field "Summary"
    And I should see a "textarea#edit-field-landing-page-summary-0-value" element
    And I should see a "textarea#edit-field-landing-page-summary-0-value.required" element

    And I see field "Show Related Content?"
    And I should see an "input#edit-field-show-related-content-value" element
    And I should not see an "input#edit-field-show-related-content-value.required" element

    And I should see text matching "Related links"
    And I should see text matching "No Paragraph added yet."
    And I should see the button "Add Related links" in the "content" region

    And I see field "Show Social Sharing?"
    And I should see an "input#edit-field-show-social-sharing-value" element
    And I should not see an "input#edit-field-show-social-sharing-value.required" element

    And I see field "Show Contact Us?"
    And I should see an "input#edit-field-landing-page-show-contact-value" element
    And I should not see an "input#edit-field-landing-page-show-contact-value.required" element

    And I should see text matching "Contact Us"
    And I should see text matching "No Paragraph added yet."
    And I should see the button "Add Contact Us" in the "content" region

    And I see field "Show Content Rating?"
    And I should see an "input#edit-field-show-content-rating-value" element
    And I should not see an "input#edit-field-show-content-rating-value.required" element

    And I should see text matching "Life span"
    And I should see an "input#edit-field-life-span-0-value" element

    And I see field "Secondary Campaign"
    And I should see an "input#edit-field-landing-page-c-secondary-0-target-id" element

    And the "#edit-field-featured-image" element should contain "Profile Image"
    And I should see an "input#edit-field-featured-image-entity-browser-entity-browser-open-modal" element

    And I see field "Category"
    And I should see an "select#edit-field-profile-category" element

    And I see field "Expertise"
    And I should see an "select#edit-field-expertise" element

    And I see field "Induction Year"
    And I should see an "select#edit-field-year" element

    And I see field "Location"
    And I should see a "input#edit-field-location-0-target-id.required" element

    And I see field "Detail"
    And I should see a "textarea#edit-body-0-value" element

    And I see field "Tags"
    And I should see an "input#edit-field-tags-0-target-id" element
    And I should not see an "input#edit-field-tags-0-target-id.required" element

    And I see field "Topic"
    And I should see an "input#edit-field-topic-0-target-id" element
    And I should see an "input#edit-field-topic-0-target-id.required" element

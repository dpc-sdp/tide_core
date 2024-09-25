@tide
Feature: Fields for Grant content type

  Ensure that Grant content has the expected fields.

  @api @javascript
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create grant content" permission
    When I visit "node/add/grant"
    And save screenshot
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I see field "Topic"
    And I should see an "input#edit-field-topic-0-target-id" element
    And I should see an "input#edit-field-topic-0-target-id.required" element

    And I see field "Audience"
    And I should see an "input#edit-field-audience-0-target-id.required" element

    And I see field "Introduction text"
    And I should see an "textarea#edit-field-landing-page-intro-text-0-value" element
    And I should not see an "textarea#edit-field-landing-page-intro-text-0-value.required" element

    And I see field "Summary"
    And I should see a "textarea#edit-field-landing-page-summary-0-value" element
    And I should see a "textarea#edit-field-landing-page-summary-0-value.required" element

    And the "#edit-field-featured-image" element should contain "Featured Image"
    And I should see an "input#edit-field-featured-image-entity-browser-entity-browser-open-modal" element

    And I click on the horizontal tab "Grant overview"
    Then I see field "Title"
    And I should see an "input#edit-field-overview-title-0-value" element
    And I should see an "textarea#edit-field-description-0-value" element
    And I should see an "input#edit-field-call-to-action-0-uri" element
    # And I see "Funding Level"
    # And I should see an "input#edit-field-node-funding-level-0-from" element

    # Grant date field.
    And I should see an "input#edit-field-node-on-going-value" element
    And I should see an "input#edit-field-node-dates-0-value-date" element
    And I should see an "input#edit-field-node-dates-0-value-time" element
    And I should see an "input#edit-field-node-dates-0-end-value-date" element
    And I should see an "input#edit-field-node-dates-0-end-value-time" element

    And I click on the horizontal tab "Grant Author"
    And I see field "Department"
    And I should see an "select#edit-field-node-department" element

    And I click on the horizontal tab "Grant timeline"
    Then I should see an "#edit-field-node-timeline-0-top-type" element
    And I see field "field_node_timeline[0][subform][field_timeline][0][subform][field_paragraph_title][0][value]"
    And I see field "field_node_timeline[0][subform][field_timeline][0][subform][field_paragraph_date_range][0][value][date]"
    And I see field "field_node_timeline[0][subform][field_timeline][0][subform][field_paragraph_date_range][0][end_value][date]"
    And I see field "field_node_timeline[0][subform][field_timeline][0][subform][field_paragraph_cta_text][0][value]"
    And I see field "field_node_timeline[0][subform][field_timeline][0][subform][field_paragraph_link][0][uri]"
    And I see the button "Select images"
    And I see field "field_node_timeline[0][subform][field_timeline][0][subform][field_paragraph_summary][0][value]"
    And I should see the button "Add Timeline" in the "content" region

    And I click on the horizontal tab "Grant guidelines"
    Then I should see 4 "#edit-field-node-guidelines-0-subform-field-paragraph-accordion-wrapper .field-multiple-table tr.draggable" elements
    And I should see the button "Add Accordion Content" in the "content" region
    When I press "Add Accordion Content"
    And I wait for AJAX to finish
    Then I should see 5 "#edit-field-node-guidelines-0-subform-field-paragraph-accordion-wrapper .field-multiple-table tr.draggable" elements

    And I scroll selector "#edit-group-sidebar" into view
    And I click on the horizontal tab "Sidebar"

    And I click on the detail "Contact"
    And I see field "Show contact details"
    And I should see an "input#edit-field-landing-page-show-contact-value" element
    And I should not see an "input#edit-field-landing-page-show-contact-value.required" element
    And I should see text matching "Contact us"
    And I should see text matching "No Contact Us block added yet."
    And I should see the button "Add Contact Us" in the "content" region

    And I should see text matching "Social sharing"

    And I see field "Show content rating?"
    And I should see an "input#edit-field-show-content-rating-value" element
    And I should not see an "input#edit-field-show-content-rating-value.required" element

    And I see field "Tags"
    And I should see an "input#edit-field-tags-0-target-id" element
    And I should not see an "input#edit-field-tags-0-target-id.required" element

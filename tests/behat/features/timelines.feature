@tide
Feature: Check that Timelines paragraphs.

  Ensure Timeline and Timelines paragraphs and their fields exist.

  @api
  Scenario: Timeline paragraph exists
    Given I am logged in as a user with the "administrator" role
    When I go to "admin/structure/paragraphs_type"
    Then I should see the text "Timeline" in the "timeline" row
    And I should see the text "Timelines" in the "timelines" row

    When I go to "admin/structure/paragraphs_type/timelines/fields"
    Then I should see the text "field_timeline"
    And I should see the text "field_paragraph_title"

    When I go to "admin/structure/paragraphs_type/timeline/fields"
    Then I should see the text "Body" in the "field_paragraph_body" row
    And I should see the text "Title" in the "field_paragraph_title" row
    And I should see the text "Date" in the "field_paragraph_date_range" row
    And I should see the text "Feature image" in the "field_paragraph_media" row
    And I should see the text "Text" in the "field_paragraph_cta_text" row
    And I should see the text "Link" in the "field_paragraph_link" row

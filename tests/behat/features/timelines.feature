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
    Then I should see "Timeline"

    When I go to "admin/structure/paragraphs_type/timeline/fields"
    Then I should see "field_paragraph_body"
    And I should see "field_paragraph_title"
    And I should see the text "field_paragraph_date_range" in the "Date" row
    And I should see the text "field_paragraph_media" in the "Feature image" row
    And I should see the text "field_paragraph_cta_text" in the "Text" row
    And I should see the text "field_paragraph_link" in the "Link" row

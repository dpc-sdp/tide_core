@tide
Feature: Fields for different Media types

  Ensure that all media types have the expected fields.

  @api @javascript @nosuggest
  Scenario: The Document media type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create media" permission
    When I visit "media/add/document"
    Then I see field "Name"
    And I should see an "input#edit-name-0-value.required" element

    And I should see "Add a new file" in the "label[for=edit-field-media-file-0-upload]" element
    And I should see an "label[for=edit-field-media-file-0-upload].form-required" element
    And I should see an "input#edit-field-media-file-0-upload" element

    And I should see "Allowed types: pdf doc docx xls xlsx xlsm csv txt ppt pptx dot dotm dotx tiff tif eps zip." in the "#edit-field-media-file-0--description" element

    And I should see "License Type" in the "label[for=edit-field-license-type]" element
    And I should see an "select#edit-field-license-type" element

    And I should see "Audience" in the "label[for=edit-field-media-audience-0-target-id]" element
    And I should not see an "label[for=edit-field-media-audience-0-target-id].required" element
    And I should see an "input#edit-field-media-audience-0-target-id" element

    And I should see "Department" in the "label[for=edit-field-media-department-0-target-id]" element
    And I should see an "label[for=edit-field-media-department-0-target-id].form-required" element
    And I should see an "input#edit-field-media-department-0-target-id" element

    And I should see "Topic" in the "label[for=edit-field-media-topic-0-target-id]" element
    And I should not see an "label[for=edit-field-media-topic-0-target-id].required" element
    And I should see an "input#edit-field-media-topic-0-target-id" element


  @api @javascript @nosuggest
  Scenario: The File media type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create media" permission
    When I visit "media/add/file"
    Then I see field "Name"
    And I should see an "input#edit-name-0-value.required" element

    And I should see "Add a new file" in the "label[for=edit-field-media-file-0-upload]" element
    And I should see an "label[for=edit-field-media-file-0-upload].form-required" element
    And I should see an "input#edit-field-media-file-0-upload" element

    And I should see "Allowed types: txt rtf doc docx ppt pptx xls xlsx pdf odf odg odp ods odt fodt fods fodp fodg key numbers pages tiff tif eps zip." in the "#edit-field-media-file-0--description" element

    And I should see "License Type" in the "label[for=edit-field-license-type]" element
    And I should see an "select#edit-field-license-type" element

    And I should see "Audience" in the "label[for=edit-field-media-audience-0-target-id]" element
    And I should not see an "label[for=edit-field-media-audience-0-target-id].required" element
    And I should see an "input#edit-field-media-audience-0-target-id" element

    And I should see "Department" in the "label[for=edit-field-media-department-0-target-id]" element
    And I should see an "label[for=edit-field-media-department-0-target-id].form-required" element
    And I should see an "input#edit-field-media-department-0-target-id" element

    And I should see "Topic" in the "label[for=edit-field-media-topic-0-target-id]" element
    And I should not see an "label[for=edit-field-media-topic-0-target-id].required" element
    And I should see an "input#edit-field-media-topic-0-target-id" element

  @api @javascript @nosuggest
  Scenario: The Image media type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create media" permission
    When I visit "media/add/image"
    Then I see field "Name"
    And I should see an "input#edit-name-0-value.required" element

    And I should see "Add a new file" in the "label[for=edit-field-media-image-0-upload]" element
    And I should see an "label[for=edit-field-media-image-0-upload].form-required" element
    And I should see an "input#edit-field-media-image-0-upload" element
    And I should see "Allowed types: png gif jpg jpeg svg." in the "#edit-field-media-image-0--description" element

    And I should see "Caption" in the "label[for=edit-field-media-caption-0-value]" element
    And I should not see an "label[for=edit-field-media-caption-0-value].form-required" element
    And I should see an "input#edit-field-media-caption-0-value" element

    And I should see "Alignment" in the "label[for=edit-field-media-alignment]" element
    And I should not see an "label[for=edit-field-media-alignment].form-required" element
    And I should see an "select#edit-field-media-alignment" element

    And I should see "License Type" in the "label[for=edit-field-license-type]" element
    And I should see an "select#edit-field-license-type" element

    And I should see "Audience" in the "label[for=edit-field-media-audience-0-target-id]" element
    And I should not see an "label[for=edit-field-media-audience-0-target-id].required" element
    And I should see an "input#edit-field-media-audience-0-target-id" element

    And I should see "Department" in the "label[for=edit-field-media-department-0-target-id]" element
    And I should see an "label[for=edit-field-media-department-0-target-id].form-required" element
    And I should see an "input#edit-field-media-department-0-target-id" element

    And I should see "Topic" in the "label[for=edit-field-media-topic-0-target-id]" element
    And I should not see an "label[for=edit-field-media-topic-0-target-id].required" element
    And I should see an "input#edit-field-media-topic-0-target-id" element

  @api @javascript @nosuggest @skipped
  Scenario: Test file uploads.
    Given I am logged in as a user with the "create media" permission
    When I visit "media/add/image"
    And I attach the file "SampleJPGImage_50kbmb.jpg" to "Image"
    And I wait 80 seconds for AJAX to finish
    Then I should see the text "Alternative text"
    And I should see the text "Title"

  @api @nosuggest
  Scenario: The Audio media type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create media" permission
    When I visit "media/add/audio"
    Then I see field "Name"
    And I should see an "input#edit-name-0-value.required" element

    And I should see "Add a new file" in the "label[for=edit-field-media-file-0-upload]" element
    And I should see an "label[for=edit-field-media-file-0-upload].form-required" element
    And I should see an "input#edit-field-media-file-0-upload" element

    And I should see "Allowed types: mp3." in the "#edit-field-media-file-0--description" element

    And I should see "Length" in the "label[for=edit-field-media-length-0-value]" element
    And I should not see an "label[for=edit-field-media-length-0-value].form-required" element
    And I should see an "input#edit-field-media-length-0-value" element

    And I should see "Summary" in the "label[for=edit-field-media-summary-0-value]" element
    And I should not see an "label[for=edit-field-media-summary-0-value].form-required" element
    And I should see an "textarea#edit-field-media-summary-0-value" element

    And I should see "Transcript" in the "label[for=edit-field-media-transcript-0-value]" element
    And I should see an "label[for=edit-field-media-transcript-0-value].form-required" element
    And I should see an "textarea#edit-field-media-transcript-0-value" element

    And I should see "License Type" in the "label[for=edit-field-license-type]" element
    And I should see an "select#edit-field-license-type" element

    And I should see text matching "Metadata"

    And I should see "Audience" in the "label[for=edit-field-media-audience-0-target-id]" element
    And I should not see an "label[for=edit-field-media-audience-0-target-id].required" element
    And I should see an "input#edit-field-media-audience-0-target-id" element

    And I should see "Department" in the "label[for=edit-field-media-department-0-target-id]" element
    And I should see an "label[for=edit-field-media-department-0-target-id].form-required" element
    And I should see an "input#edit-field-media-department-0-target-id" element

    And I should see "Topic" in the "label[for=edit-field-media-topic-0-target-id]" element
    And I should not see an "label[for=edit-field-media-topic-0-target-id].required" element
    And I should see an "input#edit-field-media-topic-0-target-id" element

  @api @nosuggest
  Scenario: The Embedded Video media type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create media" permission
    When I visit "media/add/embedded_video"
    Then I see field "Name"
    And I should see an "input#edit-name-0-value.required" element

    Then I see field "Video URL"
    And I should see an "input#edit-field-media-video-embed-field-0-value.required" element

    And I should see "Length" in the "label[for=edit-field-media-length-0-value]" element
    And I should not see an "label[for=edit-field-media-length-0-value].form-required" element
    And I should see an "input#edit-field-media-length-0-value" element

    And I should see "Summary" in the "label[for=edit-field-media-summary-0-value]" element
    And I should not see an "label[for=edit-field-media-summary-0-value].form-required" element
    And I should see an "textarea#edit-field-media-summary-0-value" element

    And I should see "Transcript" in the "label[for=edit-field-media-transcript-0-value]" element
    And I should see an "label[for=edit-field-media-transcript-0-value].form-required" element
    And I should see an "textarea#edit-field-media-transcript-0-value" element

    And I should see "License Type" in the "label[for=edit-field-license-type]" element
    And I should see an "select#edit-field-license-type" element

    And I should see text matching "Metadata"

    And I should see "Audience" in the "label[for=edit-field-media-audience-0-target-id]" element
    And I should not see an "label[for=edit-field-media-audience-0-target-id].required" element
    And I should see an "input#edit-field-media-audience-0-target-id" element

    And I should see "Department" in the "label[for=edit-field-media-department-0-target-id]" element
    And I should see an "label[for=edit-field-media-department-0-target-id].form-required" element
    And I should see an "input#edit-field-media-department-0-target-id" element

    And I should see "Topic" in the "label[for=edit-field-media-topic-0-target-id]" element
    And I should not see an "label[for=edit-field-media-topic-0-target-id].required" element
    And I should see an "input#edit-field-media-topic-0-target-id" element

    And I should see "Is this video being live streamed?" in the "label[for=edit-field-is-streamed-value]" element
    And I should not see an "label[for=edit-field-is-streamed-value].form-required" element
    And I should see an "input#edit-field-is-streamed-value" element

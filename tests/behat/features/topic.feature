Feature: Check the Topic taxonomy

  Ensure Topic vocabulary and default terms exist.

  @api
  Scenario: Topic taxonomy exists
    Given vocabulary "topic" with name "Topic" exists
    And taxonomy term "Arts" from vocabulary "topic" exists
    And taxonomy term "Business" from vocabulary "topic" exists
    And taxonomy term "Education" from vocabulary "topic" exists

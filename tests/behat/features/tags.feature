@tide
Feature: Check the Tags taxonomy

  Ensure Tags vocabulary exist.

  @api
  Scenario: Tags taxonomy exists
    Given the vocabulary "tags" with the name "Tags" should exist

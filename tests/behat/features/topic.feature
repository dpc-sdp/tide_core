@tide
Feature: Check the Topic taxonomy

  Ensure Topic vocabulary exist.

  @api
  Scenario: Topic taxonomy exists
    Given the vocabulary "topic" with the name "Topic" should exist

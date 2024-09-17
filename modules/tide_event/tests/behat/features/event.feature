@tide
Feature: Check the Event taxonomy

  Ensure Event vocabulary exist.

  @api
  Scenario: Event taxonomy exists
    Given vocabulary "event" with name "Event Category" exists

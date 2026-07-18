@block @block_oerexchangemodqueue @javascript
Feature: Add the OER Exchange moderation queue block to the Dashboard
  In order to see pending moderation work at a glance
  As a manager
  I need to be able to add the block to my Dashboard

  Scenario: An admin (a manager) adds the block and it renders the queue summary
    Given I log in as "admin"
    And I visit "/my/"
    And I turn editing mode on
    When I add the "OER Exchange: moderation queue" block
    Then I should see "Open reports (0)" in the "OER Exchange: moderation queue" "block"

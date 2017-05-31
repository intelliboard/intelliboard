@local @local_intelliboard
Feature: Student Dashboard link on Site Homepage
  In order to access the Student Dashboard
  As a student
  I need to be able to see and click the IntelliBoard Student Dashboard link

  Scenario: Accessing Student Dashboard
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student1  | Student1 | student1@example.com |
    When I log in as "student1"
    And I am on homepage
    Then I should see "Learner Dashboard" in the ".block_navigation" "css_element"

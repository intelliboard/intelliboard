@local @local_intelliboard
Feature: Student Dashboard link on Site Homepage
  In order to access the Student Dashboard
  As a student
  I need to be able to see and click the IntelliBoard Student Dashboard link

  Scenario: Accessing Student Dashboard
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And I log in as "admin"
    And I create a course with:
      | Course full name | Course 1 |
      | Course short name | C1 |
    And I enrol "Teacher 1" user as "Teacher"
    And I enrol "Student 1" user as "Student"
    And I log out

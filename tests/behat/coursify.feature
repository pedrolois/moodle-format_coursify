@format @format_coursify
Feature: Learners see a Coursify course as tiles and open its sections
  In order to find my way around a course
  As a student
  I need to see each section as a tile, open it and come back

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format   | numsections | enablecompletion | activitymodal |
      | Course 1 | C1        | coursify | 3           | 1                | 0             |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Sam       | Student  |
      | teacher1 | Tina      | Teacher  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | section | name        | completion |
      | page     | C1     | 1       | First page  | 1          |
      | page     | C1     | 2       | Second page | 1          |

  Scenario: A student opens a section from its tile and goes back to all sections
    Given I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Course progress"
    And I should see "First page" in the ".coursify-continue" "css_element"
    And I should not see "Second page"
    When I click on "[data-number='2'] a" "css_element"
    Then I should see "Second page"
    And ".coursify-strip" "css_element" should exist
    When I click on "All sections" "link" in the ".coursify-sectionnav" "css_element"
    Then I should see "Course progress"

  Scenario: The tile shows the progress of its section
    Given I am on the "C1" "Course" page logged in as "student1"
    And I should see "Start" in the "[data-number='1']" "css_element"
    When I am on the "First page" "page activity" page
    And I press "Mark as done"
    And I am on the "C1" "Course" page
    Then I should see "Done" in the "[data-number='1']" "css_element"
    And I should see "Second page" in the ".coursify-continue" "css_element"

  Scenario: A teacher gives a tile its colour and a group
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    When I edit the section "1" and I fill the form with:
      | Tile colour    | Green  |
      | Starts a group | Unit 1 |
    And I turn editing mode off
    Then "[data-number='1'].coursify-tile--green" "css_element" should exist
    And I should see "Unit 1" in the ".coursify-group" "css_element"

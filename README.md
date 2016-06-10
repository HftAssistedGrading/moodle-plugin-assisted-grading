# moodle-plugin-assisted-grading
Moodle plugin for assisted manual grading of essay questions

This tool consists of two parts: The Moodle plugin itself and an external web service that processes student answers so the plugin can display them in an optimised order for manual grading.

The intention behind this plugin can be found in
Kiefer, C. and Pado, U. (2015). Freitextaufgaben in Online-Tests? Bewertung und Bewertungsunterstuetzung. HMD Praxis der Wirtschaftsinformatik, pages 1--12.

We studied the effectiveness of the answer sorting strategy in the plugin in
U. Pado and C. Kiefer, Short Answer Grading: When Sorting Helps and When it Doesn't. 4th NLP4CALL workshop at Nodalida, Vilnius, 2015.

## Installation
1. Download the Moodle plugin code and put the folder assistedgrading under mod/quiz/report/ in your Moodle installation to install it as a new Moodle plugin. Empty your Moodle cache to make sure everything is displayed correctly.

2.Download the packaged web service from http://www.hft-stuttgart.de/Studienbereiche/Informatik/Bachelor-Informatik/Einrichtungen/MMK-Labor/Projekt-HP-UP/index.html/en?set_language=en&cl=en . Deploy the file GA.war on your Apache Tomcat. (You can find the source code for the webservice at https://github.com/HftAssistedGrading/webservice-assisted-grading and the sources for the linguistic analysis at https://github.com/HftAssistedGrading/linguistic-analysis-assisted-grading).


## Configuration
After installation the plugin is available within a quiz as menu item 'Assisted Grading'/'Unterstützte Bewertung' beneath the menu item 'Results'/'Bewertung'. Choose a question to grade just as in the original Moodle free text grading view. At the top of the new page, you will need to specify the webservice address. See file AssistedGradingEinfuehrung.pdf for screen shots. If you deployed the webservice as /GA the base address may look like this: 'http://123.456.789.123:8080/GA/webresources/gradingassistant'.  Make sure to append '/webresources/gradingassistant' to your deploy path **without a trailing slash**.  If the webservice does not respond properly a message will be displayed. If you have trouble connecting to the webservice, check your Tomcat settings to determine the correct path to the webservice. 

## How to use
See file AssistedGradingEinfuehrung.pdf for a short introduction to using the plugin.

## Details
The plugin is a fork of the default quiz report plugin shipped with Moodle and extends it by interacting with a werbservice. Before the HTML for the view is generated it sends the quiz data to the werbservice and sorts the data from the response by a user selected criteria. The data consists of the particular question of the quiz, a reference answer set by the quiz creator and all student answers. The main objective of the werbservice is to calculate a score for each student answer in relation to the reference answer. This score can be used by the plugin for sorting the answers either ascending or descending.

## Technical details
For the communication between the plugin and the werbservice the data is en/decoded as JSON.

Here's an example JSON request:
```json
{
  "records": [
    {
      "id": 1,
      "question": "Question text",
      "referenceanswer": "Reference answer",
      "answer": "Student answer",
      "mark": 0,
      "max": 3,
      "numAttempts": 1,
      "min": 1,
      "sec": 14
    },
    {
      "id": 2,
      "question": "Question text",
      "referenceanswer": "Reference answer",
      "answer": "Other student answer",
      "mark": 0.5,
      "max": 3,
      "numAttempts": 1,
      "min": 1,
      "sec": 41
    }
  ]
}
```
The element "records" contains an array of all the student answers along with additional information. The field "id" is an internal id set by Moodles database backend, "answer" contains the student answer, "max" is the maximum mark, "min" and "sec" are the time in minutes and seconds it took for the student to answer that question.

A response from the webservice would look like this:
```json
[
  {
    "id": 1,
    "answer": "Student answer",
    "score": 0.0,
    "sanity_check": []
  },
  {
    "id": 2,
    "answer": "Other student answer",
    "score": 0.0,
    "sanity_check": []
  }
]
```  
The field "score" contains the calculated score in relation to given reference answer, "sanity_check" contains an array of id's with similar student answers to that particular answer. While "score" is solely used for sorting, "sanity_check" is used by the plugin to notify the user giving different marks for similar student answers.


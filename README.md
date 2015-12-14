# moodle-plugin-assisted-grading
Moodle plugin for assisted manual grading of essay questions

The intention behind this plugin and technical details can be found here:
Kiefer, C. and Pado, U. (2015). Freitextaufgaben in Online-Tests? Bewertung
und Bewertungsunterstützung. HMD Praxis der Wirtschaftsinformatik, pages
1–12.

## Installation
1. You can find the packaged web service here: http://www.connsulting.de/software/software.htm or here: http://www.nlpado.de/~ulrike/data.html (You may also look at the source code here: https://github.com/HftKiefer/webservice-assisted-grading and here: https://github.com/HftKiefer/linguistic-analysis-assisted-grading). Deploy the webservice GA.war on your Apache Tomcat.
2. Put the folder assistedgrading under mod/quiz/report/ in your Moodle installation to install it as a new Moodle plugin.

## Configuration
After installation the plugin is available within a quiz as separate menu item. You will need to specify the webservice address in the options by accessing a quiz report. The webservice base adress for example may look like this: 'http://123.456.789.123:8080/GA/webresources/gradingassistant'. If the webservice does not respond properly a message will be displayed.

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

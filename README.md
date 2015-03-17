# moodle-plugin-assisted-grading
Moodle plugin for assisted manual grading of essay questions

The intention behind this plugin and technical details can be found here:
Kiefer, C. and Pado, U. (2015). Freitextaufgaben in Online-Tests? Bewertung
und Bewertungsunterstützung. HMD Praxis der Wirtschaftsinformatik, pages
1–12.

Installation:

1. You can find the packaged web service here: XXX. Deploy the webservice GA.war on your Apache Tomcat.
2. Put the folder assistedgrading under server/moodle/mod/quiz/report in your Moodle installation to install it as a new Moodle plugin. You will need to specify the webservice base address during installation (for example 'http://123.456.789.123:8080/GA/webresources/gradingassistant')

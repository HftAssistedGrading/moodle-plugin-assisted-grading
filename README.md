# moodle-plugin-assisted-grading
Moodle plugin for assisted manual grading of essay questions

The intention behind this plugin and technical details can be found here:
Kiefer, C. and Pado, U. (2015). Freitextaufgaben in Online-Tests? Bewertung
und Bewertungsunterstützung. HMD Praxis der Wirtschaftsinformatik, pages
1–12.

Installation:

1. You can find the packaged web service here: http://www.connsulting.de/software/software.htm or here: http://www.nlpado.de/~ulrike/data.html (You may also look at the source code here: https://github.com/HftKiefer/webservice-assisted-grading and here: https://github.com/HftKiefer/linguistic-analysis-assisted-grading). Deploy the webservice GA.war on your Apache Tomcat.
2. Put the folder assistedgrading under server/moodle/mod/quiz/report in your Moodle installation to install it as a new Moodle plugin. You will need to specify the webservice address each time you want to use the grading assistant report, therefore you may want to change the default base address in report.php, line 34. The webservice base adress for example may look like this: 'http://123.456.789.123:8080/GA/webresources/gradingassistant'.

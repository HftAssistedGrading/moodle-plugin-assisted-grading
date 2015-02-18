# moodle-plugin-assisted-grading
Moodle plugin for assisted manual grading of essay questions

The intention behind this plugin and technical details can be found here:
Kiefer, C. and Pado, U. (2015). Freitextaufgaben in Online-Tests? Bewertung
und Bewertungsunterstützung. HMD Praxis der Wirtschaftsinformatik, pages
1–12.

Installation:
1. Deploy the webservice GA.war on Apache Tomcat
2. This step must be changed so that the webservice adress must be specified directly in Moodle when installing the Plugin.
For now: Change the following two constants in report.php so that they point to the webservice you just deployed:
 const WS_POST_ADDRESS = 'http://123.456.789.123:8080/GA/webresources/gradingassistant/post';
 const WS_PING_ADDRESS = 'http://123.456.789.123:8080/GA/webresources/gradingassistant/ping';
 Therefore, change 123.456.789.123 to the ip of the machine where the webservice is running. 
 3. Put the folder assistedgrading under server/moodle/mod/quiz/report in your Moodle installation to install it as a new Moodle plugin.
 
 
APIBundle
=========

Expose data from the YDLE interface

actions exposed :
- details of a room
- room listing
- saving data for a node
- adding log

The whole documentation is available on the Ydle's Wiki : http://wiki.ydle.fr

If you need help you can ask for on our forum : http://forum.ydle.fr


Adding log from the master.

To add log from the master, you have to call the url : /api/log/add and pass in POST the following parameters :

- message : a text message
- level : One of the following : INFO, ERROR, WARNING, DEBUG, CRITICAL
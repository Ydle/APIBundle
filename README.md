LICENCE 
========

This file is part of Ydle.

Ydle is free software: you can redistribute it and/or modify
it under the terms of the GNU  Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Ydle is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU  Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with Ydle.  If not, see <http://www.gnu.org/licenses/>.


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
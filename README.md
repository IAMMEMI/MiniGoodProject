Mini project 1.2
============
First project to learn symfony framework, using a goods table with CRUD operations

Minimum Requirements
--------------------

* Xampp with php7
* Composer

How to run
--------------
* **git clone** with the bitbucket link to clone the project inside a directory
* **composer install** to install all dependencies
* Enable pdo mysql driver within the php.ini configuration file, uncommenting its line
* Start xampp mysql server, with ./xampp startmysql
* Create a database named Miniproject
* Create a new account with all privileges on Miniproject
	username: MiniprojectAdmin
	password: admin
* ```php bin/console doctrine:schema:update --force``` to create "goods" table inside the db
* ```php bin/console server:run``` to run the php built-in server


NOTES FOR FRONTEND
---------------------------

### Get Goods 

##### **Request**
PARAMETER  |VALUE|Accepted Value|
--:|--|--|
**Title**  |GetGoods|
**URL**  |/goods|
**Query Param** |field|id, description, quantity, price;| 
||order|asc, desc;|
||value|**id and quantity**: *any int value greater than zero, max 11 digits*; **description**: *any string value, max 25 chars*; **price**: *any double value, greater than zero*;|
|**Accepted QueryStrings**|?coloumn=*fieldname*|All goods ordered by the specified coloumn. The order is ascending by default.|
||?coloumn=*fieldname*&order=*ordervalue*|All goods ordered by the fieldname, ordered in *ordervalue* mode if they are more than 20.|
||?field=*fieldname*&value=*fieldvalue*|All goods having the specified value in the specified field. The order is ascending by id.|
||?field=*fieldname*&value=*fieldvalue*&order=*ordervalue*|All goods having the specified value in the specified field, ordered by the fieldname, ordered in *ordervalue* mode if they are more than 20.|
||?field=*fieldname*&value=*fieldname*&coloumn=*fieldname*|All goods having the specified value in the specified field, ordered by *fieldname* specified in coloumn, ordered in ascending mode if they are more than 20.|
||?field=*fieldname*&value=*fieldname*&order=*ordervalue*&coloumn=*fieldname*|All goods having the specified value in the specified field, ordered by *fieldname* specified in coloumn, ordered in *ordervalue* mode if they are more than 20.|

**Method**  |GET|

##### **Example**
URL  |RESULT|
--:|--|
|/goods|All goods in the database, sorted in ascending order by id.
|/goods?coloumn=price|All goods in the database, sorted in ascending order by price.|
|/goods?order=desc&coloumn=quantity|All goods sorted in descending order according to the quantity field.|
|/goods?field=description&value=prova|All goods that contains the string "prova" in the description field, sorted in ascending order by id.|
|/goods?field=description&value=prova&order=desc|All goods that contains "prova" in the description field, ordered by description in descending mode.|
|/goods?field=description&value=prova&coloumn=price|All goods that contains "prova" in the description field, ordered by price in ascending mode.|
|/goods?field=description&value=prova&coloumn=price&order=desc|All goods that contains "prova" in the description field, ordered by price in descending mode.|
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


Communication protocol
---------------------------

## Introduction

## Data types

The following data types are used in these JSON objects:

- `struct`: *JSON structure containing other fields.*
- `string`: *free-form text.*
- `number`: *integer or real number.*
- `id`: *unique monotonically increasing numerical id.*

## API

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


##### **Response**
TYPE|WHEN|HEADER|BODY|
--:|:--:|:--:|:--:|
_200 (ok)_|The request has been successfully processed by the server and the goods are returned|Content-type : application/json|`Good[struct]` as defined in **Objects Section**|
_400 (Bad Request)_|The request is not correct, the type of error is defined inside it as a json object|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_500 (Internal Server Error)_|A problem has occurred in processing the request|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_503 (Service Unavailable)_|Server response out of time (timeout 30 seconds) request|NO_CONTENT|NO_CONTENT|

### Get Good
##### **Request**
PARAMETER  |VALUE|
--:|--|
**Title**  |GetGoodId|
**URL**  |/goods/{id}|
**Method**  |GET|

##### **Response**
TYPE|WHEN|HEADER|BODY|
--:|:--:|:--:|:--:|
_200 (ok)_|The request has been successfully processed by the server and the good requested is returned|Content-type : application/json|`Good[struct]` as defined in **Objects Section**|
_400 (Bad Request)_|The request is not correct, the type of error is defined inside it as a json object|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_500 (Internal Server Error)_|A problem has occurred in processing the request|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_503 (Service Unavailable)_|Server response out of time (timeout 30 seconds) request|NO_CONTENT|NO_CONTENT|

##### **Example**
***/goods/1***: A simple GET Request of the good with id 1. 
```javascript
{
    'id':1
    'desciption': 'coffee',
    'quantity': 95,
    'price': 4.31
}
```

### Create Good 

##### **Request**
PARAMETER  |VALUE|
--:|--|
**Title**  |CreateGood|
**URL**  |/goods|
**Method**  |POST|
**Header** |Content-Type: application/json|
**Body**  | `Good[struct]` object as defined in **Objects Section**|

##### **Response**
TYPE|WHEN|HEADER|BODY|
--:|:--:|:--:|:--:|
_200 (ok)_|The request has been successfully processed by the server|Content-type : application/json|`Good[struct]` as defined in **Objects Section**|
_400 (Bad Request)_|The request is not correct, the type of error is defined inside it as a json object|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_500 (Internal Server Error)_|A problem has occurred in processing the request|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_503 (Service Unavailable)_|Server response out of time (timeout 30 seconds) request|NO_CONTENT|NO_CONTENT|

### Put Good 
##### **Request**
PARAMETER  |VALUE|
--:|--|
**Title**  |PutGood|
**URL**  |/goods/{id}|
**Method**  |PUT|
**Header** |Content-Type: application/json|
**Body**  | `Good[struct]` object as defined in **Objects Section**|

##### **Response**
TYPE|WHEN|HEADER|BODY|
--:|:--:|:--:|:--:|
_200 (ok)_|The request has been successfully processed by the server|Content-type : application/json|`Good[struct]` as defined in **Objects Section**|
_400 (Bad Request)_|The request is not correct, the type of error is defined inside it as a json object|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_500 (Internal Server Error)_|A problem has occurred in processing the request|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_503 (Service Unavailable)_|Server response out of time (timeout 30 seconds) request|NO_CONTENT|NO_CONTENT|

##### **Example**
***/goods/1***: Changing quantity and price of the good with id 1. Notice that in the request the id is not sent. In fact, id is taken from the URL.
__Before__
```javascript
{
    'id':1
    'desciption': 'coffee',
    'quantity': 95,
    'price': 4.31
}
```

__After__
```javascript
{
    'id':1
    'desciption': 'coffee',
    'quantity': 950,
    'price': 3.50
}
```

### Delete Good 
##### **Request**
PARAMETER  |VALUE|
--:|--|
**Title**  |DeleteGood|
**URL**  |/goods/{id}|
**Method**  |DELETE|

##### **Response**
TYPE|WHEN|HEADER|BODY|
--:|:--:|:--:|:--:|
_200 (ok)_|The request has been successfully processed by the server |NO_CONTENT|NO_CONTENT|
_400 (Bad Request)_|The request is not correct, the type of error is defined inside it as a json object|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_500 (Internal Server Error)_|A problem has occurred in processing the request|Content-type : application/json|`ErrorResponse[struct]` as defined in **Objects Section**|
_503 (Service Unavailable)_|Server response out of time (timeout 30 seconds) request|NO_CONTENT|NO_CONTENT|
## Objects
The field type is specified inside square brackets.

##### Good Object
This JSON object is composed by the following fields:
- `id [OPTIONAL]`: the auto-increasing id of the good in the database;
- `description`: a brief description of the good, type `string`, max 25 characters;
- `quantity`: the available quantity of the good, type `number` integer, greater than zero, max 11 digits;
- `price`: the unitary price of the good, type `number` float, greater than zero.
 
__Example__
```javascript
{
    'id':2
    'desciption': 'advanced dishwasher',
    'quantity': 5,
    'price': 450.50
}
```

##### ErrorResponse Object
This JSON object is composed by the following fields:
- `type[string]`: a selection from the following types list:
    - `BAD_JSON`: specifies that a bad request response is caused by 
        malformed json;
    - `BAD_QUERY`: specifies that a bad request response is caused by
        a malformed queryString;
    - `DB_ERROR`: specifies that an internal server error response is caused
        by a database error;
    - `SERVER_ERROR`: specifies that an internal server error response is caused
        by a server problem, an administrator must be called;
    - `any HTTP STATUS CODE`:if any other error status code different from 400 and 500 is sent as as response, the type is the status code itself.
- `title[string]`: the error definition
- `description[string]`: the error description

__Examples__
```javascript
{
    'type': 'BAD_JSON',
    'title': 'Error processing the json object sent',
    'description': 'Description value too long, it must be less than 25 characters' 
}
```
```javascript
{
    'type': 404,
    'title': 'Exception occurred',
    'description': 'My Error says: No good found with id 4 with code 8' 
}
```
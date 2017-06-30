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

Generate the SSH keys :
------------------------
*```mkdir -p var/jwt``` 
*```openssl genrsa -out var/jwt/private.pem -aes256 4096```
*```openssl rsa -pubout -in var/jwt/private.pem -out var/jwt/public.pem```

Create a user:
------------------------
*```php bin/console fos:user:create```
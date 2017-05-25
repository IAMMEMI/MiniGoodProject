mini_project 1.1
============
First project to learn symfony framework, using a goods table with CRUD operations

Minimum Requirements
--------------------

* Xampp with php7
* Composer

How to run
--------------
* git clone with the bitbucket link to clone the project inside a directory
* composer install to install all dependencies
* Enable pdo mysql driver within the php.ini configuration file, uncommenting its line
* Start xampp mysql server, with ./xampp startmysql
* Create a database named Miniproject
* Create a new account with all privileges on Miniproject
	username: MiniprojectAdmin
	password: admin
* php bin/console doctrine:schema:update --force to create "goods" table inside the db
* php bin/console server:run to run the php built-in server

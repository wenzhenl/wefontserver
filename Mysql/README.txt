How to init the AlyssaDB in Mysql:

1. First log in to Mysql (with root);
2. source the .sql files in command line:

    mysql> source DropAlyssaDB.sql
    mysql> source CreateAlyssaDB.sql

Creates the Database named 'AlyssaDB' with 4 tables:
    User
    Font
    Glyph
    UserValidation

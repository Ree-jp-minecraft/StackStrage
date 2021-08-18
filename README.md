# StackStorage [![](https://poggit.pmmp.io/shield.dl.total/StackStorage)](https://poggit.pmmp.io/p/StackStorage)

Add simple virtual storage  
Mysql Version

# Setup

### Config

```yaml
database: mysql #Will support sqlite
host: localhost #Mysql address
dbName: StackStorage #Mysql db
user: StackStorage #Mysql user
pass: password #Mysql password
```

### Mysql

```mysql
CREATE DATABASE StackStorage;
CREATE USER StackStorage IDENTIFIED BY 'password';
GRANT ALL on StackStorage.* to StackStorage;
```

# How to use

You can open the storage at / stackstorage or / st

# Admin

/stackstorage [userName]  
*You can only open it if the player is on the server

# Feature

Use the familiar chest gui that opens the most while playing with Minecraft to get items in and out infinitely
Enchantments and durability can also be saved
![image](image/image1.png)

If you put more than 64 pieces of one item, only 64 pieces will be displayed in the storage, but the amount currently stored in the storage is written in the item description field.
The description field will be automatically deleted when the player puts it in the inventory, so it will not interfere
![image2](image/image2.png)

# Permission
`stackstorage.command.my`  
Permission to open my storage by command  
Default can be used by anyone

`stackstorage.command.user`  
Permission to open all user storage by command  
Default can be used by op

# Download

https://poggit.pmmp.io/p/StackStorage

# Note

- Data of versions below 0.1.0 cannot be inherited
- Mysql only

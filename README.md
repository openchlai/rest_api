# rest_api
#DATABASE SETUP
1.Create user commands.

    sudo mysql -e "create user nginx@localhost identified via unix_socket;"
2. Create database.
    sudo mysql -e "create database helpline"
3. Import database schema.
    sudo  mysql helpline < /usr/src/OpenChs/rest_api/uchl.sql
4. Grant authorization.
    grant select,insert on tower.* to nginx@localhost;
    grant update on helpline.auth to nginx@localhost;
    grant update on helpline.contact to nginx@localhost;
    grant update on helpline.kase to nginx@localhost;
    grant update on helpline.kase_activity to nginx@localhost;
    grant update on helpline.activity to nginx@localhost;
    grant update on helpline.disposition to nginx@localhost;
    grant delete on helpline.session to nginx@localhost;
    grant update on helpline.chan to nginx@localhost;
5. Database setup complete.
    exit;



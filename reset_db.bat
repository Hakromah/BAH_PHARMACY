@echo off
echo Killing mysqld...
taskkill /F /IM mysqld.exe
timeout /t 2 /nobreak >nul

echo Starting mysqld with skip-grant-tables...
start /b "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --skip-grant-tables --standalone
timeout /t 4 /nobreak >nul

echo Resetting root password...
"c:\xampp\mysql\bin\mysql.exe" -u root -P 3307 -e "FLUSH PRIVILEGES; ALTER USER 'root'@'localhost' IDENTIFIED BY '';"
echo Password reset done.

echo Killing mysqld again...
taskkill /F /IM mysqld.exe
timeout /t 2 /nobreak >nul

echo Starting mysqld normally...
start /b "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --standalone
echo Done.

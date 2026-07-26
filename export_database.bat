@echo off
echo Exporting attendance_db database...

REM Change this path to your XAMPP installation path if different
set XAMPP_PATH=C:\xampp

REM Export the database
"%XAMPP_PATH%\mysql\bin\mysqldump" -u root -p attendance_db > attendance_db_backup.sql

echo Database exported to attendance_db_backup.sql
echo You can now use this file to import data to PostgreSQL after deployment
pause
@echo off
set "JAVA_HOME=C:\Program Files\Java\jdk-21"
set "ANDROID_HOME=C:\Users\mcg45\AppData\Local\Android\Sdk"
set "ANDROID_SDK_ROOT=C:\Users\mcg45\AppData\Local\Android\Sdk"
cd /d "%~dp0"
"C:\Users\mcg45\.gradle\wrapper\dists\gradle-8.14.3-all\10utluxaxniiv4wxiphsi49nj\gradle-8.14.3\bin\gradle.bat" assembleDebug --stacktrace

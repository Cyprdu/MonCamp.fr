@echo off
setlocal enabledelayedexpansion

:: Répertoires
set "BASE_DIR=C:\xampp\htdocs\camps"
set "OUT_DIR=%BASE_DIR%\out"
set "OUT_FILE=%OUT_DIR%\liste_fichiers.txt"

:: Crée le dossier out s'il n'existe pas
if not exist "%OUT_DIR%" (
    mkdir "%OUT_DIR%"
)

:: Vide le fichier de sortie
echo. > "%OUT_FILE%"

:: Boucle sur tous les fichiers
for /r "%BASE_DIR%" %%F in (*) do (
    set "FILE=%%~nxF"
    set "EXT=%%~xF"

    rem Ignore certains types de fichiers
    if /I not "!EXT!"==".csv" if /I not "!EXT!"==".png" if /I not "!EXT!"==".html" if /I not "!EXT!"==".py" if /I not "!EXT!"==".bat" (
        rem Évite aussi d’inclure le fichier de sortie lui-même
        if /I not "%%F"=="%OUT_FILE%" (
            echo === FICHIER : %%F === >> "%OUT_FILE%"
            type "%%F" >> "%OUT_FILE%"
            echo. >> "%OUT_FILE%"
            echo. >> "%OUT_FILE%"
        )
    )
)

echo Export terminé. Fichier : %OUT_FILE%
pause

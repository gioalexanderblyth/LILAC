@echo off
REM === Change directory to your project ===
cd /d "C:\xampp\htdocs\LILAC"

REM === Pull latest changes first (avoid conflicts) ===
git pull origin main

REM === Stage all files in this folder (100+ files ok) ===
git add .

REM === Commit with a timestamp message ===
set datetime=%date% %time%
git commit -m "Batch commit on %datetime%"

REM === Push to GitHub ===
git push origin main

pause

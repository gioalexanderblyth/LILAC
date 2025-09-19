@echo off
set /p commitMessage="Enter your commit message: "

echo.
echo Adding all changes to the staging area...
git add .

echo.
echo Committing changes with message: "%commitMessage%"
git commit -m "%commitMessage%"

echo.
echo Pushing changes to GitHub...
git push

echo.
echo Done! Your changes have been uploaded to GitHub.
pause
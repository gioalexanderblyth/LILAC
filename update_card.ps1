$content = Get-Content 'events_activities.php' -Raw
$content = $content -replace 'class="h-32 bg-cover bg-center bg-no-repeat relative"', 'class="h-32 bg-cover bg-center bg-no-repeat relative" style="background-image: url(''img/sea-teacher-evaluation-meeting.jpg'')"'
$content | Set-Content 'events_activities.php'

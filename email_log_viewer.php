<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Log Viewer - LILAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Email Log Viewer</h1>
            <p class="text-gray-600 mb-4">This shows emails that would have been sent in local development mode.</p>
            
            <?php
            $emailLogFile = 'email_log.txt';
            
            if (file_exists($emailLogFile)) {
                $content = file_get_contents($emailLogFile);
                $emails = explode('---', $content);
                
                if (count($emails) > 1) {
                    echo '<div class="space-y-6">';
                    
                    foreach ($emails as $index => $email) {
                        $email = trim($email);
                        if (empty($email)) continue;
                        
                        echo '<div class="border border-gray-200 rounded-lg p-4 bg-gray-50">';
                        echo '<h3 class="text-lg font-semibold text-gray-800 mb-2">Email #' . ($index + 1) . '</h3>';
                        echo '<pre class="text-sm text-gray-700 whitespace-pre-wrap bg-white p-3 rounded border">';
                        echo htmlspecialchars($email);
                        echo '</pre>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    
                    echo '<div class="mt-6 flex gap-4">';
                    echo '<button onclick="clearLog()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Clear Log</button>';
                    echo '<button onclick="location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Refresh</button>';
                    echo '</div>';
                } else {
                    echo '<p class="text-gray-500">No emails in log yet.</p>';
                }
            } else {
                echo '<p class="text-gray-500">No email log file found. Try sharing some documents first.</p>';
            }
            ?>
        </div>
    </div>
    
    <script>
        function clearLog() {
            if (confirm('Are you sure you want to clear the email log?')) {
                fetch('clear_email_log.php', { method: 'POST' })
                    .then(() => location.reload());
            }
        }
    </script>
</body>
</html> 
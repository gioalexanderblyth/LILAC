<?php
// Installation script for LILAC System
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC System - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold text-center mb-6">LILAC System Installation</h1>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $host = $_POST['host'] ?? 'localhost';
                $username = $_POST['username'] ?? 'root';
                $password = $_POST['password'] ?? '';
                $dbname = $_POST['dbname'] ?? 'lilac_system';
                
                try {
                    // Connect to MySQL server (without selecting database)
                    $pdo = new PDO("mysql:host=$host", $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create database
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
                    $pdo->exec("USE `$dbname`");
                    
                    // Create tables
                    $sql = file_get_contents('sql/schema.sql');
                    $statements = explode(';', $sql);
                    
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if (!empty($statement) && !str_starts_with($statement, '--')) {
                            $pdo->exec($statement);
                        }
                    }
                    
                    // Update database config file
                    $configContent = "<?php
class Database {
    private \$host = '$host';
    private \$db_name = '$dbname';
    private \$username = '$username';
    private \$password = '$password';
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$this->conn = new PDO(\"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name, \$this->username, \$this->password);
            \$this->conn->exec(\"set names utf8\");
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException \$exception) {
            echo \"Connection error: \" . \$exception->getMessage();
        }

        return \$this->conn;
    }
}
?>";
                    
                    file_put_contents('config/database.php', $configContent);
                    
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <strong>Success!</strong> LILAC System has been installed successfully.
                            <br><br>
                            <strong>Database:</strong> ' . htmlspecialchars($dbname) . '<br>
                            <strong>Host:</strong> ' . htmlspecialchars($host) . '<br>
                            <br>
                            You can now access the <a href="funds.html" class="text-blue-600 underline">Funds page</a> to test the system.
                          </div>';
                    
                } catch (PDOException $e) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
                          </div>';
                }
            } else {
            ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Database Host:</label>
                    <input type="text" name="host" value="localhost" class="w-full border rounded px-3 py-2" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Database Username:</label>
                    <input type="text" name="username" value="root" class="w-full border rounded px-3 py-2" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Database Password:</label>
                    <input type="password" name="password" class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Database Name:</label>
                    <input type="text" name="dbname" value="lilac_system" class="w-full border rounded px-3 py-2" required>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Install LILAC System
                    </button>
                </div>
            </form>
            
            <div class="mt-6 p-4 bg-yellow-100 border border-yellow-400 rounded">
                <h3 class="font-bold text-yellow-800">Requirements:</h3>
                <ul class="list-disc list-inside text-yellow-700 mt-2">
                    <li>PHP 7.4 or higher</li>
                    <li>MySQL 5.7 or MariaDB 10.2 or higher</li>
                    <li>PDO MySQL extension enabled</li>
                </ul>
            </div>
            
            <?php } ?>
        </div>
    </div>
</body>
</html> 
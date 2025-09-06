<?php
echo "Installing PHPMailer...\n";

// Create vendor directory if it doesn't exist
if (!is_dir('vendor')) {
    mkdir('vendor', 0755, true);
    echo "Created vendor directory\n";
}

// Create PHPMailer directory structure
$phpmailerDir = 'vendor/phpmailer/phpmailer';
if (!is_dir($phpmailerDir)) {
    mkdir($phpmailerDir, 0755, true);
    echo "Created PHPMailer directory\n";
}

// Download PHPMailer files
$files = [
    'src/PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'src/SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
    'src/Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'
];

foreach ($files as $file => $url) {
    $filePath = $phpmailerDir . '/' . $file;
    $dirPath = dirname($filePath);
    
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
    }
    
    $content = file_get_contents($url);
    if ($content !== false) {
        file_put_contents($filePath, $content);
        echo "Downloaded: $file\n";
    } else {
        echo "Failed to download: $file\n";
    }
}

// Create autoload file
$autoloadContent = '<?php
// Simple autoloader for PHPMailer
spl_autoload_register(function ($class) {
    // PHPMailer namespace
    if (strpos($class, "PHPMailer\\PHPMailer\\") === 0) {
        $class = str_replace("PHPMailer\\PHPMailer\\", "", $class);
        $file = __DIR__ . "/phpmailer/phpmailer/src/" . $class . ".php";
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
?>';

file_put_contents('vendor/autoload.php', $autoloadContent);
echo "Created autoload.php\n";

echo "PHPMailer installation complete!\n";
echo "You can now configure email settings at email_config.php\n";
?> 
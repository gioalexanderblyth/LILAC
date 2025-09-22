<?php
require_once 'config/documents_config.php';

echo "Testing Rules Configuration Fix\n";
echo "=" . str_repeat("=", 40) . "\n\n";

// Test PHP configuration
echo "📋 PHP Configuration Rules:\n";
echo "-" . str_repeat("-", 30) . "\n";

foreach (DocumentsConfig::$CATEGORIES as $category => $config) {
    echo "Category: $category\n";
    echo "  Keywords: " . implode(', ', $config['keywords']) . "\n";
    echo "  Patterns: " . implode(', ', $config['patterns']) . "\n";
    echo "  Priority: " . $config['priority'] . "\n\n";
}

// Test JavaScript configuration output
echo "📋 JavaScript Configuration (for debugging):\n";
echo "-" . str_repeat("-", 40) . "\n";
echo "<script>\n";
echo "console.log('Testing DocumentsConfig categories:');\n";
echo "console.log(" . json_encode(DocumentsConfig::getCategoryRulesForJS()) . ");\n";
echo "console.log('Testing DocumentsConfig categoriesByPriority:');\n";
echo "console.log(" . json_encode(DocumentsConfig::getCategoriesByPriority()) . ");\n";
echo "</script>\n";

echo "\n✅ Test completed! Check browser console for JavaScript output.\n";
echo "\n🔍 To verify the fix:\n";
echo "1. Open documents.php in your browser\n";
echo "2. Click the 'Rules' button\n";
echo "3. You should now see all categories with their keywords\n";
echo "4. Check browser console for any errors\n";
?>

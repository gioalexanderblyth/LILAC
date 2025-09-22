<?php
require_once 'config/documents_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rules Debug</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Document Categorization Rules Debug</h1>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">PHP Configuration</h2>
            <div id="php-config" class="text-sm font-mono bg-gray-50 p-4 rounded"></div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">JavaScript Initialization</h2>
            <div id="js-init" class="text-sm font-mono bg-gray-50 p-4 rounded"></div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Rules Test</h2>
            <div id="rules-test" class="text-sm font-mono bg-gray-50 p-4 rounded"></div>
        </div>
    </div>

    <script>
        // Dynamic configuration from PHP
        const DocumentsConfig = {
            categories: <?php echo json_encode(DocumentsConfig::getCategoryRulesForJS()); ?>,
            categoriesByPriority: <?php echo json_encode(DocumentsConfig::getCategoriesByPriority()); ?>,
        };

        // Debug output
        document.getElementById('php-config').innerHTML = JSON.stringify(DocumentsConfig.categories, null, 2);

        // Test document categorizer initialization
        console.log('DocumentsConfig:', DocumentsConfig);

        // Simulate document categorizer initialization
        class DocumentCategorizer {
            constructor() {
                this.rules = {};
                this.initializeFromConfig();
            }

            initializeFromConfig() {
                if (typeof DocumentsConfig !== 'undefined' && DocumentsConfig.categories) {
                    this.rules = {};

                    Object.keys(DocumentsConfig.categories).forEach(category => {
                        const phpRules = DocumentsConfig.categories[category];

                        this.rules[category] = {
                            keywords: phpRules.keywords || [],
                            filePatterns: phpRules.patterns ? phpRules.patterns.map(pattern => new RegExp(pattern.replace(/\\\\/g, '\\'), 'i')) : [],
                            datePatterns: [],
                            priority: phpRules.priority || 10
                        };
                    });

                    console.log('DocumentCategorizer initialized with PHP config:', Object.keys(this.rules));
                    document.getElementById('js-init').innerHTML = '✅ Successfully initialized from PHP config\nCategories: ' + Object.keys(this.rules).join(', ');
                } else {
                    document.getElementById('js-init').innerHTML = '❌ Failed to initialize - PHP config not available';
                    console.error('PHP config not available');
                }
            }

            getCategories() {
                return Object.keys(this.rules);
            }

            getCategoryRules(category) {
                return this.rules[category] || null;
            }
        }

        // Create global instance
        window.documentCategorizer = new DocumentCategorizer();

        // Test the rules
        let testHtml = '';
        const categories = window.documentCategorizer.getCategories();
        testHtml += 'Categories found: ' + categories.length + '\n';
        testHtml += 'Categories: ' + categories.join(', ') + '\n\n';

        categories.forEach(category => {
            const rules = window.documentCategorizer.getCategoryRules(category);
            if (rules) {
                testHtml += category + ':\n';
                testHtml += '  Keywords: ' + rules.keywords.join(', ') + '\n';
                testHtml += '  Patterns: ' + rules.filePatterns.length + ' patterns\n';
                testHtml += '  Priority: ' + rules.priority + '\n\n';
            }
        });

        document.getElementById('rules-test').innerHTML = testHtml;
    </script>
</body>
</html>

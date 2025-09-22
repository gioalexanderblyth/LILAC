<?php
require_once 'config/documents_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOM Ready Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">DOM Ready Test</h1>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Test Results</h2>
            <div id="test-results" class="text-sm font-mono bg-gray-50 p-4 rounded"></div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Manual Test</h2>
            <button onclick="testDOMReady()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Test DOM Ready
            </button>
            <button onclick="testComponents()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-2">
                Test Components
            </button>
        </div>
    </div>

    <script>
        // Comprehensive DOM readiness check
        window.isDOMReady = function() {
            const readyState = document.readyState;
            const hasBody = !!document.body;
            const isNotLoading = readyState !== 'loading';

            const result = readyState === 'interactive' ||
                           readyState === 'complete' ||
                           (hasBody && readyState === undefined) ||
                           (hasBody && isNotLoading);

            console.log('DOM readiness check:', {
                readyState: readyState,
                hasBody: hasBody,
                isNotLoading: isNotLoading,
                result: result
            });

            return result;
        };

        // Test DOM ready function
        function testDOMReady() {
            const results = document.getElementById('test-results');
            const isReady = window.isDOMReady();

            results.innerHTML = `
                <div class="mb-2">
                    <strong>DOM Ready Test:</strong> ${isReady ? '✅ PASS' : '❌ FAIL'}
                </div>
                <div class="mb-2">
                    <strong>document.readyState:</strong> ${document.readyState}
                </div>
                <div class="mb-2">
                    <strong>document.body exists:</strong> ${!!document.body}
                </div>
                <div class="mb-2">
                    <strong>DOM Content Loaded:</strong> ${document.readyState !== 'loading'}
                </div>
                <div class="mb-2">
                    <strong>Current time:</strong> ${new Date().toLocaleTimeString()}
                </div>
            `;
        }

        // Test LILAC components
        function testComponents() {
            const results = document.getElementById('test-results');

            let componentStatus = '';

            if (window.lilacNotifications) {
                componentStatus += `<div class="mb-1"><strong>LILAC Notifications:</strong> ✅ Available</div>`;
                if (window.lilacNotifications.container) {
                    componentStatus += `<div class="mb-1 ml-4"><strong>Container:</strong> ✅ Created</div>`;
                } else {
                    componentStatus += `<div class="mb-1 ml-4"><strong>Container:</strong> ❌ Not created</div>`;
                }
            } else {
                componentStatus += `<div class="mb-1"><strong>LILAC Notifications:</strong> ❌ Not available</div>`;
            }

            if (window.lilacValidator) {
                componentStatus += `<div class="mb-1"><strong>LILAC Validator:</strong> ✅ Available</div>`;
            } else {
                componentStatus += `<div class="mb-1"><strong>LILAC Validator:</strong> ❌ Not available</div>`;
            }

            if (window.lilacLoading) {
                componentStatus += `<div class="mb-1"><strong>LILAC Loading:</strong> ✅ Available</div>`;
            } else {
                componentStatus += `<div class="mb-1"><strong>LILAC Loading:</strong> ❌ Not available</div>`;
            }

            if (window.lilacMobileNav) {
                componentStatus += `<div class="mb-1"><strong>LILAC Mobile Nav:</strong> ✅ Available</div>`;
            } else {
                componentStatus += `<div class="mb-1"><strong>LILAC Mobile Nav:</strong> ❌ Not available</div>`;
            }

            results.innerHTML = `
                <div class="mb-2">
                    <strong>Components Test:</strong>
                </div>
                ${componentStatus}
                <div class="mb-2">
                    <strong>Current time:</strong> ${new Date().toLocaleTimeString()}
                </div>
            `;
        }

        // Auto-test on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                testDOMReady();
                testComponents();
            }, 1000);
        });

        // Test immediately
        setTimeout(testDOMReady, 100);
        setTimeout(testComponents, 500);
    </script>
</body>
</html>

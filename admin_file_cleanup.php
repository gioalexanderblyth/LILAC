<?php
/**
 * Administrative File Cleanup Utility
 * 
 * This script helps maintain file system integrity by:
 * - Finding and removing orphaned files (files without database records)
 * - Identifying missing files (database records without files)
 * - Providing detailed reports on file system status
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'classes/Document.php';

// Check if running from command line or web
$isCommandLine = php_sapi_name() === 'cli';

if (!$isCommandLine) {
    echo "<h1>LILAC File Cleanup Utility</h1>\n";
    echo "<pre>\n";
}

try {
    $document = new Document();
    
    // Get action from command line arguments or GET parameters
    $action = 'check';
    $dryRun = true;
    
    if ($isCommandLine) {
        $options = getopt('', ['action:', 'dry-run:']);
        $action = $options['action'] ?? 'check';
        $dryRun = ($options['dry-run'] ?? 'true') === 'true';
    } else {
        $action = $_GET['action'] ?? 'check';
        $dryRun = ($_GET['dry_run'] ?? 'true') === 'true';
    }
    
    echo "=== LILAC File Cleanup Utility ===\n";
    echo "Action: $action\n";
    echo "Dry Run: " . ($dryRun ? 'Yes' : 'No') . "\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    switch ($action) {
        case 'check':
            echo "=== Checking for Orphaned Files ===\n";
            $orphanedReport = $document->cleanupOrphanedFiles(true);
            
            echo "Files checked: {$orphanedReport['total_files_checked']}\n";
            echo "Orphaned files found: {$orphanedReport['orphaned_files_found']}\n";
            
            if ($orphanedReport['orphaned_files_found'] > 0) {
                echo "\nOrphaned files:\n";
                foreach ($orphanedReport['orphaned_files'] as $file) {
                    $sizeKB = round($file['size'] / 1024, 2);
                    echo "  - {$file['filename']} ({$sizeKB} KB, modified: {$file['modified']})\n";
                }
            }
            
            if (!empty($orphanedReport['errors'])) {
                echo "\nErrors:\n";
                foreach ($orphanedReport['errors'] as $error) {
                    echo "  - $error\n";
                }
            }
            
            echo "\n=== Checking for Missing Files ===\n";
            $missingReport = $document->findMissingFiles();
            
            echo "Database records checked: {$missingReport['total_records_checked']}\n";
            echo "Missing files found: {$missingReport['missing_files_found']}\n";
            
            if ($missingReport['missing_files_found'] > 0) {
                echo "\nMissing files:\n";
                foreach ($missingReport['missing_files'] as $missing) {
                    echo "  - Document ID {$missing['document_id']}: {$missing['document_name']}\n";
                    echo "    Expected file: {$missing['expected_path']}\n";
                    echo "    Original filename: {$missing['filename']}\n\n";
                }
            }
            
            if (!empty($missingReport['errors'])) {
                echo "\nErrors:\n";
                foreach ($missingReport['errors'] as $error) {
                    echo "  - $error\n";
                }
            }
            
            echo "\n=== Summary ===\n";
            echo "Total orphaned files: {$orphanedReport['orphaned_files_found']}\n";
            echo "Total missing files: {$missingReport['missing_files_found']}\n";
            
            if ($orphanedReport['orphaned_files_found'] > 0) {
                echo "\nTo clean up orphaned files, run:\n";
                if ($isCommandLine) {
                    echo "  php admin_file_cleanup.php --action=cleanup --dry-run=false\n";
                } else {
                    echo "  " . $_SERVER['REQUEST_URI'] . "?action=cleanup&dry_run=false\n";
                }
            }
            break;
            
        case 'cleanup':
            echo "=== Cleaning Up Orphaned Files ===\n";
            
            if ($dryRun) {
                echo "DRY RUN MODE - No files will be deleted\n\n";
            } else {
                echo "LIVE MODE - Files will be permanently deleted\n\n";
            }
            
            $cleanupReport = $document->cleanupOrphanedFiles($dryRun);
            
            echo "Files checked: {$cleanupReport['total_files_checked']}\n";
            echo "Orphaned files found: {$cleanupReport['orphaned_files_found']}\n";
            
            if (!$dryRun) {
                echo "Files deleted: {$cleanupReport['files_deleted']}\n";
                echo "Deletion errors: {$cleanupReport['deletion_errors']}\n";
            }
            
            if ($cleanupReport['orphaned_files_found'] > 0) {
                echo "\nProcessed files:\n";
                foreach ($cleanupReport['orphaned_files'] as $file) {
                    $sizeKB = round($file['size'] / 1024, 2);
                    $status = $dryRun ? '[WOULD DELETE]' : '[DELETED]';
                    echo "  $status {$file['filename']} ({$sizeKB} KB)\n";
                }
            }
            
            if (!empty($cleanupReport['errors'])) {
                echo "\nErrors:\n";
                foreach ($cleanupReport['errors'] as $error) {
                    echo "  - $error\n";
                }
            }
            
            if ($dryRun && $cleanupReport['orphaned_files_found'] > 0) {
                echo "\nTo actually delete these files, run with dry_run=false\n";
            }
            break;
            
        case 'stats':
            echo "=== File System Statistics ===\n";
            
            // Get upload directory stats
            $uploadsDir = 'uploads/';
            if (is_dir($uploadsDir)) {
                $files = array_diff(scandir($uploadsDir), ['.', '..', 'index.html', '.htaccess']);
                $totalFiles = 0;
                $totalSize = 0;
                
                foreach ($files as $file) {
                    $filePath = $uploadsDir . $file;
                    if (is_file($filePath)) {
                        $totalFiles++;
                        $totalSize += filesize($filePath);
                    }
                }
                
                echo "Upload directory: $uploadsDir\n";
                echo "Total files: $totalFiles\n";
                echo "Total size: " . round($totalSize / 1024 / 1024, 2) . " MB\n";
            } else {
                echo "Upload directory not found: $uploadsDir\n";
            }
            
            // Get database stats
            $stats = $document->getStats();
            echo "\nDatabase records: {$stats['total']}\n";
            echo "Recent uploads (7 days): {$stats['recent']}\n";
            echo "Categories: {$stats['categories']}\n";
            
            // Quick integrity check
            $orphanedReport = $document->cleanupOrphanedFiles(true);
            $missingReport = $document->findMissingFiles();
            
            echo "\nIntegrity check:\n";
            echo "  Orphaned files: {$orphanedReport['orphaned_files_found']}\n";
            echo "  Missing files: {$missingReport['missing_files_found']}\n";
            
            $integrityStatus = ($orphanedReport['orphaned_files_found'] == 0 && $missingReport['missing_files_found'] == 0) ? 'GOOD' : 'ISSUES FOUND';
            echo "  Status: $integrityStatus\n";
            break;
            
        default:
            echo "Invalid action: $action\n\n";
            echo "Available actions:\n";
            echo "  check   - Check for orphaned and missing files\n";
            echo "  cleanup - Clean up orphaned files\n";
            echo "  stats   - Show file system statistics\n\n";
            
            echo "Usage:\n";
            if ($isCommandLine) {
                echo "  php admin_file_cleanup.php --action=check\n";
                echo "  php admin_file_cleanup.php --action=cleanup --dry-run=false\n";
                echo "  php admin_file_cleanup.php --action=stats\n";
            } else {
                echo "  {$_SERVER['REQUEST_URI']}?action=check\n";
                echo "  {$_SERVER['REQUEST_URI']}?action=cleanup&dry_run=false\n";
                echo "  {$_SERVER['REQUEST_URI']}?action=stats\n";
            }
            break;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (!$isCommandLine) {
        echo "</pre>\n";
    }
    exit(1);
}

if (!$isCommandLine) {
    echo "</pre>\n";
    echo "<hr>\n";
    echo "<p><a href='?action=check'>Check Files</a> | ";
    echo "<a href='?action=cleanup&dry_run=true'>Dry Run Cleanup</a> | ";
    echo "<a href='?action=cleanup&dry_run=false' onclick='return confirm(\"Are you sure you want to delete orphaned files?\")'>Clean Up Files</a> | ";
    echo "<a href='?action=stats'>Show Statistics</a></p>\n";
}

echo "\nCleanup utility completed.\n";
?> 
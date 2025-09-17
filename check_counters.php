<?php
/**
 * Check the updated document counters
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "=== DOCUMENT COUNTERS STATUS ===\n\n";
    
    // Get all award readiness data
    $stmt = $pdo->query("SELECT award_key, total_documents, total_events, total_items, 
                        readiness_percentage, is_ready, last_calculated 
                        FROM award_readiness ORDER BY award_key");
    $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($awards as $award) {
        echo "Award: " . strtoupper($award['award_key']) . "\n";
        echo "  Documents: " . $award['total_documents'] . "\n";
        echo "  Events: " . $award['total_events'] . "\n";
        echo "  Total Items: " . $award['total_items'] . "\n";
        echo "  Readiness: " . $award['readiness_percentage'] . "%\n";
        echo "  Ready: " . ($award['is_ready'] ? 'YES' : 'NO') . "\n";
        echo "  Last Updated: " . $award['last_calculated'] . "\n\n";
    }
    
    // Get totals
    $stmt = $pdo->query("SELECT 
        SUM(total_documents) as total_documents,
        SUM(total_events) as total_events,
        SUM(total_items) as total_items
        FROM award_readiness");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== TOTALS ===\n";
    echo "Total Documents: " . $totals['total_documents'] . "\n";
    echo "Total Events: " . $totals['total_events'] . "\n";
    echo "Total Items: " . $totals['total_items'] . "\n";
    
    // Test the awards API to confirm it's working
    echo "\n=== TESTING AWARDS API ===\n";
    $url = 'http://localhost/LILAC/api/awards.php?action=get_all';
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && isset($data['counts'])) {
        echo "Awards API Counts: " . json_encode($data['counts']) . "\n";
    } else {
        echo "Awards API Error: " . $response . "\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>

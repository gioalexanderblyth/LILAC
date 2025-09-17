<?php
/**
 * Test the updated CHED criteria
 */

echo "=== TESTING UPDATED CHED CRITERIA ===\n\n";

// Test content from your CHED file
$testContent = "COMPREHENSIVE INTERNATIONALIZATION PROGRAM
Multi-Award Criteria Initiative

This document combines multiple CHED award criteria to test comprehensive categorization:

Leadership Elements:
- Champion bold innovation in international programs
- Cultivate global citizens through education
- Nurture lifelong learning opportunities

Education Program Features:
- Expand access to global opportunities
- Foster collaborative innovation in curriculum
- Joint research initiatives

Emerging Leadership Components:
- Strategic and inclusive growth planning
- Empowerment of others through mentorship

Regional Office Functions:
- Comprehensive internationalization efforts
- Cooperation and collaboration with partners

Global Citizenship Goals:
- Ignite intercultural understanding
- Empower changemakers in the community

This comprehensive program demonstrates excellence across all CHED award criteria areas.";

$content = strtolower($testContent);

// Updated CHED criteria (20 total: 5+5+4+3+3)
$criteria = [
    'leadership' => [
        'Champion Bold Innovation',
        'Cultivate Global Citizens', 
        'Nurture Lifelong Learning',
        'Lead with Purpose',
        'Ethical and Inclusive Leadership'
    ],
    'education' => [
        'Expand Access to Global Opportunities',
        'Foster Collaborative Innovation',
        'Embrace Inclusivity and Beyond',
        'Drive Academic Excellence',
        'Build Sustainable Partnerships'
    ],
    'emerging' => [
        'Pioneer New Frontiers',
        'Adapt and Transform',
        'Build Capacity',
        'Create Impact'
    ],
    'regional' => [
        'Comprehensive Internationalization Efforts',
        'Cooperation and Collaboration',
        'Measurable Impact'
    ],
    'global' => [
        'Ignite Intercultural Understanding',
        'Empower Changemakers',
        'Cultivate Active Engagement'
    ]
];

$totalCriteria = 0;
$matches = [];

foreach ($criteria as $award => $awardCriteria) {
    $count = count($awardCriteria);
    $totalCriteria += $count;
    
    echo strtoupper($award) . " ($count criteria):\n";
    
    $awardMatches = [];
    foreach ($awardCriteria as $criterion) {
        if (strpos($content, strtolower($criterion)) !== false) {
            $awardMatches[] = $criterion;
            echo "  ✅ $criterion\n";
        } else {
            echo "  ❌ $criterion\n";
        }
    }
    
    if (!empty($awardMatches)) {
        $matches[$award] = $awardMatches;
    }
    
    echo "\n";
}

echo "TOTAL CRITERIA: $totalCriteria\n";
echo "MATCHES FOUND: " . count($matches) . " award categories\n\n";

foreach ($matches as $award => $awardMatches) {
    echo strtoupper($award) . " matches: " . count($awardMatches) . " criteria\n";
    foreach ($awardMatches as $match) {
        echo "  - $match\n";
    }
    echo "\n";
}
?>

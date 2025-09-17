<?php
/**
 * Check the current CHED criteria count and structure
 */

echo "=== CURRENT CHED CRITERIA COUNT ===\n\n";

// Current criteria from awards API
$currentCriteria = [
    'leadership' => [
        'champion bold innovation', 'cultivate global citizens', 'nurture lifelong learning',
        'lead with purpose', 'ethical and inclusive leadership', 'internationalization',
        'leadership', 'innovation', 'global citizens', 'lifelong learning', 'purpose',
        'ethical', 'inclusive', 'bold', 'champion', 'cultivate', 'nurture'
    ],
    'education' => [
        'expand access to global opportunities', 'foster collaborative innovation',
        'embrace inclusivity and beyond', 'international education', 'global opportunities',
        'collaborative innovation', 'inclusivity', 'education program', 'academic',
        'curriculum', 'international', 'global', 'opportunities', 'collaborative'
    ],
    'emerging' => [
        'innovation', 'strategic and inclusive growth', 'empowerment of others',
        'emerging leadership', 'strategic growth', 'inclusive growth', 'empowerment',
        'emerging', 'strategic', 'inclusive', 'growth', 'empower', 'mentoring'
    ],
    'regional' => [
        'comprehensive internationalization efforts', 'cooperation and collaboration',
        'measurable impact', 'regional office', 'internationalization efforts',
        'cooperation', 'collaboration', 'measurable impact', 'regional', 'office',
        'comprehensive', 'efforts', 'measurable', 'impact'
    ],
    'global' => [
        'ignite intercultural understanding', 'empower changemakers',
        'cultivate active engagement', 'global citizenship', 'intercultural understanding',
        'changemakers', 'active engagement', 'citizenship', 'intercultural',
        'understanding', 'changemakers', 'engagement', 'ignite', 'empower', 'cultivate'
    ]
];

$totalCount = 0;
foreach ($currentCriteria as $award => $criteria) {
    $count = count($criteria);
    $totalCount += $count;
    echo strtoupper($award) . ": $count criteria\n";
    foreach ($criteria as $i => $criterion) {
        echo "  " . ($i + 1) . ". $criterion\n";
    }
    echo "\n";
}

echo "TOTAL CURRENT COUNT: $totalCount criteria\n\n";

echo "=== CORRECT CHED CRITERIA (5+5+4+3+3=20) ===\n\n";

// Correct CHED criteria structure
$correctCriteria = [
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

$correctTotal = 0;
foreach ($correctCriteria as $award => $criteria) {
    $count = count($criteria);
    $correctTotal += $count;
    echo strtoupper($award) . ": $count criteria\n";
    foreach ($criteria as $i => $criterion) {
        echo "  " . ($i + 1) . ". $criterion\n";
    }
    echo "\n";
}

echo "CORRECT TOTAL COUNT: $correctTotal criteria\n";
echo "DIFFERENCE: " . ($totalCount - $correctTotal) . " extra keywords\n";
?>

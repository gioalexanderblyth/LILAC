<?php
/**
 * Database Schema Setup for Enhanced Document Management and Events System
 * Creates all necessary tables for awards, documents, events, and tracking
 * Compatible with older MySQL versions
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // 1. Enhanced Documents Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS enhanced_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_name VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        category VARCHAR(100) DEFAULT 'Awards',
        description TEXT,
        extracted_content LONGTEXT,
        award_assignments TEXT,
        analysis_data TEXT,
        manual_override BOOLEAN DEFAULT FALSE,
        override_assignments TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_upload_date (upload_date)
    )");
    
    // 2. Enhanced Events Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS enhanced_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        event_time TIME,
        location VARCHAR(255),
        image_path VARCHAR(500),
        file_path VARCHAR(500),
        file_type VARCHAR(100),
        extracted_content LONGTEXT,
        award_assignments TEXT,
        analysis_data TEXT,
        status ENUM('upcoming', 'completed', 'cancelled') DEFAULT 'upcoming',
        manual_override BOOLEAN DEFAULT FALSE,
        override_assignments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_event_date (event_date),
        INDEX idx_status (status)
    )");
    
    // 3. Award Types Configuration Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS award_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        award_key VARCHAR(50) UNIQUE NOT NULL,
        award_name VARCHAR(255) NOT NULL,
        criteria TEXT NOT NULL,
        keywords TEXT NOT NULL,
        threshold INT DEFAULT 2,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // 4. Document-Award Assignments Table (for many-to-many relationships)
    $pdo->exec("CREATE TABLE IF NOT EXISTS document_award_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_id INT NOT NULL,
        award_key VARCHAR(50) NOT NULL,
        confidence_score DECIMAL(3,2) NOT NULL,
        matched_keywords TEXT,
        satisfied_criteria TEXT,
        is_manual_override BOOLEAN DEFAULT FALSE,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (document_id) REFERENCES enhanced_documents(id) ON DELETE CASCADE,
        UNIQUE KEY unique_document_award (document_id, award_key),
        INDEX idx_award_key (award_key),
        INDEX idx_confidence_score (confidence_score)
    )");
    
    // 5. Event-Award Assignments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS event_award_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        award_key VARCHAR(50) NOT NULL,
        confidence_score DECIMAL(3,2) NOT NULL,
        matched_keywords TEXT,
        satisfied_criteria TEXT,
        is_manual_override BOOLEAN DEFAULT FALSE,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES enhanced_events(id) ON DELETE CASCADE,
        UNIQUE KEY unique_event_award (event_id, award_key),
        INDEX idx_award_key (award_key),
        INDEX idx_confidence_score (confidence_score)
    )");
    
    // 6. Award Readiness Tracking Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS award_readiness (
        id INT AUTO_INCREMENT PRIMARY KEY,
        award_key VARCHAR(50) NOT NULL,
        total_documents INT DEFAULT 0,
        total_events INT DEFAULT 0,
        total_items INT DEFAULT 0,
        satisfied_criteria TEXT,
        unsatisfied_criteria TEXT,
        readiness_percentage DECIMAL(5,2) DEFAULT 0.00,
        is_ready BOOLEAN DEFAULT FALSE,
        last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_award_readiness (award_key),
        INDEX idx_is_ready (is_ready),
        INDEX idx_readiness_percentage (readiness_percentage)
    )");
    
    // 7. Event Counters Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS event_counters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        counter_type ENUM('upcoming', 'completed', 'total') NOT NULL,
        count_value INT DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_counter_type (counter_type)
    )");
    
    // 8. File Processing Log Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS file_processing_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_id INT,
        file_type ENUM('document', 'event') NOT NULL,
        processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        extracted_content_length INT DEFAULT 0,
        processing_time_ms INT DEFAULT 0,
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        INDEX idx_file_type (file_type),
        INDEX idx_processing_status (processing_status),
        INDEX idx_created_at (created_at)
    )");
    
    // Insert default award types if they don't exist
    $awardTypes = [
        [
            'award_key' => 'leadership',
            'award_name' => 'Internationalization (IZN) Leadership Award',
            'criteria' => json_encode([
                'Champion Bold Innovation',
                'Cultivate Global Citizens', 
                'Nurture Lifelong Learning',
                'Lead with Purpose',
                'Ethical and Inclusive Leadership'
            ]),
            'keywords' => json_encode([
                'leadership', 'internationalization', 'global', 'strategic', 'vision', 'innovation',
                'partnership', 'collaboration', 'exchange', 'program', 'initiative', 'development',
                'international', 'cross-cultural', 'cultural', 'diversity', 'inclusion', 'mentorship',
                'faculty', 'student', 'research', 'academic', 'institutional', 'governance',
                'policy', 'framework', 'strategy', 'planning', 'management', 'administration',
                'award', 'recognition', 'excellence', 'achievement', 'impact', 'outcome'
            ]),
            'threshold' => 3
        ],
        [
            'award_key' => 'education',
            'award_name' => 'Outstanding International Education Program Award',
            'criteria' => json_encode([
                'Expand Access to Global Opportunities',
                'Foster Collaborative Innovation',
                'Embrace Inclusivity and Beyond'
            ]),
            'keywords' => json_encode([
                'education', 'program', 'curriculum', 'academic', 'course', 'learning',
                'teaching', 'pedagogy', 'instruction', 'training', 'development', 'skill',
                'knowledge', 'expertise', 'competency', 'qualification', 'certification',
                'degree', 'diploma', 'certificate', 'study', 'research', 'scholarship',
                'international', 'global', 'cross-cultural', 'multicultural', 'diverse',
                'inclusive', 'accessible', 'opportunity', 'access', 'expand', 'foster'
            ]),
            'threshold' => 2
        ],
        [
            'award_key' => 'emerging',
            'award_name' => 'Emerging Leadership Award',
            'criteria' => json_encode([
                'Innovation',
                'Strategic and Inclusive Growth',
                'Empowerment of Others'
            ]),
            'keywords' => json_encode([
                'emerging', 'new', 'innovative', 'pioneering', 'cutting-edge', 'advanced',
                'modern', 'contemporary', 'current', 'latest', 'recent', 'fresh',
                'breakthrough', 'revolutionary', 'transformative', 'disruptive', 'creative',
                'original', 'unique', 'novel', 'unprecedented', 'groundbreaking',
                'strategic', 'growth', 'development', 'expansion', 'scaling', 'scalable',
                'empowerment', 'empower', 'enable', 'facilitate', 'support', 'assist'
            ]),
            'threshold' => 2
        ],
        [
            'award_key' => 'regional',
            'award_name' => 'Best Regional Office for Internationalization Award',
            'criteria' => json_encode([
                'Comprehensive Internationalization Efforts',
                'Cooperation and Collaboration',
                'Measurable Impact'
            ]),
            'keywords' => json_encode([
                'regional', 'region', 'local', 'area', 'district', 'province', 'state',
                'territory', 'zone', 'office', 'branch', 'center', 'centre', 'hub',
                'headquarters', 'base', 'location', 'site', 'facility', 'institution',
                'comprehensive', 'complete', 'full', 'total', 'entire', 'whole',
                'cooperation', 'collaboration', 'partnership', 'alliance', 'network',
                'coordination', 'coordinate', 'manage', 'administration', 'governance',
                'impact', 'effect', 'result', 'outcome', 'achievement', 'success'
            ]),
            'threshold' => 2
        ],
        [
            'award_key' => 'citizenship',
            'award_name' => 'Global Citizenship Award',
            'criteria' => json_encode([
                'Ignite Intercultural Understanding',
                'Empower Changemakers',
                'Cultivate Active Engagement'
            ]),
            'keywords' => json_encode([
                'citizenship', 'citizen', 'community', 'society', 'social', 'civic',
                'public', 'civil', 'democratic', 'participatory', 'engagement', 'involvement',
                'participation', 'contribution', 'service', 'volunteer', 'activism',
                'advocacy', 'awareness', 'consciousness', 'understanding', 'knowledge',
                'cultural', 'intercultural', 'multicultural', 'diversity', 'inclusion',
                'tolerance', 'respect', 'acceptance', 'appreciation', 'celebration',
                'ignite', 'spark', 'inspire', 'motivate', 'encourage', 'stimulate'
            ]),
            'threshold' => 2
        ]
    ];
    
    foreach ($awardTypes as $awardType) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO award_types (award_key, award_name, criteria, keywords, threshold) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $awardType['award_key'],
            $awardType['award_name'],
            $awardType['criteria'],
            $awardType['keywords'],
            $awardType['threshold']
        ]);
    }
    
    // Initialize event counters
    $counterTypes = ['upcoming', 'completed', 'total'];
    foreach ($counterTypes as $type) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO event_counters (counter_type, count_value) VALUES (?, 0)");
        $stmt->execute([$type]);
    }
    
    // Initialize award readiness tracking
    foreach ($awardTypes as $awardType) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO award_readiness (award_key, satisfied_criteria, unsatisfied_criteria) VALUES (?, '[]', ?)");
        $stmt->execute([$awardType['award_key'], json_encode(json_decode($awardType['criteria']))]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database schema created successfully',
        'tables_created' => [
            'enhanced_documents',
            'enhanced_events', 
            'award_types',
            'document_award_assignments',
            'event_award_assignments',
            'award_readiness',
            'event_counters',
            'file_processing_log'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database schema creation failed: ' . $e->getMessage()
    ]);
}
?>

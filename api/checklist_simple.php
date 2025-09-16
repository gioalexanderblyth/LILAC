<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function respond($success, $data = []) {
    echo json_encode(['success' => $success] + $data);
    exit;
}

if ($action === 'get_readiness_summary') {
    // Load database connection
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Create tables if they don't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS award_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            award_key VARCHAR(50) UNIQUE NOT NULL,
            award_name VARCHAR(255) NOT NULL,
            criteria TEXT,
            keywords TEXT,
            threshold INT DEFAULT 5,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS award_readiness (
            id INT AUTO_INCREMENT PRIMARY KEY,
            award_key VARCHAR(50) UNIQUE NOT NULL,
            total_documents INT DEFAULT 0,
            total_events INT DEFAULT 0,
            total_items INT DEFAULT 0,
            satisfied_criteria TEXT,
            unsatisfied_criteria TEXT,
            readiness_percentage DECIMAL(5,2) DEFAULT 0,
            is_ready BOOLEAN DEFAULT FALSE,
            last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default award types if not exist
        $defaultAwards = [
            ['leadership', 'Internationalization (IZN) Leadership', '["Champion Bold Innovation", "Cultivate Global Citizens", "Nurture Lifelong Learning", "Lead with Purpose", "Ethical and Inclusive Leadership"]', '["leadership", "innovation", "global", "learning", "purpose", "ethical"]'],
            ['education', 'Outstanding International Education Program', '["Foster Collaborative Innovation", "Embrace Inclusivity and Beyond", "Drive Academic Excellence", "Build Sustainable Partnerships"]', '["education", "collaboration", "inclusivity", "excellence", "partnerships"]'],
            ['emerging', 'Emerging Internationalization', '["Pioneer New Frontiers", "Adapt and Transform", "Build Capacity", "Create Impact"]', '["emerging", "pioneer", "adapt", "transform", "capacity", "impact"]'],
            ['regional', 'Regional Internationalization', '["Comprehensive Internationalization Efforts", "Cooperation and Collaboration", "Measurable Impact"]', '["regional", "internationalization", "cooperation", "collaboration", "impact"]'],
            ['citizenship', 'Global Citizenship', '["Ignite Intercultural Understanding", "Empower Changemakers", "Cultivate Active Engagement"]', '["citizenship", "intercultural", "understanding", "changemakers", "engagement"]']
        ];
        
        foreach ($defaultAwards as $award) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO award_types (award_key, award_name, criteria, keywords) VALUES (?, ?, ?, ?)");
            $stmt->execute($award);
        }
        
        // Insert default readiness records if not exist
        foreach ($defaultAwards as $award) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO award_readiness (award_key) VALUES (?)");
            $stmt->execute([$award[0]]);
        }
        
        // Get summary data
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total_awards,
            SUM(CASE WHEN is_ready = 1 THEN 1 ELSE 0 END) as ready_awards,
            SUM(CASE WHEN is_ready = 0 THEN 1 ELSE 0 END) as incomplete_awards,
            SUM(total_documents) as total_documents,
            SUM(total_events) as total_events,
            SUM(total_items) as total_content
            FROM award_readiness");
        
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT award_key, total_documents, total_events, total_items, 
                            readiness_percentage, is_ready, satisfied_criteria 
                            FROM award_readiness ORDER BY award_key");
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        respond(true, [
            'summary' => $summary,
            'totals' => $totals
        ]);
        
    } catch (Exception $e) {
        respond(false, ['message' => 'Error: ' . $e->getMessage()]);
    }
}

respond(false, ['message' => 'Invalid action']);
?>

<?php
/**
 * Dynamic Documents Configuration
 * Centralized configuration for all hardcoded values in documents system
 */

class DocumentsConfig {
    
    // User roles and permissions
    public static $USER_ROLES = [
        'admin' => [
            'name' => 'Administrator',
            'permissions' => ['read', 'write', 'delete', 'manage_users', 'manage_categories'],
            'level' => 4
        ],
        'manager' => [
            'name' => 'Manager', 
            'permissions' => ['read', 'write', 'delete', 'manage_categories'],
            'level' => 3
        ],
        'coordinator' => [
            'name' => 'Coordinator',
            'permissions' => ['read', 'write'],
            'level' => 2
        ],
        'user' => [
            'name' => 'User',
            'permissions' => ['read'],
            'level' => 1
        ]
    ];
    
    // Default session configuration
    public static $DEFAULT_SESSION = [
        'user_id' => 'demo_user',
        'user_role' => 'admin',
        'csrf_token_length' => 32
    ];
    
    // Document categories with dynamic detection rules
    public static $CATEGORIES = [
        'MOUs & MOAs' => [
            'keywords' => ['MOU', 'MOA', 'Memorandum of Understanding', 'Agreement', 'Partnership', 'Renewal', 'KUMA-MOU', 'collaboration', 'cooperation', 'institution', 'university', 'college', 'student exchange', 'international', 'global', 'research collaboration'],
            'patterns' => ['/\b(MOU|MOA|Memorandum of Understanding|Agreement|Partnership|Renewal|KUMA-MOU)\b/i'],
            'priority' => 1,
            'description' => 'Memorandums of Understanding and Agreements'
        ],
        'Registrar Files' => [
            'keywords' => ['Registrar', 'Enrollment', 'Transcript', 'TOR', 'Certificate', 'COR', 'Student Record', 'GWA', 'Grades'],
            'patterns' => ['/\b(Registrar|Enrollment|Transcript|TOR|Certificate|COR|Student\s*Record|GWA|Grades)\b/i'],
            'priority' => 2,
            'description' => 'Student records and academic documents'
        ],
        'Templates' => [
            'keywords' => ['Template', 'Form', 'Admission', 'Application', 'Registration', 'Checklist', 'Request'],
            'patterns' => ['/\b(Template|Form|Admission|Application|Registration|Checklist|Request)\b/i'],
            'priority' => 3,
            'description' => 'Forms, templates, and application documents'
        ],
        'Events & Activities' => [
            'keywords' => ['Conference', 'Seminar', 'Workshop', 'Meeting', 'Symposium', 'Event', 'Activity', 'Program', 'Training', 'Session'],
            'patterns' => ['/\b(Conference|Seminar|Workshop|Meeting|Symposium|Event|Activity|Program|Training|Session)\b/i'],
            'priority' => 4,
            'description' => 'Event documentation and activity records'
        ],
        'Awards' => [
            'keywords' => ['Award', 'Recognition', 'Certificate', 'Certification', 'Accreditation', 'Achievement', 'Honor', 'Distinction', 'Excellence', 'Quality', 'Standard', 'Compliance'],
            'patterns' => ['/\b(Award|Recognition|Certificate|Certification|Accreditation|Achievement|Honor|Distinction|Excellence|Quality|Standard|Compliance)\b/i'],
            'priority' => 5,
            'description' => 'Awards, recognitions, and certifications'
        ]
    ];
    
    // File type configurations
    public static $FILE_TYPES = [
        'pdf' => ['extensions' => ['pdf'], 'mime_types' => ['application/pdf'], 'max_size' => '50MB'],
        'docx' => ['extensions' => ['docx'], 'mime_types' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 'max_size' => '25MB'],
        'doc' => ['extensions' => ['doc'], 'mime_types' => ['application/msword'], 'max_size' => '25MB'],
        'txt' => ['extensions' => ['txt'], 'mime_types' => ['text/plain'], 'max_size' => '10MB'],
        'rtf' => ['extensions' => ['rtf'], 'mime_types' => ['application/rtf', 'text/rtf'], 'max_size' => '15MB'],
        'csv' => ['extensions' => ['csv'], 'mime_types' => ['text/csv', 'application/csv'], 'max_size' => '5MB'],
        'html' => ['extensions' => ['html', 'htm'], 'mime_types' => ['text/html'], 'max_size' => '10MB'],
        'xml' => ['extensions' => ['xml'], 'mime_types' => ['text/xml', 'application/xml'], 'max_size' => '10MB'],
        'json' => ['extensions' => ['json'], 'mime_types' => ['application/json', 'text/json'], 'max_size' => '5MB']
    ];
    
    // Filter and view configurations
    public static $FILTERS = [
        'file_groups' => ['all', 'recent', 'favorites', 'shared', 'deleted'],
        'sort_options' => [
            'upload_date' => 'Upload Date',
            'name' => 'File Name', 
            'size' => 'File Size',
            'category' => 'Category',
            'type' => 'File Type'
        ],
        'sort_orders' => ['ASC', 'DESC'],
        'views' => ['all', 'my', 'favorites', 'sharing', 'deleted'],
        'view_modes' => ['list', 'grid']
    ];
    
    // Pagination configuration
    public static $PAGINATION = [
        'default_limit' => 20,
        'limits' => [10, 20, 50, 100],
        'max_limit' => 100
    ];
    
    // UI Configuration
    public static $UI = [
        'default_view_mode' => 'list',
        'default_sort_by' => 'upload_date',
        'default_sort_order' => 'DESC',
        'default_file_group' => 'all',
        'date_format' => 'en-US',
        'date_options' => [
            'weekday' => 'short',
            'month' => 'short', 
            'day' => 'numeric',
            'year' => 'numeric'
        ]
    ];
    
    // Security configuration
    public static $SECURITY = [
        'csrf_token_length' => 32,
        'session_timeout' => 3600, // 1 hour
        'max_upload_attempts' => 3,
        'allowed_file_extensions' => ['pdf', 'docx', 'doc', 'txt', 'rtf', 'csv', 'html', 'htm', 'xml', 'json']
    ];
    
    /**
     * Get category detection rules for JavaScript
     */
    public static function getCategoryRulesForJS() {
        $rules = [];
        foreach (self::$CATEGORIES as $category => $config) {
            $rules[$category] = [
                'keywords' => $config['keywords'],
                'patterns' => $config['patterns'],
                'priority' => $config['priority']
            ];
        }
        return $rules;
    }
    
    /**
     * Get allowed roles for current user context
     */
    public static function getAllowedRoles($userRole = null) {
        if (!$userRole) {
            return array_keys(self::$USER_ROLES);
        }
        
        $userLevel = self::$USER_ROLES[$userRole]['level'] ?? 1;
        $allowedRoles = [];
        
        foreach (self::$USER_ROLES as $role => $config) {
            if ($config['level'] <= $userLevel) {
                $allowedRoles[] = $role;
            }
        }
        
        return $allowedRoles;
    }
    
    /**
     * Check if user has permission
     */
    public static function hasPermission($userRole, $permission) {
        $userConfig = self::$USER_ROLES[$userRole] ?? null;
        if (!$userConfig) {
            return false;
        }
        
        return in_array($permission, $userConfig['permissions']);
    }
    
    /**
     * Get category by priority (for dynamic ordering)
     */
    public static function getCategoriesByPriority() {
        $categories = self::$CATEGORIES;
        uasort($categories, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return array_keys($categories);
    }
}
?>

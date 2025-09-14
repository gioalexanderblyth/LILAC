<?php
/**
 * Location Analysis System
 * Extracts location information from text content and images using AI-powered analysis
 */

class LocationAnalyzer {
    private $pdo;
    
    // Common location patterns and keywords
    private $locationPatterns = [
        // Venue types
        'venue_types' => [
            'conference center', 'convention center', 'auditorium', 'hall', 'theater', 'theatre',
            'university', 'college', 'campus', 'building', 'center', 'centre', 'institute',
            'hotel', 'resort', 'club', 'restaurant', 'cafe', 'library', 'museum',
            'stadium', 'arena', 'gymnasium', 'gym', 'room', 'classroom', 'lab', 'laboratory'
        ],
        
        // Location indicators
        'location_indicators' => [
            'at', 'in', 'on', 'located at', 'held at', 'takes place at', 'venue',
            'address', 'location', 'place', 'site', 'where', 'hosted by', 'hosted at'
        ],
        
        // Geographic terms
        'geographic_terms' => [
            'street', 'avenue', 'boulevard', 'road', 'drive', 'lane', 'way',
            'city', 'town', 'village', 'district', 'area', 'region', 'state',
            'province', 'country', 'nation', 'continent', 'island', 'peninsula'
        ],
        
        // Common venue names
        'common_venues' => [
            'main hall', 'grand ballroom', 'conference room', 'meeting room',
            'lecture hall', 'seminar room', 'workshop room', 'exhibition hall',
            'gallery', 'lobby', 'foyer', 'atrium', 'courtyard', 'garden',
            'outdoor', 'indoor', 'virtual', 'online', 'zoom', 'teams', 'webinar'
        ]
    ];
    
    // Location extraction patterns (focused on exact venue extraction only)
    private $extractionPatterns = [
        // PRIMARY VENUE PATTERNS (highest priority - exact venue mentions)
        
        // "held at [venue]" - most direct venue mention
        '/\b(?:held at|takes place at)\s+([A-Za-z\s]+(?:Ballroom|Hall|Auditorium|Center|Centre|Hotel|Resort|Convention|Conference|Building|Room|Theater|Theatre|University|College|Institute|School)[A-Za-z\s]*(?:,\s*[A-Za-z\s]+,\s*[A-Za-z\s]+)?)/i',
        
        // "at [venue]" - direct venue mention
        '/\b(?:at)\s+([A-Za-z\s]+(?:Ballroom|Hall|Auditorium|Center|Centre|Hotel|Resort|Convention|Conference|Building|Room|Theater|Theatre|University|College|Institute|School)[A-Za-z\s]*(?:,\s*[A-Za-z\s]+,\s*[A-Za-z\s]+)?)/i',
        
        // Complete venue with city and country
        '/\b([A-Za-z\s]+(?:Ballroom|Hall|Auditorium|Center|Centre|Hotel|Resort|Convention|Conference|Building|Room|Theater|Theatre|University|College|Institute|School)[A-Za-z\s]+),\s*([A-Za-z\s]+),\s*(Indonesia|Philippines|Thailand|Vietnam|Japan|Malaysia|Singapore|Cambodia|Laos|Myanmar|Brunei)/i',
        
        // Host institution with complete location
        '/\b(?:host institution is|hosted by)\s+([A-Za-z\s]+(?:Universitas|University|College|Institute|School)[A-Za-z\s]+),\s*(?:located in|in)\s*([A-Za-z\s]+),\s*(Indonesia|Philippines|Thailand|Vietnam|Japan|Malaysia|Singapore|Cambodia|Laos|Myanmar|Brunei)/i',
        
        // University/Institution with complete location
        '/\b([A-Za-z\s]+(?:Universitas|University|College|Institute|School)[A-Za-z\s]+),\s*([A-Za-z\s]+),\s*(Indonesia|Philippines|Thailand|Vietnam|Japan|Malaysia|Singapore|Cambodia|Laos|Myanmar|Brunei)/i',
        
        // Venue with city only
        '/\b([A-Za-z\s]+(?:Ballroom|Hall|Auditorium|Center|Centre|Hotel|Resort|Convention|Conference|Building|Room|Theater|Theatre)[A-Za-z\s]+),\s*([A-Za-z\s]+(?:City|Town|Jaya|Malang|Jakarta|Manila|Bangkok|Ho Chi Minh|Tokyo|Kuala Lumpur|Singapore|Phnom Penh|Vientiane|Yangon|Bandar Seri Begawan))/i',
        
        // University with city only
        '/\b([A-Za-z\s]+(?:Universitas|University|College|Institute|School)[A-Za-z\s]+),\s*([A-Za-z\s]+(?:City|Town|Jaya|Malang|Jakarta|Manila|Bangkok|Ho Chi Minh|Tokyo|Kuala Lumpur|Singapore|Phnom Penh|Vientiane|Yangon|Bandar Seri Begawan))/i',
        
        // SECONDARY PATTERNS (lower priority)
        
        // Specific venue names only
        '/\b([A-Za-z\s]+(?:Ballroom|Hall|Auditorium|Center|Centre|Hotel|Resort|Convention|Conference|Building|Room|Theater|Theatre)[A-Za-z\s]+)/i',
        
        // University/Institution names only
        '/\b([A-Za-z\s]+(?:Universitas|University|College|Institute|School)[A-Za-z\s]+)/i',
        
        // City, Country patterns (fallback)
        '/\b([A-Za-z\s]+),\s*(Indonesia|Philippines|Thailand|Vietnam|Japan|Malaysia|Singapore|Cambodia|Laos|Myanmar|Brunei)\b/i',
        
        // Virtual/Online patterns
        '/\b(?:virtual|online|zoom|teams|webinar|web-based|remote|digital)\b/i'
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Extract ONLY the exact event location from text content
     * Returns only venue, building, city, country - no summaries or context
     */
    public function extractLocationFromText($text) {
        if (empty($text)) {
            return null;
        }
        
        $locations = [];
        
        // Clean and normalize text
        $text = $this->cleanText($text);
        
        // Check for virtual/online events first
        if (preg_match('/\b(?:virtual|online|zoom|teams|webinar|web-based|remote|digital)\b/i', $text)) {
            return [
                'location' => 'Virtual/Online Event',
                'confidence' => 0.9
            ];
        }
        
        // Try each extraction pattern in order of priority
        foreach ($this->extractionPatterns as $patternIndex => $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    // Handle patterns with multiple capture groups
                    if (count($match) > 3 && isset($match[3])) {
                        // Complete venue with city and country
                        $location = trim($match[1] . ', ' . $match[2] . ', ' . $match[3]);
                    } elseif (count($match) > 2 && isset($match[2])) {
                        // Venue with city
                        $location = trim($match[1] . ', ' . $match[2]);
                    } else {
                        // Single venue name
                        $location = trim($match[1] ?? $match[0]);
                    }
                    
                    // Clean the location to remove unwanted text
                    $location = $this->cleanLocation($location);
                    
                    // Validate that it's a proper venue/location
                    if ($this->isValidVenueLocation($location)) {
                        $locations[] = [
                            'location' => $location,
                            'priority' => $patternIndex, // Lower index = higher priority
                            'confidence' => $this->calculateVenueConfidence($location, $text)
                        ];
                    }
                }
            }
        }
        
        // Sort by priority (lower index first) then by confidence
        if (!empty($locations)) {
            usort($locations, function($a, $b) {
                if ($a['priority'] === $b['priority']) {
                    return $b['confidence'] <=> $a['confidence'];
                }
                return $a['priority'] <=> $b['priority'];
            });
            
            // Return only the best match - no alternatives
            return [
                'location' => $locations[0]['location'],
                'confidence' => $locations[0]['confidence']
            ];
        }
        
        return null;
    }
    
    /**
     * Extract location from image using OCR and pattern analysis
     */
    public function extractLocationFromImage($imagePath) {
        if (!file_exists($imagePath)) {
            return null;
        }
        
        // For now, we'll use a simple approach
        // In a full implementation, you'd use OCR libraries like Tesseract
        // or cloud services like Google Vision API, AWS Textract, etc.
        
        $imageText = $this->performOCR($imagePath);
        
        if ($imageText) {
            return $this->extractLocationFromText($imageText);
        }
        
        return null;
    }
    
    /**
     * Analyze event content and suggest location
     */
    public function analyzeEventLocation($title, $description, $imagePath = null) {
        $analysis = [
            'suggested_location' => null,
            'confidence' => 0,
            'sources' => [],
            'alternatives' => []
        ];
        
        // Combine title and description for analysis
        $combinedText = $title . ' ' . $description;
        
        // Extract from text
        $textLocation = $this->extractLocationFromText($combinedText);
        if ($textLocation) {
            $analysis['sources'][] = 'text_analysis';
            $analysis['alternatives'][] = $textLocation;
            
            if ($textLocation['confidence'] > $analysis['confidence']) {
                $analysis['suggested_location'] = $textLocation['location'];
                $analysis['confidence'] = $textLocation['confidence'];
            }
        }
        
        // Extract from image if provided
        if ($imagePath && file_exists($imagePath)) {
            $imageLocation = $this->extractLocationFromImage($imagePath);
            if ($imageLocation) {
                $analysis['sources'][] = 'image_analysis';
                $analysis['alternatives'][] = $imageLocation;
                
                if ($imageLocation['confidence'] > $analysis['confidence']) {
                    $analysis['suggested_location'] = $imageLocation['location'];
                    $analysis['confidence'] = $imageLocation['confidence'];
                }
            }
        }
        
        // Apply smart suggestions based on content
        $smartSuggestion = $this->getSmartLocationSuggestion($title, $description);
        if ($smartSuggestion) {
            $analysis['sources'][] = 'smart_suggestion';
            $analysis['alternatives'][] = $smartSuggestion;
            
            if ($smartSuggestion['confidence'] > $analysis['confidence']) {
                $analysis['suggested_location'] = $smartSuggestion['location'];
                $analysis['confidence'] = $smartSuggestion['confidence'];
            }
        }
        
        return $analysis;
    }
    
    /**
     * Get smart location suggestions based on content analysis
     */
    private function getSmartLocationSuggestion($title, $description) {
        $text = strtolower($title . ' ' . $description);
        
        // Check for virtual/online events
        if (preg_match('/\b(?:virtual|online|zoom|teams|webinar|web-based|remote|digital|live stream|streaming)\b/', $text)) {
            return [
                'location' => 'Virtual/Online Event',
                'confidence' => 0.9
            ];
        }
        
        // Check for specific university mentions first
        if (preg_match('/\b(?:universitas|university|college|school|institute)\s+([a-z\s]+)(?:,\s*([a-z\s]+),\s*(?:indonesia|philippines|thailand|vietnam|japan|malaysia|singapore|cambodia|laos|myanmar|brunei))?/i', $text, $matches)) {
            $university = ucwords(trim($matches[1]));
            $city = isset($matches[2]) ? ucwords(trim($matches[2])) : '';
            $country = isset($matches[3]) ? ucwords(trim($matches[3])) : '';
            
            if ($city && $country) {
                return [
                    'location' => "$university, $city, $country",
                    'confidence' => 0.9
                ];
            } elseif ($city) {
                return [
                    'location' => "$university, $city",
                    'confidence' => 0.8
                ];
            } else {
                return [
                    'location' => $university,
                    'confidence' => 0.7
                ];
            }
        }
        
        // Check for host institution mentions
        if (preg_match('/\b(?:host institution is|hosted by)\s+([a-z\s]+(?:universitas|university|college|school|institute)[a-z\s]*)/i', $text, $matches)) {
            $institution = ucwords(trim($matches[1]));
            return [
                'location' => $institution,
                'confidence' => 0.8
            ];
        }
        
        // Check for university/campus events (generic fallback)
        if (preg_match('/\b(?:university|campus|college|school|institute|academic)\b/', $text)) {
            return [
                'location' => 'University Campus',
                'confidence' => 0.6
            ];
        }
        
        // Check for conference events
        if (preg_match('/\b(?:conference|convention|summit|symposium|workshop|seminar)\b/', $text)) {
            return [
                'location' => 'Conference Center',
                'confidence' => 0.6
            ];
        }
        
        // Check for cultural events
        if (preg_match('/\b(?:cultural|art|museum|gallery|theater|theatre|performance|exhibition)\b/', $text)) {
            return [
                'location' => 'Cultural Venue',
                'confidence' => 0.6
            ];
        }
        
        return null;
    }
    
    /**
     * Clean and normalize text for analysis
     */
    private function cleanText($text) {
        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Remove common prefixes that might interfere
        $text = preg_replace('/^(?:event|meeting|conference|workshop|seminar):\s*/i', '', $text);
        
        return $text;
    }
    
    /**
     * Clean and improve extracted venue location (focused on exact venue extraction)
     */
    private function cleanLocation($location) {
        // Remove common prefixes that indicate location context
        $location = preg_replace('/^(?:at|in|on|held at|takes place at|hosted by|hosted at|located at|venue|location|place|site|where)\s*/i', '', $location);
        
        // Remove "The" prefix
        $location = preg_replace('/^the\s+/i', '', $location);
        
        // Remove event-related suffixes and context
        $location = preg_replace('/\s+(?:convened|during|held|took place|was held|is held|meeting|event|conference|workshop|seminar).*$/i', '', $location);
        
        // Remove sentence fragments and incomplete phrases
        $location = preg_replace('/\s+(?:of one of|in one of|at one of|stitutions|participants|members|delegates|representatives|from|officers).*$/i', '', $location);
        
        // Remove incomplete words at the beginning
        $location = preg_replace('/^(?:ternational|nternational|ational|tional|ional|onal|nal|al|l|s|is|institution|host|institution is|hosted by)\s*/i', '', $location);
        
        // Remove extra descriptive text
        $location = preg_replace('/\s+(?:inside|within|at the|in the|of the|for the).*$/i', '', $location);
        
        // Clean up extra whitespace and normalize
        $location = preg_replace('/\s+/', ' ', $location);
        $location = trim($location);
        
        // Capitalize properly while preserving specific names
        $location = ucwords(strtolower($location));
        
        // Fix specific university and location names
        $location = str_replace('Universitas Brawijaya', 'Universitas Brawijaya', $location);
        $location = str_replace('Malang', 'Malang', $location);
        $location = str_replace('Indonesia', 'Indonesia', $location);
        $location = str_replace('Petaling Jaya', 'Petaling Jaya', $location);
        $location = str_replace('Malaysia', 'Malaysia', $location);
        $location = str_replace('Hotel Intercontinental', 'Hotel InterContinental', $location);
        
        return $location;
    }
    
    /**
     * Check if extracted text is a valid venue location (strict validation)
     */
    private function isValidVenueLocation($location) {
        if (strlen($location) < 3 || strlen($location) > 200) {
            return false;
        }
        
        $locationLower = strtolower($location);
        
        // Must contain venue-related keywords
        $venueKeywords = [
            'ballroom', 'hall', 'auditorium', 'center', 'centre', 'hotel', 'resort', 
            'convention', 'conference', 'building', 'room', 'theater', 'theatre',
            'university', 'college', 'institute', 'school', 'campus',
            'city', 'town', 'jaya', 'malang', 'jakarta', 'manila', 'bangkok',
            'indonesia', 'philippines', 'thailand', 'vietnam', 'japan', 'malaysia',
            'singapore', 'cambodia', 'laos', 'myanmar', 'brunei'
        ];
        
        foreach ($venueKeywords as $keyword) {
            if (strpos($locationLower, $keyword) !== false) {
                return true;
            }
        }
        
        // Check for address patterns
        if (preg_match('/\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Boulevard|Blvd|Road|Rd|Drive|Dr|Lane|Ln|Way)/i', $location)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate confidence score for a venue location (focused on exact venue extraction)
     */
    private function calculateVenueConfidence($location, $context) {
        $confidence = 0.1; // Base confidence
        $locationLower = strtolower($location);
        $contextLower = strtolower($context);
        
        // Higher confidence for specific venue types
        $venueTypes = ['ballroom', 'hall', 'auditorium', 'center', 'centre', 'hotel', 'resort', 'convention', 'conference'];
        foreach ($venueTypes as $venueType) {
            if (strpos($locationLower, $venueType) !== false) {
                $confidence += 0.4;
                break;
            }
        }
        
        // Higher confidence for university/institution names
        $institutionTypes = ['university', 'college', 'institute', 'school', 'universitas'];
        foreach ($institutionTypes as $institutionType) {
            if (strpos($locationLower, $institutionType) !== false) {
                $confidence += 0.3;
                break;
            }
        }
        
        // Higher confidence for geographic indicators
        $geoIndicators = ['city', 'town', 'jaya', 'malang', 'jakarta', 'manila', 'bangkok'];
        foreach ($geoIndicators as $geoIndicator) {
            if (strpos($locationLower, $geoIndicator) !== false) {
                $confidence += 0.2;
                break;
            }
        }
        
        // Higher confidence for country names
        $countries = ['indonesia', 'philippines', 'thailand', 'vietnam', 'japan', 'malaysia', 'singapore'];
        foreach ($countries as $country) {
            if (strpos($locationLower, $country) !== false) {
                $confidence += 0.3;
                break;
            }
        }
        
        // Higher confidence for direct venue indicators in context
        $venueIndicators = ['held at', 'takes place at', 'venue', 'location', 'at'];
        foreach ($venueIndicators as $indicator) {
            if (strpos($contextLower, $indicator) !== false) {
                $confidence += 0.2;
                break;
            }
        }
        
        // Higher confidence for address patterns
        if (preg_match('/\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Boulevard|Blvd|Road|Rd|Drive|Dr|Lane|Ln|Way)/i', $location)) {
            $confidence += 0.5;
        }
        
        // Cap confidence at 1.0
        return min($confidence, 1.0);
    }
    
    /**
     * Perform OCR on image (simplified version)
     * In a real implementation, you'd use Tesseract or cloud OCR services
     */
    private function performOCR($imagePath) {
        // This is a placeholder for OCR functionality
        // In a real implementation, you would:
        // 1. Use Tesseract OCR: `tesseract image.jpg output.txt`
        // 2. Use Google Vision API
        // 3. Use AWS Textract
        // 4. Use Azure Computer Vision
        
        // For now, return null to indicate OCR is not available
        // You can implement this based on your preferred OCR solution
        return null;
    }
    
    /**
     * Get location suggestions based on event type
     */
    public function getLocationSuggestions($eventType = '') {
        $suggestions = [
            'Virtual/Online Event',
            'University Campus',
            'Conference Center',
            'Hotel Conference Room',
            'Community Center',
            'Library',
            'Museum',
            'Theater/Auditorium',
            'Restaurant/Cafe',
            'Outdoor Venue'
        ];
        
        // Add specific suggestions based on event type
        switch (strtolower($eventType)) {
            case 'conference':
            case 'summit':
            case 'symposium':
                array_unshift($suggestions, 'Convention Center', 'Conference Hotel', 'University Auditorium');
                break;
                
            case 'workshop':
            case 'seminar':
            case 'training':
                array_unshift($suggestions, 'Training Center', 'Workshop Room', 'Classroom');
                break;
                
            case 'cultural':
            case 'art':
            case 'exhibition':
                array_unshift($suggestions, 'Art Gallery', 'Museum', 'Cultural Center');
                break;
                
            case 'sports':
            case 'fitness':
                array_unshift($suggestions, 'Sports Complex', 'Gymnasium', 'Stadium');
                break;
        }
        
        return array_unique($suggestions);
    }
}
?>

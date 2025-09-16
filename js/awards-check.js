/**
 * Awards Check System
 * Handles cross-module awards checking after document/event creation
 */

class AwardsCheck {
    constructor() {
        this.apiEndpoint = 'api/awards-check.php';
    }
    
    /**
     * Check award criteria after document/event creation
     * @param {string} type - Type of item created ('award', 'event', 'mou', 'document')
     * @param {string|number} itemId - ID of the created item
     * @param {object} additionalData - Additional data for award checking
     */
    async checkAwardCriteria(type, itemId, additionalData = {}) {
        try {
            console.log(`🔍 Checking award criteria for ${type} with ID: ${itemId}`);
            
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'check_awards',
                    type: type,
                    item_id: itemId,
                    additional_data: additionalData
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                if (result.awards_earned && result.awards_earned.length > 0) {
                    this.showAwardsEarned(result.awards_earned);
                }
                
                if (result.progress_updated && result.progress_updated.length > 0) {
                    this.showProgressUpdated(result.progress_updated);
                }
                
                console.log('✅ Award criteria check completed successfully');
            } else {
                console.warn('⚠️ Award criteria check failed:', result.error);
            }
            
        } catch (error) {
            console.error('❌ Error checking award criteria:', error);
            // Don't show error to user as this is a background process
        }
    }
    
    /**
     * Show awards earned notification
     */
    showAwardsEarned(awards) {
        awards.forEach(award => {
            const message = `🎉 Congratulations! You've earned the "${award.name}" award!`;
            
            if (window.errorHandler) {
                window.errorHandler.showSuccess(message, 8000); // Show longer for awards
            } else {
                // Fallback notification
                alert(message);
            }
            
            // Log for analytics
            console.log(`🏆 Award earned: ${award.name} (${award.category})`);
        });
    }
    
    /**
     * Show progress updated notification
     */
    showProgressUpdated(progressUpdates) {
        progressUpdates.forEach(update => {
            const message = `📈 Progress updated: ${update.description} (${update.progress}%)`;
            
            if (window.errorHandler) {
                window.errorHandler.showInfo(message, 5000);
            }
            
            console.log(`📊 Progress update: ${update.description}`);
        });
    }
    
    /**
     * Check specific award criteria
     * @param {string} awardType - Type of award to check
     * @param {object} criteria - Criteria to check against
     */
    async checkSpecificAward(awardType, criteria) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'check_specific_award',
                    award_type: awardType,
                    criteria: criteria
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.award_earned) {
                this.showAwardsEarned([result.award_earned]);
                return true;
            }
            
            return false;
            
        } catch (error) {
            console.error('Error checking specific award:', error);
            return false;
        }
    }
    
    /**
     * Get user's award progress
     */
    async getUserProgress() {
        try {
            const response = await fetch(`${this.apiEndpoint}?action=get_user_progress`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                return result.progress;
            }
            
            return null;
            
        } catch (error) {
            console.error('Error getting user progress:', error);
            return null;
        }
    }
}

// Initialize awards check system
document.addEventListener('DOMContentLoaded', function() {
    window.awardsCheck = new AwardsCheck();
});

// Global function for backward compatibility
window.checkAwardCriteria = function(type, itemId, additionalData) {
    if (window.awardsCheck) {
        window.awardsCheck.checkAwardCriteria(type, itemId, additionalData);
    }
};

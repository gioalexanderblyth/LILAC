<?php
/**
 * MOU Sync Manager
 * Handles automatic synchronization between enhanced_documents and mous tables
 */

class MouSyncManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Sync document upload to MOU table
     * Called when a document is uploaded to enhanced_documents with MOU category
     */
    public function syncUpload($documentId, $documentName, $filename, $filePath, $fileSize, $fileType, $category, $description = '', $extractedContent = '') {
        try {
            // Only sync if category is MOU-related
            if (!$this->isMouCategory($category)) {
                return ['success' => true, 'message' => 'Not a MOU category, skipping sync'];
            }
            
            // Get original filename from enhanced_documents table
            $originalFilename = '';
            $stmt = $this->pdo->prepare("SELECT original_filename FROM enhanced_documents WHERE id = ?");
            $stmt->execute([$documentId]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($doc) {
                $originalFilename = $doc['original_filename'];
            }
            
            // Check if already exists in mous table
            $checkStmt = $this->pdo->prepare("SELECT id FROM mous WHERE file_name = ? OR partner_name = ?");
            $checkStmt->execute([$filename, $documentName]);
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                return ['success' => true, 'message' => 'MOU already exists, skipping sync'];
            }
            
            // Create description for MOU table
            $mouDescription = "Auto-synced from enhanced_documents table\n";
            $mouDescription .= "Original Name: " . $documentName . "\n";
            $mouDescription .= "Original Filename: " . $originalFilename . "\n";
            $mouDescription .= "Category: " . $category . "\n";
            $mouDescription .= "File Type: " . $fileType . "\n";
            if ($description) {
                $mouDescription .= "Description: " . $description . "\n";
            }
            if ($extractedContent) {
                $mouDescription .= "Extracted Content: " . substr($extractedContent, 0, 200) . "...\n";
            }
            
            // Insert into mous table
            $insertStmt = $this->pdo->prepare("INSERT INTO mous 
                (partner_name, status, date_signed, end_date, description, type, file_name, original_filename, file_size, file_path, created_at, updated_at) 
                VALUES (?, 'active', ?, NULL, ?, 'MOU', ?, ?, ?, ?, NOW(), NOW())");
            
            // Use current date as signed date
            $signedDate = date('Y-m-d');
            
            $insertStmt->execute([
                $documentName,
                $signedDate,
                $mouDescription,
                $filename,
                $originalFilename,
                $fileSize,
                $filePath
            ]);
            
            $mouId = $this->pdo->lastInsertId();
            
            return [
                'success' => true, 
                'message' => 'MOU synced successfully',
                'mou_id' => $mouId,
                'document_id' => $documentId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'MOU sync failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync document deletion from MOU table
     * Called when a document is deleted from enhanced_documents
     */
    public function syncDeletion($documentId, $documentName, $filename) {
        try {
            // Find corresponding MOU entries
            $stmt = $this->pdo->prepare("SELECT id FROM mous WHERE file_name = ? OR partner_name = ?");
            $stmt->execute([$filename, $documentName]);
            $mouEntries = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($mouEntries)) {
                return ['success' => true, 'message' => 'No corresponding MOU found, skipping sync'];
            }
            
            $deletedCount = 0;
            foreach ($mouEntries as $mouId) {
                $deleteStmt = $this->pdo->prepare("DELETE FROM mous WHERE id = ?");
                $deleteStmt->execute([$mouId]);
                
                if ($deleteStmt->rowCount() > 0) {
                    $deletedCount++;
                }
            }
            
            return [
                'success' => true, 
                'message' => "Deleted {$deletedCount} MOU(s) from sync",
                'deleted_count' => $deletedCount
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'MOU deletion sync failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if category is MOU-related
     */
    private function isMouCategory($category) {
        $mouCategories = ['MOUs & MOAs', 'MOU', 'MOA', 'Memorandum of Understanding', 'Agreement'];
        return in_array($category, $mouCategories) || 
               stripos($category, 'mou') !== false || 
               stripos($category, 'moa') !== false ||
               stripos($category, 'agreement') !== false;
    }
    
    /**
     * Get sync status for a document
     */
    public function getSyncStatus($documentId) {
        try {
            // Get document info
            $stmt = $this->pdo->prepare("SELECT document_name, filename, category FROM enhanced_documents WHERE id = ?");
            $stmt->execute([$documentId]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doc) {
                return ['success' => false, 'message' => 'Document not found'];
            }
            
            // Check if synced to MOU table
            $stmt = $this->pdo->prepare("SELECT id, status FROM mous WHERE file_name = ? OR partner_name = ?");
            $stmt->execute([$doc['filename'], $doc['document_name']]);
            $mou = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'document' => $doc,
                'synced' => $mou !== false,
                'mou_info' => $mou
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Sync status check failed: ' . $e->getMessage()
            ];
        }
    }
}
?>

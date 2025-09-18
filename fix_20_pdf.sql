-- Fix 20.pdf content
UPDATE enhanced_documents 
SET extracted_content = 'Agreement', category = 'MOU' 
WHERE id = 45;

-- Check the result
SELECT id, original_filename, extracted_content, category 
FROM enhanced_documents 
WHERE id = 45;

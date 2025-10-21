-- Add full-text search indexes for better search performance
ALTER TABLE articles
ADD FULLTEXT INDEX idx_article_search (title, content);

ALTER TABLE recipes
ADD FULLTEXT INDEX idx_recipe_search (title, description, ingredients);

-- Add search columns if they don't exist
ALTER TABLE articles
ADD COLUMN IF NOT EXISTS views INT DEFAULT 0 AFTER content;

ALTER TABLE recipes
ADD COLUMN IF NOT EXISTS views INT DEFAULT 0 AFTER instructions;

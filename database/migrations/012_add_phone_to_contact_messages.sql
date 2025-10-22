-- Add phone column to contact_messages table
ALTER TABLE contact_messages
ADD COLUMN phone VARCHAR(20) NULL AFTER email;

<?php
/**
 * EmailTemplate - Database-based email template management
 * Handles CRUD operations for email templates
 */

class EmailTemplate {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $db->getConnection();
    }

    /**
     * Get all email templates
     * @param bool $activeOnly - Only fetch active templates
     * @return array
     */
    public function getAll($activeOnly = false) {
        $sql = "SELECT * FROM email_templates";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY template_name ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON variables
        foreach ($templates as &$template) {
            if ($template['variables']) {
                $template['variables'] = json_decode($template['variables'], true);
            }
        }

        return $templates;
    }

    /**
     * Get a single template by key
     * @param string $key - Template key
     * @return array|null
     */
    public function getByKey($key) {
        $sql = "SELECT * FROM email_templates WHERE template_key = :key LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':key', $key);
        $stmt->execute();

        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($template && $template['variables']) {
            $template['variables'] = json_decode($template['variables'], true);
        }

        return $template ?: null;
    }

    /**
     * Get a single template by ID
     * @param int $id - Template ID
     * @return array|null
     */
    public function getById($id) {
        $sql = "SELECT * FROM email_templates WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($template && $template['variables']) {
            $template['variables'] = json_decode($template['variables'], true);
        }

        return $template ?: null;
    }

    /**
     * Create a new email template
     * @param array $data - Template data
     * @return int|false - New template ID or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO email_templates
                (template_key, template_name, subject, body_html, body_text, description, variables, is_active)
                VALUES
                (:template_key, :template_name, :subject, :body_html, :body_text, :description, :variables, :is_active)";

        $stmt = $this->conn->prepare($sql);

        // Encode variables as JSON if it's an array
        $variables = isset($data['variables']) && is_array($data['variables'])
            ? json_encode($data['variables'])
            : $data['variables'];

        $stmt->bindParam(':template_key', $data['template_key']);
        $stmt->bindParam(':template_name', $data['template_name']);
        $stmt->bindParam(':subject', $data['subject']);
        $stmt->bindParam(':body_html', $data['body_html']);
        $stmt->bindParam(':body_text', $data['body_text']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':variables', $variables);
        $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Update an existing email template
     * @param int $id - Template ID
     * @param array $data - Updated template data
     * @return bool
     */
    public function update($id, $data) {
        $sql = "UPDATE email_templates SET
                template_key = :template_key,
                template_name = :template_name,
                subject = :subject,
                body_html = :body_html,
                body_text = :body_text,
                description = :description,
                variables = :variables,
                is_active = :is_active
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        // Encode variables as JSON if it's an array
        $variables = isset($data['variables']) && is_array($data['variables'])
            ? json_encode($data['variables'])
            : $data['variables'];

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':template_key', $data['template_key']);
        $stmt->bindParam(':template_name', $data['template_name']);
        $stmt->bindParam(':subject', $data['subject']);
        $stmt->bindParam(':body_html', $data['body_html']);
        $stmt->bindParam(':body_text', $data['body_text']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':variables', $variables);
        $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Delete an email template
     * @param int $id - Template ID
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM email_templates WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Toggle template active status
     * @param int $id - Template ID
     * @return bool
     */
    public function toggleActive($id) {
        $sql = "UPDATE email_templates SET is_active = NOT is_active WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Render template with variables
     * @param string $key - Template key
     * @param array $variables - Variables to replace
     * @return array|null - Array with 'subject' and 'body' keys
     */
    public function render($key, $variables = []) {
        $template = $this->getByKey($key);

        if (!$template || !$template['is_active']) {
            return null;
        }

        $subject = $template['subject'];
        $body = $template['body_html'];

        // Replace variables in subject and body
        foreach ($variables as $varKey => $varValue) {
            // Ensure variable key is in format {VAR_NAME}
            $placeholder = '{' . strtoupper(str_replace(['{', '}'], '', $varKey)) . '}';
            $subject = str_replace($placeholder, $varValue, $subject);
            $body = str_replace($placeholder, $varValue, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'template' => $template
        ];
    }

    /**
     * Get template statistics
     * @return array
     */
    public function getStats() {
        $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
                FROM email_templates";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if template key exists
     * @param string $key - Template key
     * @param int $excludeId - ID to exclude from check (for updates)
     * @return bool
     */
    public function keyExists($key, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM email_templates WHERE template_key = :key";
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':key', $key);
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}

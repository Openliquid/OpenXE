<?php

/**
 * WiedervorlageManager handles reminder scheduling and call integrations.
 *
 * It provides methods to schedule reminders, retrieve pending reminders,
 * fetch call lists from the 3CX API and calculate statistics on contacts.
 */
class WiedervorlageManager
{
    /** @var \PDO|mixed */
    protected $db;

    /**
     * @param mixed $db Database connection from Application
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Schedule a reminder for a contact.
     *
     * @param int       $contactId
     * @param \DateTime $date
     * @param string    $note
     */
    public function scheduleReminder($contactId, \DateTime $date, $note = '')
    {
        $contactId = (int)$contactId;
        $datum = $date->format('Y-m-d H:i:s');
        $note = $this->db->real_escape_string($note);
        $this->db->Insert(
            "INSERT INTO crm_reminder (contact_id, remind_at, note) VALUES ({$contactId}, '{$datum}', '{$note}')"
        );
    }

    /**
     * Get all pending reminders.
     *
     * @return array
     */
    public function getPendingReminders()
    {
        $now = date('Y-m-d H:i:s');
        return $this->db->SelectArr(
            "SELECT id, contact_id, remind_at, note FROM crm_reminder WHERE remind_at <= '{$now}' ORDER BY remind_at"
        );
    }

    /**
     * Fetch call list from 3CX API.
     *
     * @param string $apiUrl
     * @param string $token
     *
     * @return array
     */
    public function fetch3CXCallList($apiUrl, $token)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $token,
                'timeout' => 20,
            ],
        ]);
        $response = @file_get_contents($apiUrl . '/CallLog', false, $context);
        if ($response === false) {
            return [];
        }
        $data = json_decode($response, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Calculate simple statistics for contacts.
     *
     * @return array
     */
    public function getContactStatistics()
    {
        return $this->db->SelectArr(
            "SELECT status, COUNT(*) AS cnt FROM adresse GROUP BY status"
        );
    }
}

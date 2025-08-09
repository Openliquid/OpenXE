<?php
/**
 * TimeTracker handles logging and retrieving working times.
 * It also offers a simple calendar sync to Google or Outlook via cURL.
 */
class TimeTracker
{
    /** @var \DB $db */
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Log a working time entry.
     *
     * @param int       $userId
     * @param string    $start     Datetime string
     * @param string    $end       Datetime string
     * @param int|null  $projectId Optional project reference
     * @param string    $notes     Optional comment
     */
    public function logTime($userId, $start, $end, $projectId = null, $notes = '')
    {
        $sql = "INSERT INTO timetracker_logs (user_id, start_time, end_time, project_id, notes) VALUES (?, ?, ?, ?, ?)";
        $params = [$userId, $start, $end, $projectId, $notes];
        $this->db->Insert($sql, $params);
    }

    /**
     * Return time entries for a user within a date range.
     *
     * @param int    $userId
     * @param string $from   Date string (Y-m-d)
     * @param string $to     Date string (Y-m-d)
     *
     * @return array
     */
    public function getLogs($userId, $from, $to)
    {
        $sql = "SELECT id, start_time, end_time, project_id, notes FROM timetracker_logs WHERE user_id = ? AND start_time BETWEEN ? AND ?";
        return $this->db->SelectArr($sql, [$userId, $from . ' 00:00:00', $to . ' 23:59:59']);
    }

    /**
     * Synchronise an event to an external calendar provider.
     *
     * @param string $provider google|outlook
     * @param array  $event    [title,start,end]
     * @param string $token    OAuth access token
     *
     * @return string|null     Response body or null on failure
     */
    public function syncCalendar($provider, array $event, $token)
    {
        if ($provider === 'google') {
            $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
        } elseif ($provider === 'outlook') {
            $url = 'https://graph.microsoft.com/v1.0/me/events';
        } else {
            return null;
        }

        $payload = json_encode([
            'subject' => $event['title'],
            'start'   => ['dateTime' => $event['start'], 'timeZone' => 'UTC'],
            'end'     => ['dateTime' => $event['end'], 'timeZone' => 'UTC'],
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}

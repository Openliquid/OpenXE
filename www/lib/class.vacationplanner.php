<?php
/**
 * VacationPlanner manages vacation requests and approvals.
 * It can synchronise approved vacations with external calendars.
 */
class VacationPlanner
{
    /** @var \DB $db */
    protected $db;
    /** @var TimeTracker */
    protected $tracker;

    public function __construct($db, TimeTracker $tracker)
    {
        $this->db      = $db;
        $this->tracker = $tracker;
    }

    /**
     * Create a vacation request.
     *
     * @param int    $userId
     * @param string $from     Date string (Y-m-d)
     * @param string $to       Date string (Y-m-d)
     * @param string $reason   Optional comment
     */
    public function requestVacation($userId, $from, $to, $reason = '')
    {
        $sql = "INSERT INTO vacation_requests (user_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)";
        $this->db->Insert($sql, [$userId, $from, $to, $reason]);
    }

    /**
     * Approve a vacation request and push it to external calendar.
     *
     * @param int    $requestId
     * @param int    $approverId
     * @param string $provider
     * @param string $token
     */
    public function approveVacation($requestId, $approverId, $provider = '', $token = '')
    {
        $row = $this->db->SelectArr("SELECT * FROM vacation_requests WHERE id = ?", [$requestId]);
        if (empty($row)) {
            return;
        }
        $data = $row[0];
        $this->db->Update("UPDATE vacation_requests SET status = 'approved', approver_id = ? WHERE id = ?", [$approverId, $requestId]);

        if ($provider && $token) {
            $event = [
                'title' => 'Vacation',
                'start' => $data['start_date'] . 'T00:00:00',
                'end'   => $data['end_date'] . 'T23:59:59',
            ];
            $this->tracker->syncCalendar($provider, $event, $token);
        }
    }

    /**
     * Get vacation requests for a user.
     *
     * @param int $userId
     *
     * @return array
     */
    public function getRequests($userId)
    {
        $sql = "SELECT * FROM vacation_requests WHERE user_id = ? ORDER BY start_date";
        return $this->db->SelectArr($sql, [$userId]);
    }
}

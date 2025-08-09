<?php
/**
 * Page controller for the time tracker and vacation planner.
 */
class Timetracker
{
    /** @var Application */
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
        $this->app->ActionHandlerInit($this);
        $this->app->ActionHandler('calendar', 'Calendar');
        $this->app->ActionHandler('events', 'Events');
        $this->app->ActionHandler('save', 'Save');
        $this->app->DefaultActionHandler('calendar');
        $this->app->ActionHandlerListen($app);
    }

    /**
     * Render the calendar view.
     */
    public function Calendar()
    {
        $this->app->erp->MenuEintrag('index.php?module=timetracker&action=calendar', 'Zeiterfassung');
        $this->app->Tpl->Parse('PAGE', 'timetracker_calendar.tpl');
    }

    /**
     * Provide events in JSON for FullCalendar.
     */
    public function Events()
    {
        $userId = $this->app->User->GetID();
        $tracker = new TimeTracker($this->app->DB);
        $entries = $tracker->getLogs($userId, date('Y-m-01'), date('Y-m-t'));
        $events = [];
        foreach ($entries as $row) {
            $events[] = [
                'id'    => $row['id'],
                'title' => $row['notes'],
                'start' => $row['start_time'],
                'end'   => $row['end_time'],
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($events);
        exit;
    }

    /**
     * Persist a new time log from form submission.
     */
    public function Save()
    {
        $userId = $this->app->User->GetID();
        $start  = $this->app->Secure->GetPOST('start');
        $end    = $this->app->Secure->GetPOST('end');
        $note   = $this->app->Secure->GetPOST('note');
        $tracker = new TimeTracker($this->app->DB);
        $tracker->logTime($userId, $start, $end, null, $note);
        header('Location: index.php?module=timetracker&action=calendar');
        exit;
    }
}

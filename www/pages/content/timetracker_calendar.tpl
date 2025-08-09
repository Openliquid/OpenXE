<div id="tt-calendar"></div>
{literal}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<script>
 document.addEventListener('DOMContentLoaded', function() {
   var calendarEl = document.getElementById('tt-calendar');
   var calendar = new FullCalendar.Calendar(calendarEl, {
     initialView: 'dayGridMonth',
     events: 'index.php?module=timetracker&action=events'
   });
   calendar.render();
 });
</script>
{/literal}

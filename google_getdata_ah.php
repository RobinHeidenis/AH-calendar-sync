<?php

header('Content-Type: text/calendar');

include('./consts.php');

$appointments = json_decode(file_get_contents('./scheduledata/appointments_ah.json'), true);

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//Robin INC//NONSGML Calendar//EN\r\n";
//echo "METHOD:CANCEL\r\n";

if (is_array($appointments)) { // catch zero appointments (no array)

    foreach ($appointments as $item) {
        echo "BEGIN:VEVENT\r\n";
        echo "UID:AHWERK" . strtotime($item["date"]) . "\r\n";
        echo "DTSTAMP:" . gmdate('Ymd\THis\Z', time()) . "\r\n";
        echo "DTSTART:" . gmdate('Ymd\THis\Z', strtotime($item["date"] . $item["starttime"])) . "\r\n";
        echo "DTEND:" . gmdate('Ymd\THis\Z', strtotime($item["date"] . $item["endtime"])) . "\r\n";
        echo "SUMMARY:AH: werk\r\n";
        echo "LOCATION: " . WORKPLACE_ADDRESS . "\r\n";
        echo "END:VEVENT\r\n";
    }

}

echo "END:VCALENDAR\r\n";

?>
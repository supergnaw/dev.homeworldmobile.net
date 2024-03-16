<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

//$titmouse = new \app\Nestbox\Titmouse\Titmouse('users', 'username');
//$babbler = new \app\Nestbox\Babbler\Babbler();
//$api = new \app\PlayFab\Playfab(PLAYFAB_APP_ID);

// Process Request URI
$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));

/*
 * TIMERS
 */
$targetTimes = [
    // Daily at 02:00
    "Daily Quest Reset" => (gmdate("YmdH") < gmdate("Ymd02"))
        ? gmdate("Y-m-d\T02:00:00")
        : gmdate("Y-m-d\T02:00:00", strtotime("tomorrow UTC")),

    // Sunday at 01:00
    "Weekly Quest Reset" => (gmdate("YmdNH") < gmdate("Ymd701"))
        ? gmdate("Y-m-d\T01:00:00", strtotime("this Sunday UTC"))
        : gmdate("Y-m-d\T01:00:00", strtotime("next Sunday UTC")),

    // Monday at 02:00
    "Weekly Market Restock" => (gmdate("YmNH") < gmdate("Ym111"))
        ? gmdate("Y-m-d\T02:00:00", strtotime("next Monday UTC"))
        : gmdate("Y-m-d\T02:00:00", strtotime("this Monday UTC")),

    // Daily at 02:00
    "Daily Market Restock" => (gmdate("YmdH") < gmdate("Ymd02"))
        ? gmdate("Y-m-d\T02:00:00")
        : gmdate("Y-m-d\T02:00:00", strtotime("tomorrow UTC")),

    // Friday at 11
    "Weekend Event Start" => (gmdate("YmNH") < gmdate("Ym511"))
        ? gmdate("Y-m-d\T11:00:00", strtotime("this Friday UTC"))
        : gmdate("Y-m-d\T11:00:00", strtotime("next Friday UTC")),

    // Monday at 11
    "Weekend Event End" => (gmdate("YmNH") < gmdate("Ym111"))
        ? gmdate("Y-m-d\T11:00:00", strtotime("this Monday UTC"))
        : gmdate("Y-m-d\T11:00:00", strtotime("next Monday UTC")),
];

$countdownRows = [];
foreach ($targetTimes as $event => $time) {
    $countdownRows[] = "
            <tr>
                <td>{$event}</td>
                <td>{$time}</td>
                <td class='timer' data-timer-target='{$time}'>Initializing...</td>
            </tr>";
}
$countdownRows = implode($countdownRows);

$currentTime = gmdate("Y-m-d\TH:i:s");

$html = "
        <h1>Countdown Timers</h1>
        <p>Timers reflecting how long until various happenings! :)</p>
        <h2>Current Time UTC</h2>
        <p id='current-utc-time'>{$currentTime}</p>
        <table>
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Next Rollover</th>
                    <th>Time Remaining</th>
                </tr>
            </thead>
            <tbody>
                {$countdownRows}
            </tbody>
        </table>
        <script>
            document.onreadystatechange = function(event) {
                if (document.readyState === 'complete') {
                    // Update timers for each countdown
                    let elements = document.querySelectorAll('.timer');
                    Array.from(elements).forEach((element, index) => {
                        let targetTime = Math.floor(new Date(element.getAttribute('data-timer-target')).getTime() / 1000);
                        let totalSeconds, s, m, h, d;
                        setInterval(function() {
                            totalSeconds = targetTime - Math.floor(new Date().getTime() / 1000) - (new Date().getTimezoneOffset() * 60);
                            s = (totalSeconds % 60).toString().padStart(2, '0');
                            m = (Math.floor(totalSeconds / 60) % 60).toString().padStart(2, '0');
                            h = (Math.floor(totalSeconds / 60 / 60) % 24).toString().padStart(2, '0');
                            d = (Math.floor(totalSeconds / 60 / 60 / 24)).toString().padStart(2, '0');
                            element.textContent = `\${d}d\${h}h\${m}m\${s}s`;
                            if (0 == totalSeconds) {
                                location.reload();
                            }
                        }, 1000);
                    });
                    
                    // Update UTC timestamp on page
                    setInterval(function () {
                        document.getElementById('current-utc-time').textContent = new Date().toUTCString();
                    }, 1000);
                }
            };
        </script>";

return $html;
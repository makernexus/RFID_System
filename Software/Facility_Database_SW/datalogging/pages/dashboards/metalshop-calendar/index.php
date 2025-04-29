<!DOCTYPE html>
<?php
    // Show photos of everyone who is checked in today.
    //
    // Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
    // By Jim Schrempp & Giulio Gratta
    // 20250202 result is now cached for 15 seconds

    include '../../php_includes/commonfunctions.php';

    allowWebAccess();  // if IP not allowed, then die

    $today = new DateTime();
    $today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
    $now_hours = intval($today->format("H"));
    $now_minutes = intval($today->format("i"));

    $formatted_now = "0%";
    if ($now_hours >= 10 && $now_hours < 22) {
        $formatted_now = ((($now_hours-10) + ($now_minutes/60)) / 12 * 100)."%";
    } else if ($now_hours >= 22) {
        $formatted_now = "100%";
    }
    $page="machine_calendar";
    $header_title = "Coldshop Calendar";

    $cache_file = "metalshop-calendar.cache";
    include '../../php_includes/cache_header.php';

    include '../../php_includes/open_db_connection.php';

    include '../../php_includes/simple_html_dom.php';

    // Get current MgrOnDuty status
    $url = 'https://storage.googleapis.com/makernexus_amilia_reservations_cache/reservations.json';
    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //execute post
    $result = json_decode(curl_exec($ch), true);

    $metal_shop_events = array(
        'bridgeport_1' => [],
        'bridgeport_2' => [],
        'bridgeport_3' => [],
        'birmingham_cnc' => [],
        'k&t_mill' => [],
        'clausing_lathe' => [],
        'leblond_lathe' => [],
        'surface_grinder' => [],
    );

    for ($i=0; $i < count($result); $i++) { 
        $event = $result[$i];
        $event_id = $event["Location"]["Id"];

        $start_time = new DateTime($event["Start"]);
        $event_start = $start_time->format("g:ia");
        $event_start_css = $start_time->format("H-i");
        $end_time = new DateTime($event["End"]);
        $event_end = $end_time->format("g:ia");
        $event_end_css = $end_time->format("H-i");

        $event_type = $event["Type"];
        if ($event_type == "FacilityBooking") {
            $event_type_key = "booking";
            $event_title = "Reserved";
            $event_display_time = $event_start." - ".$event_end;
        } else if ($event_type == "Activity") {
            $event_type_key = "class";
            $event_title = $event["Title"];
            $event_display_time = "";
        }
        

        $formatted_event = [
            "id" => $event_id,
            "title" => $event_title,
            "event_type_key" => $event_type_key,
            "display_time" => $event_display_time,
            "start_time_css" => $event_start_css,
            "end_time_css" => $event_end_css,
        ];

        $machine_name = null;
        if ($event_id == "1486122") {
            $machine_name = "clausing_lathe";
        }
        else if ($event_id == "1486120") {
            $machine_name = "bridgeport_1";
        }
        if (!empty($machine_name)) {
            array_push($metal_shop_events[$machine_name], $formatted_event);
        }
    }
?>

<html>
    <head>
        <title>Metalshop Calendar | Maker Nexus</title>
        <?php include '../../page_components/head_meta.php'; ?>
    </head>
    <body>
        <?php include '../../page_components/header.php'; ?>
        <div class="MN-Content calendar coldshop">
            <div class="calendar-hours">
                <div class="spacer"></div>
                <div class="hours">
                    <div class="hour full">10 AM</div>
                    <div class="hour half"></div>
                    <div class="hour full">11 AM</div>
                    <div class="hour half"></div>
                    <div class="hour full">12 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">1 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">2 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">3 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">4 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">5 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">6 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">7 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">8 PM</div>
                    <div class="hour half"></div>
                    <div class="hour full">9 PM</div>
                    <div class="hour half"></div>
                    <div class="now" style="top: <?php echo $formatted_now; ?>"></div>
                </div>
            </div>
            <div class="calendar-content">
                <div class="calendar-machine">
                    <div class="machine-name"><p>Bridgeport 1</p></div>
                    <div class="reservations">
                        <?php foreach($metal_shop_events["bridgeport_1"] as $key=>$event): ?>
                            <div class="reservation <?= $event['event_type_key']; ?> start-<?= $event['start_time_css']; ?> end-<?= $event['end_time_css']; ?>">
                                <div class="tile">
                                    <p>
                                        <strong><?= $event["title"]; ?></strong>
                                        <br /><?= $event["display_time"]; ?>
                                    <p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="calendar-machine disabled">
                    <div class="machine-name"><p>Bridgeport 2</p></div>
                </div>
                <div class="calendar-machine">
                    <div class="machine-name"><p>Bridgeport 3</p></div>
                    <div class="reservations">
                        <!--
                        <div class="reservation booking start-11-00 end-13-00">
                            <div class="tile">
                                <p>
                                    <strong>Reserved</strong>
                                    <br />11:00am - 1:00pm
                                <p>
                            </div>
                        </div>
                        <div class="reservation class start-13-30 end-16-30">
                            <div class="tile">
                                <p>
                                    <strong>Bridgeport Mill (EQ) with Giulio on Sunday, April 6, 1:30 – 4:30pm</strong>
                                </p>
                            </div>
                        </div>
                        <div class="reservation admin start-17-00 end-18-00">
                            <div class="tile">
                                <p>
                                    <strong>Maintenance</strong>
                                    <br />5:00pm - 6:00pm
                                <p>
                            </div>
                        </div>
                        -->
                    </div>
                </div>
                <!--
                <div class="calendar-machine disabled">
                    <div class="machine-name"><p>Birmingham CNC</p></div>
                </div>
                <div class="calendar-machine disabled">
                    <div class="machine-name"><p>K&T Mill</p></div>
                </div>
                -->
                <div class="calendar-machine">
                    <div class="machine-name"><p>Clausing Lathe</p></div>
                    <div class="reservations">
                        <?php foreach($metal_shop_events["clausing_lathe"] as $key=>$event): ?>
                            <div class="reservation <?= $event['event_type_key']; ?> start-<?= $event['start_time_css']; ?> end-<?= $event['end_time_css']; ?>">
                                <div class="tile">
                                    <p>
                                        <strong><?= $event["title"]; ?></strong>
                                        <br /><?= $event["display_time"]; ?>
                                    <p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="calendar-machine disabled">
                    <div class="machine-name"><p>Leblond Lathe</p></div>
                </div>
                <div class="calendar-machine disabled">
                    <div class="machine-name"><p>Surface Grinder</p></div>
                </div>
            </div>
        </div>
        <?php include '../../page_components/footer.php'; ?>
    </body>
</html>

<?php
    include '../../php_includes/close_db_connection.php';
    include '../../php_includes/cache_footer.php';
?>

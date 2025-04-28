<?php

// Common functions used by server scripts
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp & Giulio Gratta

// if the client ip address is not on the allowed list, the web page will die
function allowWebAccess() {
	// for now return true until we get the public ip of MN
	return;

	$allowedIPs = array (
		'34.197.209.41'   // Particle.io cloud (all our devices)
		,'107.139.32.104'   // Jim in San Carlos
	);

	$rtnvalue = false;
	if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
		die("access from this ip is not allowed: " . $_SERVER['REMOTE_ADDR']);
	}
}

?>

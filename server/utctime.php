<?php
  // my server is in Eastern Time, I'm in Pacific, both are subject to DST,
  // so chose to send a UTC time to the sql server instead of using the built
  // in timestamp function

  date_default_timezone_set ("UTC");
  $utcnow = date("Y-m-d H:i:s"); 
?>
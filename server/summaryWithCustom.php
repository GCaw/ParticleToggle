<html>
<title>Toggle Summary</title>

<body>

<?php
	include('config.php');
	include('utctime.php');
        
	if (isset($_GET['code']))
	{
		if ($_GET['code'] == $client_key)
		{
			$quer = "";
			
			// we are running this in California, which is 7/8 hours behind UTC.
			// We can dictate that anything before 3/4am CA time is yesterday, anything after is today.
			// our cut off time is thus ~ 11AM UTC.
			
			// changing this to 5am SAST = 3AM UTC
			
			// get the time now
			$now = time();
			$hour = intval(date("H", $now));
			$day = intval(date("d", $now));
			$month = intval(date("m", $now));
			$year = intval(date("Y", $now));
			
			// if after 11am, start time = 11am today
			$utc_cutoff = 8;
			$start = mktime($utc_cutoff, 0, 0, $month, $day, $year);
			if ($hour < $utc_cutoff)
			{
				$start = $start - (60 * 60 *24); // go back a day
			}
			
			// we actually want to do queries going back a week
			$start = $start - (60*60*24*6);
			$running = false;
			
			for ($i = 0; $i < 7; $i++)
			{
				$running = false;
				$running_total = 0;
				$start_time = 0;
				
				echo "--------------------<br />";
				// end time is always 24 hours after start time
				$end = $start + (60 * 60 *24);
				
				// do a query bounded by those parameters
				echo "Start " . date("Y-m-d H:i:s", $start) . "<br />";
				echo "End   " . date("Y-m-d H:i:s", $end) . "<br />";
				
				$quer = "SELECT * FROM events WHERE (time BETWEEN '" . date("Y-m-d H:i:s", $start) . "' AND '" . date("Y-m-d H:i:s", $end) . "')";
				if (isset($_GET['tag']))
				{
					$quer = $quer . "AND tag = " . $_GET['tag'];
				}
				$quer = $quer . " ORDER BY time ASC;";
				
				try
				{
					$con = new PDO($hostdb, $user, $pass);
					$res = $con->query($quer);
					if ($res){
						while ($row = $res->fetch())
						{
							$delta = 0;
							if($row['type'] == 1)
							{
								//start
								if (!$running)
								{
									$running = true;
									$dt = DateTime::createFromFormat("Y-m-d H:i:s", $row['time']);
									$start_time = $dt->format('U');
								}
							}
							if($row['type'] == 2)
							{
								//stop
								if ($running)
								{
									$running = false;
									$dt = DateTime::createFromFormat("Y-m-d H:i:s", $row['time']);
									$delta = $dt->format('U') - $start_time;
									if ($delta < 0)
									{
										echo "delta is negative hmm";
									}
									$running_total += $delta;
								}
							}
							echo $row['time']. " " . $row['tag'] . " " . $row['type'];
							echo "delta: " .$delta . "s";
							echo  "<br />";
						}
						if ($running)
						{
							// if running but no more entries, we add the difference between the last time and now
							$delta = $now - $start_time;
							$running_total += $delta;
							echo "Still running<br />";
						}
						$total_hours = $running_total / 3600.0;
						printf("Total: %.02frs (%ds)<br />", $total_hours, $running_total);
					}
					else
					{
						http_response_code(500);
						echo "Con failed<br />";
					}
				} 
				catch(PDOException $ex)
				{
					http_response_code(500);
					echo "An Error occured!";
					die();
				}
				$con = null;
				
				$start = $start + (60*60*24);
			}
					
			if (isset($_GET['tag']))
			{
				echo "<br /><br />";
				if ($running)
				{
					echo "<form action=\"stop.php\"><input name=\"code\" type=\"hidden\" value=\"". $_GET['code'] . "\"><input name=\"tag\" type=\"hidden\" value=\"" . $_GET['tag'] . "\"><input type=\"submit\" value=\"Stop\"/></form>";	
				}
				else
				{
					echo "<form action=\"start.php\"><input name=\"code\" type=\"hidden\" value=\"". $_GET['code'] . "\"><input name=\"tag\" type=\"hidden\" value=\"" . $_GET['tag'] . "\"><input type=\"submit\" value=\"Start\"/></form>";
				}
				
				echo "<br /><br />";
				
				echo "<form action=\"custom.php\"><input name=\"code\" type=\"hidden\" value=\"". $_GET['code'] . "\"><input name=\"tag\" type=\"hidden\" value=\"" . $_GET['tag'] . "\"><input name=\"event\" type=\"hidden\" value=\"1\"><input type=\"datetime-local\" name=\"eventtime\"><input type=\"submit\" value=\"Add Start time\"/></form>";	
				
				echo "<br />";
				
				echo "<form action=\"custom.php\"><input name=\"code\" type=\"hidden\" value=\"". $_GET['code'] . "\"><input name=\"tag\" type=\"hidden\" value=\"" . $_GET['tag'] . "\"><input name=\"event\" type=\"hidden\" value=\"2\"><input type=\"datetime-local\" name=\"eventtime\"><input type=\"submit\" value=\"Add stop time\"/></form>";	
			}
		}		
		else
		{
			http_response_code(403);
			echo "wrong code";
		}
	}
	else
	{
		http_response_code(400);
		echo "no code";
	}
	
?>
</body>
</html>
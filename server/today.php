<?php
	include('config.php');
	include('utctime.php');
        
	if (isset($_GET['code']) && isset($_GET['tag']))
	{
		if ($_GET['code'] == $client_key)
		{
			$quer = "";
		
			// we are running this in Ontario, which is 4/5 hours behind UTC.
			// We can dictate that anything before 3/4am ON time is yesterday, anything after is today.
			// (3am PT = 11 am UTC)
			// (or 5am SAST = 3AM UTC)
			// (or 4am ET = 8am UTC)
			
			// get the time now
			$now = time();
			$hour = intval(date("H", $now));
			$day = intval(date("d", $now));
			$month = intval(date("m", $now));
			$year = intval(date("Y", $now));
			
			// if after our cutoff time, start time = cutoff today
			$utc_cutoff = 8;
			$start = mktime($utc_cutoff, 0, 0, $month, $day, $year);
			if ($hour < $utc_cutoff)
			{
				$start = $start - (60 * 60 *24); // go back a day
			}
			$end = $start + (60 * 60 *24);

			$running = false;
			$running_total = 0;
			$start_time = 0;
			
			// do a query bounded by those parameters
			// echo "Start " . date("Y-m-d H:i:s", $start) . "<br />";
			// echo "End   " . date("Y-m-d H:i:s", $end) . "<br />";
			
			$quer = "SELECT * FROM events WHERE (time BETWEEN '" . date("Y-m-d H:i:s", $start) . "' AND '" . date("Y-m-d H:i:s", $end) . "')";
			$quer = $quer . "AND tag = " . $_GET['tag'];
			$quer = $quer . " ORDER BY time ASC;";
			
			try
			{
				$con = new PDO($hostdb, $user, $pass);
				$res = $con->query($quer);
				if ($res){
					while ($row = $res->fetch())
					{
						$delta = 0;
						$dt = DateTime::createFromFormat("Y-m-d H:i:s", $row['time']);
						
						if($row['type'] == 1)
						{
							//start
							if (!$running)
							{
								$running = true;
								$start_time = $dt->format('U');
							}
						}
						if($row['type'] == 2)
						{
							//stop
							if ($running)
							{
								$running = false;
								$delta = $dt->format('U') - $start_time;
								if ($delta < 0)
								{
									echo "delta is negative hmm";
								}
								$running_total += $delta;
							}
						}
						// echo $row['time']. " " . $row['tag'] . " " . $row['type'];
						// echo "delta: " .$delta . "s";
						// echo  "<br />";
					}
					if ($running)
					{
						// if running but no more entries, we add the difference between the last time and now
						$delta = $now - $start_time;
						$running_total += $delta;
					}
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
			// echo "[" . $running_total . "s]";
			echo $running_total;
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
		echo "no code or tag";
	}
	
?>
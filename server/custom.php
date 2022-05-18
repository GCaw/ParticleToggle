<html>
<title>Toggle Custom</title>

<body>

<?php
	include('config.php');
	include('utctime.php');
        
	if (isset($_GET['code']) && isset($_GET['tag']) && isset($_GET['event']) && isset($_GET['eventtime']))
	{
		if ($_GET['code'] == $client_key)
		{
			echo "code: " . $_GET['code'] . " - tag: " . $_GET['tag'] . " - event: " . $_GET['event'] . " - eventtime: " . $_GET['eventtime'] . " - utc: " . $utcnow;
			
			if ($_GET['eventtime'] == "")
			{
				http_response_code(400);
				echo "<br />";
				echo "no time";
			}
			else
			{
				
				// exp: 2021-07-13T16:21
				// des: 2021-07-13 13:20:37
				$exp_format = "%Y-%m-%dT%H:%M";
				$des_format = "%Y-%m-%d %H:%M:%S";
				$dt = strtotime($_GET['eventtime']);
				if ($dt)
				{
					echo "<br />";
					echo $dt;
					$utc_time = strftime($des_format, $dt);
					echo " - Parsed: " . $utc_time;
					
					try
					{
						$con = new PDO($hostdb, $user, $pass);
						$result = $con->query("INSERT INTO events (time, tag, type) VALUES ('" . $_GET['eventtime'] . "','" . $_GET['tag'] . "','" . $_GET['event'] ."');");
						echo "Request added";
					} 
					catch(PDOException $ex)
					{
						http_response_code(500);
						echo "An Error occured!";
						die();
					}
					$con = null;
				}
				else
				{
					echo "<br />";
					echo "couldn't parse date";
				}
			}
			
		}		
		else
		{
			http_response_code(403);
			echo "wrong code";
			// return denied
		}
		
		// force redirect after 5s
		$retweb = "SERVERADDRESS/summary.php?code=" . $_GET['code'] . "&tag=" . $_GET['tag'];
		echo "<meta http-equiv='refresh' content='5; URL=" . $retweb ."' />";

	}
	else
	{
		http_response_code(400);
		echo "no code or tag";
	}
?>

</body>
</html>
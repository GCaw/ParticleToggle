<html>
<title>Toggle Stop</title>

<body>

<?php
	include('config.php');
	include('utctime.php');
        
	if (isset($_GET['code']) && isset($_GET['tag']))
	{
		if ($_GET['code'] == $client_key)
		{
			try
			{
				$con = new PDO($hostdb, $user, $pass);
				$result = $con->query("INSERT INTO events (time, tag, type) VALUES ('" . $utcnow . "','" . $_GET['tag'] . "','2');");
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
			http_response_code(403);
			echo "wrong code";
			// return denied
		}
		
		// force redirect after 2s
		$retweb = "SERVERADDRESS/summary.php?code=" . $_GET['code'] . "&tag=" . $_GET['tag'];
		echo "<meta http-equiv='refresh' content='2; URL=" . $retweb ."' />";
	}
	else
	{
		http_response_code(400);
		echo "no code or tag";
	}
	
?>

</body>
</html>
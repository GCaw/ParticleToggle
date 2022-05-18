<html>
<title>Toggle Running</title>

<body>

<?php
	include('config.php');
	include('utctime.php');
        
	if (isset($_GET['code']) && isset($_GET['tag']))
	{
		if ($_GET['code'] == $client_key)
		{
			$quer = "";
		
			
			$quer = "SELECT * FROM events WHERE tag = " . $_GET['tag'];
			$quer = $quer . " ORDER BY time DESC;";
			$running = false;
			
			try
			{
				$con = new PDO($hostdb, $user, $pass);
				$res = $con->query($quer);
				if ($res){
					if ($row = $res->fetch())
					{
						if($row['type'] == 1)
						{
							$running = true;
						}
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
			if ($running)
			{
				echo 1;
			}
			else
			{
				echo 0;
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
		echo "no code or tag";
	}
	
?>

</body>
</html>
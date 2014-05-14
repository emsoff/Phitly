<?php 
	require "bit.class.php";

	$user_config = array(
			'redirect_to' => 'REPLACE_THIS_VALUE',
			'username' => 'REPLACE_THIS_VALUE',
		);

	$app_config = array(
			'client_id' => 'REPLACE_THIS_VALUE',
			'secret' => 'REPLACE_THIS_VALUE',
		);

	$_p = new Phitly($user_config, $app_config);

?>

<html>
	<head>
		<title>Test of Phitly</title>
	</head>
	<body>
		<div style="width: 500px; margin: 0px auto;">
			<h1>My clicks</h1>
			<table>
				<tr>
					<td style="width: 100px">Date</td>
					<td>Visits</td>
				</tr>

		<?php 	
			$foo = $_p->user_clicks();
			foreach ($foo->data->clicks as $data) {
				echo "<tr><td>" . date('Y-m-d', $data->day_start) . "</td><td>" . number_format($data->clicks) . "</td></tr>";
			}
		?>
		
				<tr>
					<td><strong>Total:</strong></td>
					<td><?php echo number_format($foo->data->total_clicks); ?></td>
				</tr>
			</table>
		</div>
	</body>
</html>



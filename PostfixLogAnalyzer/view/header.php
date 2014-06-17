<!doctype html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;  charset=utf-8" />
		<title>Simple Postfix LogAnalyzer</title>
		<link rel='stylesheet' type="text/css" href="PostfixLogAnalyzer/view/styles.css">
	</head>
	<body>
		<div class="maincontent">

		<form name="filterform">
			<table border="0" cellpadding="5" cellspacing="5">
				<tr>
					<td colspan="2">
						<a name="begin" /></a>
						<h3>Simple Postfix LogAnalyzer</h3>
					</td>
				</tr>
				<tr>
					<td width="200px">
						Log FILENAME
						<br/><small>Full path or filename</small>
					</td>
					<td>
						<input type="text" name="pathToLog" value="<?=$pla->pathToLog ?>" size="12" />
					</td>
				</tr>
				<tr>
					<td width="200px">
						Enter QUEUE ID
						<br/><small>Full or part of ID.<br/> You can search NOQUEUE</small>
					</td>
					<td>
						<input type="text" name="queueId" value="<?=$pla->filter["queueId"] ?>" size="12" />
						<input type="button" value="NOQUEUE" OnClick="document.filterform.queueId.value = 'NOQUEUE'" />
					</td>
				</tr>
				<tr>
					<td>
						Enter DATE FROM/TO
					</td>
					<td>
						<input type="text" name="dayfrom" value="<?=$pla->filter["dayfrom"] ?>" size="3" />
						<input type="text" name="monthfrom" value="<?=$pla->filter["monthfrom"] ?>" size="3" />
						-
						<input type="text" name="dayto" value="<?=$pla->filter["dayto"] ?>" size="3" />
						<input type="text" name="monthto" value="<?=$pla->filter["monthto"] ?>" size="3" />
					</td>
				</tr>
				<tr>
					<td>
						Enter HOUR FROM/TO
						<br/><small>Like hh</small>
					</td>
					<td>
						<input type="text" name="timefrom" value="<?php if (!isset($_GET["timefrom"])) {	echo "00";} else {	echo $_GET["timefrom"];} ?>" size="3" />
						-
						<input type="text" name="timeto" value="<?php if (isset($_GET["timeto"])) echo $_GET["timeto"];  ?>" size="3" />
					</td>
				</tr>
				<tr>
					<td>
						GREP
						<br/><small>Works like Unix GREP</small>
					</td>
					<td>
						<input type="text" name="email" value="<?=$pla->filter["email"] ?>" size="12" />
					</td>
				</tr>
				<tr>
					<td>
						Select STATUS
					</td>
					<td>
						<select name="status">
							<option value="all">ALL</option>
							<option value="errors" <?php if ($pla->filter["status"] == "errors") {	echo "selected";} ?>>ERRORS</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						LIMIT
					</td>
					<td>
						<input type="text" name="limit" value="<?=$pla->filter["limit"] ?>" size="4" />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<input type="submit" value="View" />
						<input type="button" value="Default" OnClick="window.location.href = 'index.php'" />
					</td>
				</tr>
			</table>
		</form>

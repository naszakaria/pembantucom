<?php
$host = "localhost";
$user = "busanaco_eicra";
$pass = "enteraja";
$dbName = "busanaco_eicra";
mysql_connect($host, $user, $pass);
mysql_select_db($dbName)
or die ("Connect Failed !! : ".mysql_error());
?>
<!DOCTYPE html>
<html>
<head>
	<title>qr2svg</title>
	<style>
	  img { float: left; margin-left: 20px; border: 1px solid #000;}
	</style>
</head>
<body>
	
<?php


// Convert File
include 'qr2svg.php';
$input = 'qr4.png';
$output = 'qr4.svg';
$svg = qr2svg::convert($input, '#00f')->save($output);

$size = 100;

?>

<br/><br/>

<img src="<?=$input?>" width="<?=$size?>" height="<?=$size?>" alt=""/>
<img src="<?=$output?>" width="<?=$size?>" height="<?=$size?>" alt=""/>

</body>
</html>
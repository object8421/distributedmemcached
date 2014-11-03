<?php

ini_set( "memcache.hash_strategy", "consistent" );

require "conf.php";

$mem = new Memcache ;

foreach( $servers as $server )
{
	list( $host , $port ) = explode(":",$server) ;
	$mem->addServer($host,$port);
}

$total = 1000000 ;
$succ = 0 ;
for($i=0;$i<$total;$i++)
{
	$succ += $mem->get("item$i") ? 1 : 0 ;
}

$ret = $mem->getExtendedStats();
$mem->close();

echo "host\t\ttotal_items\tget_misses\tget_hits\n";
foreach($ret as $key=>$value )
{
	echo $key."\t".$value['total_items']."\t\t".$value['get_misses']."\t\t".$value['get_hits'].PHP_EOL ;
}

echo "success:$succ\t\tfail:".($total-$succ);


?>

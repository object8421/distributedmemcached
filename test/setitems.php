<?php

ini_set( "memcache.hash_strategy", "consistent" );

require "conf.php";

$mem = new Memcache ;

foreach( $servers as $server )
{
	list( $host , $port ) = explode(":",$server) ;
	$mem->addServer($host,$port);
}

for($i=0;$i<1000000;$i++)
{
	$mem->add("item$i" , 1 );
}
$mem->close();

echo "ok" ;
?>

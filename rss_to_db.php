<?php

/*

==================================================================
==================================================================
RSS Ingest v 0.1
- by Daniel Iversen, daniel (a dot) iversen (the at sign) gmail (another dot) com
------------------------------------------------------------------

INTRO:
- - - - - - - - -
 - Just a tiny primitive PHP script that you can run on a cron to ingest
   an RSS feed into a flat database table.

 - You can run the PHP many times with different RSS URLs

 - Its so simple to use; 1 php file, 4 parameters to change and you are done

 - Its super simple code - you can easily fiddle with it

 - If you find it useful, please drop a mail ;)


INSTALLATION AND USAGE:
- - - - - - - - - - - - - - -
 - Well its free, no license or warranty for this tool - if your house burns or your cat gets sick don not blame  me ;)
 - Change the few variables in this PHP (db connection info and access key/password)
	The 4 variables you need to look for in this PHP (usually grouped) are;

	$db_hostname="     - ....your db hostname here - maybe 'localhost' is enough...
	$db_username="     - ....your db username here...
	$db_password="     - ....your db password here...
	mysql_select_db("  - ....your db name here...

$private_access_key="youraccesskey";

 - create table using SQL below
 - create table indexes if needed
 - upload PHP to your server
 - hit the PHP with the URL:
     http://<your server>/<RSSIngest path>/<PHP file>?feed_url=<your RSS URL - must stay the same as its a key>&access_key=<secret key>
	 e.g.
     http://nexle.dk/rssingest/index.php?feed_url=http://www.instapaper.com/starred/rss/580483/qU7TKdkHYNmcjNJQSMH1QODLc&access_key=ThisIsNotMyKey
 - watch for errors and things
 - if all is o.k., install the PHP URL in a cron job (e.g. the free setcronjob.com) and relax ;)
 - If you do find it useful, please shoot me a mail, would love to know
 - If you make bug fixes, please feel free to send back for everyones benefit



CREATE TABLE SQL:
- - - - - - - - - - - - - - -
	CREATE TABLE  `rssingest` (
	 `item_id` VARCHAR( 32 ) NOT NULL ,
	 `feed_url` VARCHAR( 512 ) NOT NULL ,
	 `item_content` VARCHAR( 4000 ) NULL ,
	 `item_title` VARCHAR( 255 ) NOT NULL ,
	 `item_date` TIMESTAMP NOT NULL ,
	 `item_url` VARCHAR( 512 ) NOT NULL ,
	 `item_status` CHAR( 2 ) NOT NULL ,
	 `item_category_id` INT NULL ,
	 `fetch_date` TIMESTAMP NOT NULL
	) ENGINE = MYISAM ;



NOTES:
- - - - -
 - Only tested on MySQL
 - Done in an hour so there could be lots of bugs
 - Only tested with my particular RSS feed that I needed to ingest (Instapaper)
 - No attempt to prevent SQL injection but should be o.k. since its password protected and for your own use only


==================================================================
==================================================================

*/
//require_once("dbCon/dbcon.php");

$db_hostname="localhost";
$db_username="yourusername";
$db_password="yourpassword";

$private_access_key="youraccesskey";

// Check a few bits and pieces

if(isset($_GET['feed_url']))
{
	$feed_url = $_GET['feed_url'];
}
else
{
	die("Need to pass the (consistent) 'feed url'");
}


if(isset($_GET['access_key']))
{

	if($_GET['access_key']==$private_access_key)
	{
		echo "Access key correct, proceeding...<br/><br/>";
	}
	else
	{
		die("wrong access key");
	}
}
else
{
	die("Need to pass the 'access_key' URL parameter");
}


try
{
	/*  query the database */
	// $db = getCon();

	$db = mysql_connect($db_hostname,$db_username,$db_password);
	if (!$db)
	{
		die("Could not connect: " . mysql_error());
	}
	mysql_select_db("your_db", $db);

	echo "Starting to work with feed URL '" . $feed_url . "'";

	/* Parse XML from  http://www.instapaper.com/starred/rss/580483/qU7TKdkHYNmcjNJQSMH1QODLc */
	//$RSS_DOC = simpleXML_load_file('http://www.instapaper.com/starred/rss/580483/qU7TKdkHYNmcjNJQSMH1QODLc');

	libxml_use_internal_errors(true);
	$RSS_DOC = simpleXML_load_file($feed_url);
	if (!$RSS_DOC) {
		echo "Failed loading XML\n";
		foreach(libxml_get_errors() as $error) {
			echo "\t", $error->message;
		}
	}


	/* Get title, link, managing editor, and copyright from the document  */
	$rss_title = $RSS_DOC->channel->title;
	$rss_link = $RSS_DOC->channel->link;
	$rss_editor = $RSS_DOC->channel->managingEditor;
	$rss_copyright = $RSS_DOC->channel->copyright;
	$rss_date = $RSS_DOC->channel->pubDate;

	//Loop through each item in the RSS document

	foreach($RSS_DOC->channel->item as $RSSitem)
	{

		$item_id 	= md5($RSSitem->title);
		$fetch_date = date("Y-m-j G:i:s"); //NOTE: we don't use a DB SQL function so its database independant
		$item_title = $RSSitem->title;
		$item_date  = date("Y-m-j G:i:s", strtotime($RSSitem->pubDate));
		$item_url	= $RSSitem->link;

		echo "Processing item '" , $item_id , "' on " , $fetch_date 	, "<br/>";
		echo $item_title, " - ";
		echo $item_date, "<br/>";
		echo $item_url, "<br/>";

		// Does record already exist? Only insert if new item...

		$item_exists_sql = "SELECT item_id FROM rssingest where item_id = '" . $item_id . "'";
		$item_exists = mysql_query($item_exists_sql, $db);
		if(mysql_num_rows($item_exists)<1)
		{
			echo "<font color=green>Inserting new item..</font><br/>";
			$item_insert_sql = "INSERT INTO rssingest(item_id, feed_url, item_title, item_date, item_url, fetch_date) VALUES ('" . $item_id . "', '" . $feed_url . "', '" . $item_title . "', '" . $item_date . "', '" . $item_url . "', '" . $fetch_date . "')";
			$insert_item = mysql_query($item_insert_sql, $db);
		}
		else
		{
			echo "<font color=blue>Not inserting existing item..</font><br/>";
		}

		echo "<br/>";
	}

	// End of form //
} catch (Exception $e)
{
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
?>
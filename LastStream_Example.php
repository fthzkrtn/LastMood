<?php

// require the class
require("LastStream.php");

// create a new instance of the class
$lsfm = new LastStream;

// login to the lastfm api
$lsfm->login( "username", "password" );

// from this point, you have access to the follow API calls

$lsfm->tune_user("username"); // tune to a users library station
$lsfm->tune_artist("artist"); // tune to a similar artists station
$lsfm->tune_tag("genre"); // tune to a station based on supplied tags


// if tune_ has not been called at this point, api will tune to "tune_user('your username')"

$playlist = $lsfm->get_playlist(); // get a nicly formated array of songs with there info
$xspf = $lsfm->get_playlist(true); // get a xspf formated response, this is what we get from the API

$playing = $lsfm->now_playing(); // get an array with info about what the user is currently listening to

// You can then use the playlist array and format it for your player, or do what you want with it.

?>
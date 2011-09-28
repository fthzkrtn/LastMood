<?php

class LastStream
{
	
	private $lfmversion = "1.1.1";
	private $lfmplatform = "linux";
	
	/**
	 * Now playing method
	 */
	
	public function now_playing()
	{
		$url = 'http://ws.audioscrobbler.com/radio/np.php';
		$url.= '?session=' . $this->session;
		$response = $this->get_response($url);
		if($response['streaming'] == 'flase'){
			return false;
		}
		else {
			return $response;
		}
	}
	
	
	/**
	 * Radio station tuning methods
	 */
	
	public function tune_user($user)
	{
		$url = 'http://ws.audioscrobbler.com/radio/adjust.php';
		$url.= '?session=' . $this->session;
		$url.= '&url=' . "user/{$user}/personal";
		$response = $this->get_response($url);
		if($response['response'] == 'OK'){
			$this->station = $response['stationname'];
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
	
	public function tune_tag($tag)
	{
		$url = 'http://ws.audioscrobbler.com/radio/adjust.php';
		$url.= '?session=' . $this->session;
		$url.= '&url=' . "globaltags/{$tag}";
		$response = $this->get_response($url);
		if($response['response'] == 'OK'){
			$this->station = $response['stationname'];
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
	
	public function tune_artist($artist)
	{
		$artist = preg_replace('/ /', '%20', $artist);
		$url = 'http://ws.audioscrobbler.com/radio/adjust.php';
		$url.= '?session=' . $this->session;
		$url.= '&url=' . "artist/{$artist}/similarartist";
		$response = $this->get_response($url);
		if($response['response'] == 'OK'){
			$this->station = $response['stationname'];
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
	
	
	/**
	 * Playlist retrieval methods
	 */
	
	public function get_playlist($follow_stream = false)
	{
		if(!isset($this->station)){
			$this->tune_user($this->username);
		}
		$raw_xml = $this->get_xspf();
		$xml = simplexml_load_string($raw_xml);
	
		$i = 0;
		foreach($xml->trackList->track as $track){
			// there has to be a better way to get the value instead
			// of the object, but addings '' . causes it to typecast
			// to a string from an object
			$playlist[$i]['title']	= '' . $track->title;
			$playlist[$i]['artist']	= '' . $track->creator;
			$playlist[$i]['album']	= '' . $track->album;
			$playlist[$i]['length']	= '' . ( $track->duration / 1000 );
			$playlist[$i]['image']	= '' . $track->image;
			$playlist[$i]['mp3'] 	= '' . $track->location;
			
			if($follow_stream === true){
				// the mp3 url redirects to the real stream location
				// set $follow_stream to true and it will return the
				// actual stream location, not sure how this works
				// when actually streaming the content, the stream
				// url might only be good for a short peroid of time
				// so this is not recomended, but is useful in some
				// casses.
				$playlist[$i]['stream'] = '' . $this->follow_stream($track->location);
			}
			
			$i++;
		}
		return $playlist;
	}
	
	private function get_xspf()
	{
		$url = 'http://ws.audioscrobbler.com/radio/xspf.php';
		$url.= '?sk=' . $this->session;
		$url.= '&discovery=0';
		$url.= '&desktop=0';
		$response = $this->do_request($url);
		return $response;
	}
	
	
	/**
	 * Authentication method
	 */
	
	public function login($username, $password)
	{
		$this->username = $username;
		return $this->get_session($username, $password);
	}
	
	private function get_session($username, $password)
	{
		$url = 'http://ws.audioscrobbler.com/radio/handshake.php';
		$url.= '?username=' . $username ;
		$url.= '&version=' . $this->lfmversion ;
		$url.= '&platform=' . $this->lfmplatform ;
		$url.= '&passwordmd5=' . md5( $password ) ;
		$response = $this->get_response($url);
		if($response['session'] == "FAILED"){
			return false;
		}
		else {
			$this->session = $response['session'];
			return true;
		}
	}
	
	
	/**
	 * Communication methods
	 */
	
	private function get_response($url)
	{
		$content = $this->do_request($url);
		$response = $this->parse_response($content);
		return $response;
	}
	
	private function parse_response($response)
	{
		// this sexy little line of code right here, mad props to d3x on 
		// freenode ##php channel for helping me and writing this up
		parse_str(strtr($response, array('&' => '%26', "\n" => '&')), $return);
		return $return;
	}
	
	private function do_request($url)
	{
		$options = array( 
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER => 0
		);
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
	
	public function follow_stream($song){
		
		$options = array(
			CURLOPT_URL => $song,
			CURLOPT_FOLLOWLOCATION => 0,
			CURLOPT_HEADER => 1,
			CURLOPT_RETURNTRANSFER => 1
		);
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$data = curl_exec($ch);
		curl_close($ch);
		preg_match('/Location:(.*)/', $data, $match);
		return $match[1];
	}
	
	
	/**
	 * Error handling methods
	 */
	
	private function error($message)
	{
		die("\nError: " . $message . "\n");
	}
	
	private function _ec($ec)
	{
		// these are NOT the same as the current api v2 errors
		$errors = array(
			1 => 'There is not enough content to play the station.',
			2 => 'The group does not have enough members to have a radio station.',
			3 => 'The artist does not have enough fans to have a radio station.',
			4 => 'The station is not available for streaming, or not found.',
			5 => 'The station is available to subscribers only.',
			6 => 'The user does not have enough neighbors to have a radio station.',
			7 => 'The stream has stopped. Please try again later, or try another station.',
			8 => 'The stream has stopped. Please try again later, or try another station.'
		);
		if(isset($errors[$ec])){
			return $errors[$ec];
		}
		else {
			return $errors[1];
		}
	}
	
}

?>
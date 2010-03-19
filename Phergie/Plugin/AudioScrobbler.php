<?php

/**
 * Blah blah blah
 */
class Phergie_Plugin_AudioScrobbler extends Phergie_Plugin_Abstract
{
	/**
	 * Last.FM API entry point
	 *
	 * @var string
	 */
	private $lastfm_url = 'http://ws.audioscrobbler.com/2.0/';
	
	/**
	 * Libre.FM API entry point
	 *
	 * @var string
	 */
	private $librefm_url = 'http://alpha.dev.libre.fm/2.0/';
	
	/**
	 * Scrobbler query string for user.getRecentTracks
	 *
	 * @var string
	 */
	private $query = '?method=user.getrecenttracks&user=%s&api_key=%s';
	
	/**
	 * check and load plugin's dependencies.
	 *
	 * @return void
	 */
	public function onLoad()
	{
		if (!extension_loaded('simplexml')) {
			$this->fail('SimpleXML php extension is required');
		}
		
		$this->getPluginHandler()->getPlugin('Command');
	}
	
	/**
	 * Command function to get users status on last.fm
	 * 
	 * @return void
	 */
	public function onCommandLastfm($user = null)
	{
		if ($this->config['audioscrobbler.lastfm_api_key']) {
			$scrobbled = $this->getScrobbled($user, $this->lastfm_url, $this->config['audioscrobbler.lastfm_api_key']);
			$this->doPrivmsg($this->getEvent()->getSource(), $scrobbled);
		}
	}

	/**
	 * Command function to get users status on libre.fm
	 * 
	 * @return void
	 */
	public function onCommandLibrefm($user = null)
	{
		if ($this->config['audioscrobbler.librefm_api_key']) {
			$scrobbled = $this->getScrobbled($user, $this->librefm_url, $this->config['audioscrobbler.librefm_api_key']);
			$this->doPrivmsg($this->getEvent()->getSource(), $scrobbled);
		}
	}

	/**
	 * Simple Scrobbler API function to get recent track formatted in string
	 * 
	 * @param string $user The user name to lookup
	 * @param string $url The base URL of the scrobbler service
	 * @param string $api_key The scrobbler service api key
	 * @return string A formatted string of the most recent track played.
	 */
	public function getScrobbled($user, $url, $api_key)
	{
		$user = $user ? $user : $this->getEvent()->getNick();
		$url = sprintf($url . $this->query, urlencode($user), urlencode($api_key));
		
		$response = file_get_contents($url);
		try {
			$xml = new SimpleXMLElement($response);
		}
		catch (Exception $e) {
			return 'Can\'t find status for ' . $user;
		}
		
		if ($xml->error) {
			return 'Can\'t find status for ' . $user;
		}
		
		$recenttracks = $xml->recenttracks;
		$track = $recenttracks->track[0];
		if (isset($track['nowplaying'])) {
			$msg = sprintf("%s is listening to %s by %s", $recenttracks['user'], $track->name, $track->artist);
		}
		else {
			$msg = sprintf("%s, %s was listening to %s by %s",  $track->date, $recenttracks['user'], $track->name, $track->artist);
		}
		if ($track->streamable == 1) {
			$msg .= ' - ' . $track->url;
		}
		return $msg;
	}
}

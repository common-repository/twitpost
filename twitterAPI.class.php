<?php
/*  Copyright 2009  C. Finegan  (email : contact@indeedle.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class twitterAPI
{
	/**
	 * Credentials, stores the username & password
	 *
	 * @string unknown_type
	 */
	private $creds;
	/**
	 * Stores the returned HTTP status if needed
	 *
	 * @array unknown_type
	 */
	private $httpStatus;
	
	function __construct($username, $password)
	{
		// Store the twitter credentials in the username:password format
		$this->creds = $username.':'.$password;
	}
	
	/**
	 * Validates the login session and will return 1 if successful or 2 if not
	 *
	 * @int unknown
	 */
	public function validateLogin()
	{
		// We're going to be using CURL to contact
		$curlHandler = curl_init();
		
		// Set the options
		@curl_setopt($curlHandler, CURLOPT_URL, "http://twitter.com/account/verify_credentials.xml");
		@curl_setopt($curlHandler, CURLOPT_USERPWD, $this->creds);
		@curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
		
		// Added to prevent the 417 http error, thanks to brettcave @ http://wordpress.org/support/topic/267720
		@curl_setopt($curlHandler, CURLOPT_HTTPHEADER, array('Expect:'));
		$data = @curl_exec($curlHandler);

		// Record the status for future use
		$status = @curl_getinfo($curlHandler);
			
		// Close the connecton
		@curl_close($curlHandler);
				
		if($status['http_code'] == 200)
			return 1;
		else
			return 2;
	}
	
	/**
	 * Calls the Twitter API service
	 *
	 * @param string $url The service to contact
	 * @param bool $login Requires authentication? Default is false
	 * @param bool $http Form data being sent? Default is false
	 * @return mixed The data from the curl session
	 */
	private function callTwitterAPI($url, $login=false, $http=false)
	{
		// We're going to be using CURL to contact
		$curlHandler = @curl_init();
		
		// Set the options
		@curl_setopt($curlHandler, CURLOPT_URL, $url);
		
		// If we have to login, set it now
		if($login)
			@curl_setopt($curlHandler, CURLOPT_USERPWD, $this->creds);
			
		// If we're sending http stuff, set the option noew
		if($http)
			@curl_setopt($curlHandler, CURLOPT_POST, true);
		
		// Added to prevent the 417 http error, thanks to brettcave @ http://wordpress.org/support/topic/267720
		@curl_setopt($curlHandler, CURLOPT_HTTPHEADER, array('Expect:'));
			
		// We're getting stuff back (hopefully) so set that option
		@curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
				
		// It's time to call the API :)
		$data = @curl_exec($curlHandler);	
		
		// Record the status for future use
		$this->httpStatus = @curl_getinfo($curlHandler);
			
		// Close the connecton
		@curl_close($curlHandler);
		
				
		// return the good stuff
		return $data;
	}
	
	/**
	 * Adds the status to twitter
	 *
	 * @param string $message
	 * @return mixed the curl data
	 */
	public function addStatus($message)
	{
		// Encode it, making sure to remove whitespace and slashes
		$message = stripslashes(trim($message));
		$message = urlencode($message);
		
		// Create the URL
		$call = sprintf("http://twitter.com/statuses/update.xml?status=%s", $message);
		return $this->callTwitterAPI($call, true, true);
	}
} ?>
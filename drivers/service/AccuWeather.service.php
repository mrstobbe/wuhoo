<?php

class AccuWeather extends ServiceDriver {
	static public $id = 'accuweather';
	static public $name = 'AccuWeather';

	public function getLocIDByZip($zip) {
		//#TODO: cache long-duration/permanently
		$url = 'http://dataservice.accuweather.com/locations/v1/postalcodes/search?apikey=' . $this->app->conf('accuweather.apikey') . '&q=' . rawurlencode($zip);
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL=>$url,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_FAILONERROR=>true
		]);
		$res = curl_exec($ch);
		if ($res === false)
			return null;
		if ((($res = json_decode($res, true)) === null) || (!isset($res[0])))
			return null;
		foreach ($res as $loc) {
			if ((isset($loc['Country'])) && ($loc['Country']['ID'] === 'US'))
				return $loc['Key'];
		}
		return null;
	} //getLocIDByZip()

	public function getCurrent($locID) {
		$url = 'http://dataservice.accuweather.com/currentconditions/v1/' . $locID . '?apikey=' . $this->app->conf('accuweather.apikey');
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL=>$url,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_FAILONERROR=>true
		]);
		$res = curl_exec($ch);
		if ($res === false)
			return null;
		if ((($res = json_decode($res, true)) === null) || (!isset($res[0])) || (!isset($res[0]['Temperature'])))
			return null;
		return (double)$res[0]['Temperature']['Imperial']['Value'];
	} //getCurrent()

	public function getHourly($locID) {
		$url = 'http://dataservice.accuweather.com/forecasts/v1/hourly/24hour/' . $locID . '?apikey=' . $this->app->conf('accuweather.apikey');
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL=>$url,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_FAILONERROR=>true
		]);
		$res = curl_exec($ch);
		if ($res === false)
			return null;
		//#TODO: If I had a API key that allowed for this call, I could implement this
		/*
		if ((($res = json_decode($res, true)) === null) || (!isset($res['Temperature'])))
			return null;
		return (double)$res['Temperature']['Imperial']['Value'];
		*/
	} //getHourly()

	public function query($zip) {
		if (($locID = $this->getLocIDByZip($zip)) === null)
			return null;
		if (($current = $this->getCurrent($locID)) === null)
			return null;
		$hourly = null;
		if ((int)$this->app->conf('accuweather.hourly', false)) {
			if (($hourly = $this->getHourly($locID)) === null)
				return null;
		}
		$res = [ 'when'=>time(), 'current'=>$current ];
		if ($hourly !== null)
			$res['hourly'] = $hourly;
		return $res;
	} //query()
} //class AccuWeather


ServiceDriver::register('AccuWeather', __FILE__);

return;
?>

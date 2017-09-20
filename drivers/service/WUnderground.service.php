<?php

class WUnderground extends ServiceDriver {
	static public $id = 'wunderground';
	static public $name = 'Weather Undergound';

	public function getCurrent($zip) {
		$url = 'http://api.wunderground.com/api/' . $this->app->conf('wunderground.apikey') . '/conditions/q/' . rawurlencode($zip) . '.json';
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL=>$url,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_FAILONERROR=>true
		]);
		$res = curl_exec($ch);
		curl_close($ch);
		if ($res === false)
			return null;
		if ((($res = json_decode($res, true)) === null) || (!isset($res['current_observation'])) || (!isset($res['current_observation']['temp_f'])))
			return null;
		return (double)$res['current_observation']['temp_f'];
	} //getCurrent()

	public function getHourly($zip) {
		$url = 'http://api.wunderground.com/api/' . $this->app->conf('wunderground.apikey') . '/hourly/q/' . rawurlencode($zip) . '.json';
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL=>$url,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_FAILONERROR=>true
		]);
		$res = curl_exec($ch);
		curl_close($ch);
		if ($res === false)
			return null;
		if ((($res = json_decode($res, true)) === null) || (!isset($res['hourly_forecast'])))
			return null;
		$hourly = [ ];
		foreach ($res['hourly_forecast'] as $forcast) {
			$hourly[(int)$forcast['FCTTIME']['hour']] = (double)$forcast['temp']['english'];
		}
		ksort($hourly);
		return $hourly;
	} //getHourly()

	public function query($zip) {
		//#TODO: enable caching
		$current = $this->getCurrent($zip);
		if ($current === null)
			return null;
		$hourly = $this->getHourly($zip);
		if ($hourly === null)
			return null;
		return [
			'when'=>time(),
			'current'=>$current,
			'hourly'=>$hourly
		];
	}
} //class WUnderground

ServiceDriver::register('WUnderground', __FILE__);
return;
?>

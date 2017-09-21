<?php

class QueryAction extends Action {
	static public $defaultFormat = 'json';
	static public $caching = [
		'client'=>[
			'soft'=>false,
			'secs'=>30*60
		],
		'server'=>false
	];

	public function execute() {
		$provider = $this->param('provider', 'mean');
		$providers = ServiceDriver::getList();
		if (($provider !== 'mean') && (!isset($providers[$provider])))
			return $this->respBadParam("Invalid 'provider' parameter specified");
		$zip = $this->param('zip');
		if ($zip === null)
			return $this->respBadParam("Missing 'zip' parameter");
		$zip = trim($zip);
		if (!preg_match('`^[0-9]{5}(?:-[0-9]{4})?$`', $zip))
			return $this->respBadParam("Invalid 'zip' parameter specified");
		if ($provider === 'mean')
			return $this->getMean($zip);
		return $this->getSpecific($provider, $zip);
	} //execute()

	public function getMean($zip) {
		$providers = ServiceDriver::getList();
		
		$all = [ ];
		$withHourly = true;
		foreach ($providers as $id=>$name) {
			if (($all[$id] = $this->getResults($id, $zip)) === null)
				return $this->respError(500, 'Provider API failure');
			if (!isset($all[$id]['hourly']))
				$withHourly = false;
		}
		$hourly = null;
		$current = 0;
		foreach ($providers as $id=>$name)
			$current += $all[$id]['current'];
		$current /= count($all);
		if ($withHourly) {
			$hourly = [ ];
			for ($i = 0; $i !== 24; ++$i)
				$hourly[$i] = 0;
			foreach ($providers as $id=>$name) {
				foreach ($all[$id]['hourly'] as $i=>$temp)
					$hourly[$i] += $temp;
			}
			foreach ($hourly as $i=>$temp)
				$hourly[$i] /= count($all);
		}
		$res = [ 'when'=>time(), 'current'=>$current ];
		if ($hourly !== null)
			$res['hourly'] = $hourly;
		$res['provider'] = 'mean';
		return $this->respSuccess($res);
	} //getMean()

	public function getSpecific($provider, $zip) {
		if (($res = $this->getResults($provider, $zip)) === null)
			return $this->respError(500, 'Provider API failure');
		return $this->respSuccess($res);
	} //getSpecific()

	public function getResults($provider, $zip) {
		$service = ServiceDriver::getService($this->app, $provider);
		$res = $service->query($zip);
		if ($res !== null)
			$res['provider'] = $provider;
		return $res;
	} //getResults
} //class QueryAction


Action::register('QueryAction', __FILE__);

return;
?>

<?php

class DefaultAction extends Action {
	static public $defaultFormat = 'html';
	static public $caching = [
		'client'=>[
			'soft'=>true,
			'secs'=>15*60
		],
		'server'=>'infinite'
	];

	public function execute() {
		if ($this->path !== '/')
			return $this->respNotFound();

		return $this->respSuccess();
	}
} //class DefaultAction

Action::register('DefaultAction', __FILE__);

return;
?>

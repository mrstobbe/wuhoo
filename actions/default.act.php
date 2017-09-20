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
		//Essentially just serves up the main html

		if ($this->path !== '/')
			return $this->respNotFound();

		return $this->respSuccess();
	} //execute()
} //class DefaultAction

Action::register('DefaultAction', __FILE__);

return;
?>

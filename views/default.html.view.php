<?php

$providers = ServiceDriver::getList();
$provider = $resp->param('provider');
$zip = $resp->param('zip');

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<title>Wuhoo</title>

	<link rel="stylesheet" type="text/css" href="/css/main.css" />
	<script src="/js/wuhoo.js"></script>
</head>
<body>
	<article>
	<h1>Wuhoo</h1>
	<p>Wuhoo is a simple web-app that queries the current weather conditions given a zip-code (US only).</p>

	<form id="frmQuery" action="/" method="get">
		<div class="kvpair">
			<label class="kvpair-item kvpair-key" for="inpProvider">Provider</label>
			<span class="kvpair-item kvpair-val">
				<select name="provider" id="inpProvider">
					<option value="mean"<?= ($provider === null) ? ' selected' : '' ?>>Average of all</option>
					<?php foreach($providers as $id=>$name): ?>
						<option id="<?= Util::htmlEnc($id) ?>"<?= ($provider === $id) ? ' selected' : '' ?>><?= Util::htmlEnc($name) ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
		<div class="kvpair">
			<label class="kvpair-item kvpair-key" for="inpZip">Zip code</label>
			<span class="kvpair-item kvpair-val">
				<input id="inpZip" name="zip" value="<?= Util::htmlEnc($zip) ?>" />
			</span>
			<span class="kvpair-item kvpair-aside">May be a 5-digit (<em>ex: &quot;95816&quot;</em>) zip-code, or a full 9-digit (<em>ex: &quot;95816-3105&quot;</em>) zip-code for higher accuracy</span>
		</div>
		<div class="kvpair error-msg">
			<label class="kvpair-item kvpair-key"></label>
			<span id="errMsg" class="kvpai-item kvpair-val error-text"></span>
		</div>
		<div class="kvpair buttons">
			<label class="kvpair-item kvpair-key"></label>
			<span class="kvpair-item kvpair-val">
				<button id="btnSubmit" type="submit"><span class="btn-label">Lookup</span></button>
			</span>
		</div>
	</form>
	<div id="working" style="display: none;">Working... please wait.</div>
	<div id="results"><?php /* #TODO: results partial */ ?></div>
	</article>
	<script>wuhoo.init();</script>
</body>
</html>


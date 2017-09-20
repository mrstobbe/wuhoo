<?php

$providers = $app->getServiceList();
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
	<h1>Wuhoo</h1>
	<p>Wuhoo is a simple web-app that queries the current weather conditions given a zip-code (US only).</p>

	<form id="frmQuery" action="/" method="get">
		<div class="kvpair">
			<label class="kvpair-key" for="inpProvider">Provider</label>
			<span class="kvpair-val">
				<select name="provider" id="inpProvider">
					<option value="mean"<?= ($provider === null) ? ' default' : '' ?>>Average of all</option>
					<?php foreach($providers as $id=>$name): ?>
						<option id="<?= Util::htmlEnc($id) ?>"<?= ($provider === $id) ? ' default' : '' ?>><?= Util::htmlEsc($name) ?></option>
					<?php endforeach; ?>
				</select>
			</span>
			<span class="kvpair-aside"></span>
		</div>
		<div class="kvpair">
			<label class="kvpair-key" for="inpZip">Zip code</label>
			<span class="kvpair-val">
				<input id="inpZip" name="zip" value="<?= Util::htmlEnc($zip) ?>" />
			</span>
			<span class="kvpair-aside">May be a 5-digit (<em>ex: &quot;95816&quot;</em>) zip-code, or a full 9-digit (<em>ex: &quot;95816-3105&quot;</em>) zip-code for higher accuracy</span>
		</div>
	</form>
	<script>/* wuhoo.init(); */</script>
</body>
</html>


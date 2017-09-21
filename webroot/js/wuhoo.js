(function(g, dom, undef) {
'use strict';

var wuhoo = {
	working: false
};

function setError(msg) {
	wuhoo.formEl.className += ' error';
	while (wuhoo.errEl.firstChild !== null)
		wuhoo.errEl.removeChild(wuhoo.errEl.firstChild);
	wuhoo.errEl.appendChild(dom.createTextNode(msg));
}

function clearError() {
	wuhoo.formEl.className = wuhoo.formEl.className.replace(/(^|\s+)error\b/g, '');
}

function setWorking() {
	wuhoo.working = true;

	wuhoo.formEl.className += ' disabled';
	wuhoo.providerEl.setAttribute('disabled', 'disabled');
	wuhoo.zipEl.setAttribute('disabled', 'disabled');
	wuhoo.submitEl.setAttribute('disabled', 'disabled');

	wuhoo.workingEl.style.display = 'block';
}

function clearWorking() {
	wuhoo.formEl.className = wuhoo.formEl.className.replace(/(^|\s+)disabled\b/g, '');
	wuhoo.providerEl.removeAttribute('disabled');
	wuhoo.zipEl.removeAttribute('disabled');
	wuhoo.submitEl.removeAttribute('disabled');

	wuhoo.workingEl.style.display = 'none';

	wuhoo.working = false;
}

function makeResults(res) {
	while (wuhoo.curTempEl.firstChild !== null)
		wuhoo.curTempEl.removeChild(wuhoo.curTempEl.firstChild);
	wuhoo.curTempEl.appendChild(dom.createTextNode(Math.round(res.current) >> 0));
	while (wuhoo.hourlyEl.firstChild !== null)
		wuhoo.hourlyEl.removeChild(wuhoo.hourlyEl.firstChild);
	/*
 	//#TODO: Hourly is stubbed. Needs a lot of work to represent appropriately relative to the client's current time
 	/*
	if (!res.hourly) {
		wuhoo.hourlyEl.appendChild(dom.createTextNode('Hourly results are unavailable for your selected provider.'));
	} else {
		var table = dom.createElement('table');
		var tr = dom.createElement('tr');
		for (var i = 0; i !== 24; ++i) {
			var td = dom.createElement('td');
			td.appendChild(dom.createTextNode(Math.round(res.hourly[i]) >> 0));
			tr.appendChild(td);
		}
		table.appendChild(tr);
		wuhoo.hourlyEl.appendChild(table);
	}
	*/
	wuhoo.resultsEl.className += ' active';
}

function handleSubmit(e) {
	e.preventDefault();
	if (wuhoo.working)
		return;

	clearError();
	wuhoo.resultsEl.className = wuhoo.resultsEl.className.replace(/(^|\s+)active\b/g, '');

	var provider = wuhoo.providerEl.value.trim();
	var zip = wuhoo.zipEl.value.trim();
	wuhoo.zipEl.value = zip;
	var err = false;
	if (zip === '') {
		err = 'Please enter a zip-code';
	} else if (!/^[0-9]{5}(-[0-9]{4})?$/.test(zip)) {
		err = 'Invalid zip-code';
	}

	if (err !== false) {
		setError(err);
		return;
	}
	setWorking();
	var xhr = wuhoo.xhr = new XMLHttpRequest();
	xhr.open('GET', '/query/provider=' + encodeURIComponent(provider) + '/zip=' + encodeURIComponent(zip) + '.json', true);
	xhr.onerror = function(e) {
		setError('API Error: Could not make request');
		clearWorking();
	};

	xhr.onreadystatechange = function(e) {
		if (xhr.readyState === XMLHttpRequest.DONE) {
			var res = JSON.parse(xhr.responseText);
			if (res.statusCode === undef) {
				setError('API Error: Unexpected response from server');
			} else if (res.statusCode !== 200) {
				setError('API Error: ' + res.error);
			} else {
				makeResults(res.result);
			}
			clearWorking();
		}
	};
	xhr.send(null);
}

wuhoo.init = function() {
	if (!String.prototype.trim)
		String.prototype.type = function() { return this.replace(/^\s+/, '').replace(/\s+$/, '') };


	wuhoo.formEl = dom.getElementById('frmQuery');
	wuhoo.providerEl = dom.getElementById('inpProvider');
	wuhoo.zipEl = dom.getElementById('inpZip');
	wuhoo.submitEl = dom.getElementById('btnSubmit');
	wuhoo.resultsEl = dom.getElementById('results');
	wuhoo.workingEl = dom.getElementById('working');
	wuhoo.errEl = dom.getElementById('errMsg');
	wuhoo.curTempEl = dom.getElementById('curTemp');
	wuhoo.hourlyEl = dom.getElementById('hourly');

	wuhoo.formEl.addEventListener('submit', handleSubmit, false);
};




g.wuhoo = wuhoo;

})(this, document);

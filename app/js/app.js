//Ajax for loading in data
var request;
var networks;
var selected_network;
var general_data = new FormData();
var loader = document.getElementById('loader');
var button = document.getElementById("rescan");
var app = document.getElementById("app");

var modal = document.getElementById("modal");
var ssid_input = document.querySelector('[name="ssid"]');
var passphrase_input = document.querySelector('[name="passphrase1"]');
var button_connect = document.getElementById("connect");
var button_connect_confirm = document.getElementById("connect_confirm");

document.onkeydown = function(e) {
	if (e.key === 'Escape') {
		closeModal();
	}
}

//document.querySelector("#rescan").addEventListener('click', makeRequest);
button.addEventListener('click', function(){ makeRequest('ajax/get_nearby_networks.php', 'POST', general_data); });

button_connect.addEventListener('click', function() { 
	if (passphrase_input.value.length < 8 || passphrase_input.value.length > 64) {
		passphrase_input.classList.add("b-red-light");
		passphrase_input.classList.add("b-1");
		document.getElementById("help_message").classList.add("text-red");
		document.getElementById("help_message").classList.remove("text-grey");
	}
	else {
		closeModal(); 
		document.getElementById('passphrase').innerHTML = passphrase_input.value;
		document.getElementById('ssid').innerHTML = ssid_input.value;
		openModal('modal_confirm'); 
	}
	
});

button_connect_confirm.addEventListener('click', function() {
	var network_data = new FormData();
	network_data.append('ssid', ssid_input.value);
	network_data.append('passphrase', passphrase_input.value);
	selected_network.passphrase = passphrase_input.value;
	network_data.append('network', JSON.stringify(selected_network));
	makeRequest('ajax/connect_to_network.php', 'POST', network_data, handleSaveFileResponse);
});

function closeModal(id = null) {
	let m = null;
	if(!id) {
		m = document.getElementById("modal");
	}
	else {
		m = document.getElementById(id);
	}

	if (m.classList.contains('open')) {
		m.classList.toggle('open');
		app.classList.toggle('bg-grey-darkest');
		app.classList.toggle('opacity-25');
	}
}

function openModal(id = null) {
	let m = null;
	if(!id) {
		m = document.getElementById("modal");
		passphrase_input.classList.remove("b-red-light");
		passphrase_input.classList.remove("b-1");
		document.getElementById("help_message").classList.remove("text-red");
		document.getElementById("help_message").classList.add("text-grey");
	}
	else {
		m = document.getElementById(id);
	}
	if (!m.classList.contains('open')) {
		m.classList.toggle('open');
		app.classList.toggle('bg-grey-darkest');
		app.classList.toggle('opacity-25');

	}
}

function makeRequest(url, type = 'POST' , data = null, callback = null) {
	request = new XMLHttpRequest();

	if (!request) {
		//Problem making request
	  	return false;
	}
	if (data === null) {
		console.log("Defaulting form data.");
		data = new FormData();
	}

	loader.classList.remove('hidden');
	button.textContent = "Scanning...";
	button.disabled = true;
	button.classList.add('is-grey');
	button.classList.remove('is-primary');

	if (callback === null) {
		request.onreadystatechange = handleResponse;
	}
	else {
		request.onreadystatechange = callback;
	}
	request.open(type, url);
	// request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); //or application/json;charset=UTF-8
	setCSRFHeader(request, type, data);
	request.send(data);
}

function handleResponse() {
	if (request.readyState === XMLHttpRequest.DONE) {
	  if (request.status === 200) {
  		var response = JSON.parse(request.responseText);
  		networks = response;
    	//Request successful
    	loader.classList.add('hidden');
		button.textContent = "Rescan";
		button.disabled = false;
		button.classList.remove('is-grey');
		button.classList.add('is-primary');
    	addResults(response);

	  } else {
    	loader.classList.add('opacity-0');
		button.disabled = false;
		button.textContent = "Scan Failed. Refresh Page.";
		button.classList.remove('is-grey');
		button.classList.add('text-white');
		button.classList.add('bg-red');
    	//Problem with the request (500 error)
	  }
	}
}

function handleSaveFileResponse() {
	if (request.readyState === XMLHttpRequest.DONE) {
	  if (request.status === 200) {
  		var response = JSON.parse(request.responseText);
    	//Request successful
    	closeModal('modal_confirm');
    	alert(response.message);
    	loader.classList.add('hidden');


	  } else {
	  	alert(request.responseText);
    	loader.classList.add('hidden');
		button_connect_confirm.textContent = "File Save Failed!";
    	//Problem with the request (500 error)
	  }
	}
}

function setCSRFHeader(xhr, type, d) {
    var csrfToken = document.querySelector('meta[name=csrf_token]').getAttribute('content');
    if (/^(POST|PATCH|PUT|DELETE)$/i.test(type)) {
    	d.append("csrf_token", csrfToken);
        xhr.setRequestHeader("X-CSRF-Token", csrfToken);
    }
}

function addResults(results, elm = null) {
	if (!results) {
		return;
	}

	if (!elm) {
		elm = document.querySelector('#networks tbody');
	}
	elm.innerHTML = '';

	for (var network in results) {
		let row = document.createElement('tr');
		row.innerHTML = `<td>${results[network]['ssid'].includes('\\x00') ? '<strong>HIDDEN</strong>' : results[network]['ssid']}</td> <td>${results[network]['rssi']}</td> <td>${results[network]['channel']}</td> <td>${results[network]['protocol']}</td> <td>${results[network]['macAddress']}</td> <td class="actions opacity-0"><button class="button hover:bg-primary-light text-underline action connect-button" id="button${results[network]['ssid']}" data-name="${results[network]['ssid']}">Connect</button></td>`;
		elm.append(row);
	}


	var table_rows = document.querySelectorAll("#networks tbody tr");

	table_rows.forEach(row => row.addEventListener("mouseover", function(){ 
		for (let elm of row.children) {
			if (elm.matches('.actions')) {
				elm.classList.remove('opacity-0');
			}
		}
	}));

	table_rows.forEach(row => row.addEventListener("mouseout", function(){ 
		for (let elm of row.children) {
			if (elm.matches('.actions')) {
				elm.classList.add('opacity-0');
			}
		}
	}));

	var buttons = document.querySelectorAll("#networks tbody tr .actions .action");

	buttons.forEach(button => button.addEventListener('click', function(e) {
			selected_network = networks[event.target.dataset.name];
			ssid_input.value = selected_network.ssid ? selected_network.ssid : '';
			passphrase_input.value = selected_network.passphrase ? selected_network.passphrase : '';
			openModal();
		}));

	buttons.forEach(button => button.addEventListener("mouseover", function(){ 
		if (button.parentElement.matches('.actions')) {
			button.parentElement.classList.remove('opacity-0');
		}
	}));

	buttons.forEach(button => button.addEventListener("mouseout", function(){ 
		if (button.parentElement.matches('.actions')) {
			button.parentElement.classList.add('opacity-0');
		}
	}));
	
}
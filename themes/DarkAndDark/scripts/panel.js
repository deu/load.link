/* Uploader */
var uploader_form = document.getElementById('uploader_form');
var uploader_container = document.getElementById('uploader_container');
var uploader_box = document.getElementById('uploader_box');
var uploader_path = document.getElementById('uploader_path');
var uploader_file = document.getElementById('uploader_file');
var uploader_button = document.getElementById('uploader_button');
var uploader_shownname = '';
var uploader_progress = document.getElementById('uploader_progress');
var progress_filename = document.getElementById('progress_filename');
var progress_percent = document.getElementById('progress_percent');
var progress_sent = document.getElementById('progress_sent');
var progress_total = document.getElementById('progress_total');
function basename(path)
{
	return path.match(/[^\/\\]+$/)[0];
}
function upload_files(index)
{
	var items = uploader_file.files.length;
	var index = (index) ? index : 0;
	var file = uploader_file.files[index];

	var data = new FormData();

	var headers = {
		action: 'upload',
		token: token,
		filename: basename(file.name)
	};

	data.append('headers', new Blob([ JSON.stringify(headers) ],
		{ type: 'application/json' }));

	data.append('data', file);

	var xhr = new XMLHttpRequest();

	var box = null;

	xhr.upload.addEventListener("loadstart", function(event) {
		uploader_button.disabled = true;
		uploader_box.style.display = 'none';
		uploader_progress.style.display = 'block';
		progress_percent.innerHTML = '';
		progress_sent.innerHTML = '';
		progress_total.innerHTML = '';
		progress_filename.innerHTML = ((items > 1)
			? '(' + (index + 1) + '/' + items + ') ' : '')
			+ basename(file.name);
	});

	xhr.upload.addEventListener("progress", function(event) {
		var percent = event.loaded / event.total;
		progress_bar.value = percent;
		progress_percent.innerHTML = Math.round(percent * 100) + '%';
		progress_sent.innerHTML = bytes_to_hr(event.loaded);
		progress_total.innerHTML = bytes_to_hr(event.total);
	});

	xhr.upload.addEventListener("loadend", function(event) {
		progress_bar.removeAttribute('value');
	});

	xhr.open('POST', uploader_form.action, true);
	xhr.onload = function(r) {
		switch (this.status)
		{
			case 201:
				response = JSON.parse(this.responseText);
				setMessage('notice', 'Uploaded!<br>'
					+ '<a target="_blank" href="' + response.link + '">'
					+ response.link + '</a>');
				uploader_path.innerHTML = default_uploader_text;
				uploader_filename = '';
				update_history();
				break;
			case 202:
				response = JSON.parse(this.responseText);
				setMessage('error warning',
					response.message.replace(/(?:\r\n|\r|\n)/g, '<br>'));
				break;
			default:
				setMessage('error fatal', 'Upload Failed');
				break;
		}

		var next = index + 1;
		if (next == items)
		{
			uploader_progress.style.display = 'none';
			uploader_box.style.display = 'block';
			uploader_button.disabled = false;
		}
		else
		{
			upload_files(next)
		}
	};
	xhr.send(data);
}
uploader_form.addEventListener('submit', function(event) {
	event.preventDefault();

	if (uploader_shownname == '')
	{
		return false;
	}

	upload_files()
});
uploader_file.addEventListener('change', function(event) {
	uploader_shownname = (this.files.length == 1)
		? basename(this.value)
		: this.files.length + ' files selected.';
	uploader_path.innerHTML = uploader_shownname;

	if (autostart_upload)
	{
		upload_files()
	}
});
/* Paste */
var paste_form = document.getElementById('paste_form');
var paste_name = document.getElementById('paste_name');
var paste_text = document.getElementById('paste_text');
var paste_ext = document.getElementById('paste_language');
function random_string(length)
{
	return new Array(length + 1).join(
		(Math.random().toString(36)
			+ '00000000000000000').slice(2, 18)).slice(0, length);
}
paste_form.addEventListener('submit', function(event) {
	event.preventDefault();

	if (paste_text.value == '')
	{
		return false;
	}

	var name = (paste_name.value == '') ? random_string(8) : paste_name.value;

	var data = new FormData();

	data.append('data', new Blob([ paste_text.value ],
		{ type: 'text/plain' }));

	var headers = {
		action: 'upload',
		token: token,
		filename: name + '.' + paste_ext.value
	};

	data.append('headers', new Blob([ JSON.stringify(headers) ],
		{ type: 'application/json' }));

	var xhr = new XMLHttpRequest();
	xhr.open('POST', uploader_form.action, true);
	xhr.onload = function(r) {
		switch (this.status)
		{
			case 201:
				response = JSON.parse(this.responseText);
				setMessage('notice', 'Pasted!<br>'
					+ '<a target="_blank" href="' + response.link + '">'
					+ response.link + '</a>');
				update_history();
				break;
			case 202:
				response = JSON.parse(this.responseText);
				setMessage('error warning',
					response.message.replace(/(?:\r\n|\r|\n)/g, '<br>'));
				break;
			default:
				setMessage('error fatal', 'Paste Failed');
				break;
		}
		window.scrollTo(0, 0);
	};
	xhr.send(data);
});
paste_text.addEventListener('keydown', function(event) {
	var key = ((event.keyCode) ?
		event.keyCode : ((event.charCode) ?
			event.charCode : event.which));

	if (key == 9 && !event.shiftKey && !event.ctrlKey && !event.altKey)
	{
		var top = this.scrollTop;
		if (this.setSelectionRange)
		{
			var selection_start = this.selectionStart;
			var selection_end = this.selectionEnd;
			this.value = this.value.substring(0, selection_start)
				+ "\t" + this.value.substr(selection_end);
			this.setSelectionRange(selection_start + 1, selection_start + 1);
			this.focus();
		}
		else if (this.createTextRange)
		{
			document.selection.createRange().text = "\t";
			event.returnValue = false;
		}
		this.scrollTop = top;
		if (event.preventDefault)
		{
			event.preventDefault();
		}
		return false;
	}
	return true;
});
/* URL shortener */
var urlshortener_form = document.getElementById('urlshortener_form');
urlshortener_form.addEventListener('submit', function(event) {
	event.preventDefault();

	var url = urlshortener_form.url.value;
	if (url == '')
	{
		return false;
	}

	var data = new FormData();

	var headers = {
		action: 'shorten_url',
		token: token,
		url: url
	};

	data.append('headers', new Blob([ JSON.stringify(headers) ],
		{ type: 'application/json' }));

	var xhr = new XMLHttpRequest();
	xhr.open('POST', urlshortener_form.action, true);
	xhr.onload = function(r) {
		switch (this.status)
		{
			case 201:
				response = JSON.parse(this.responseText);
				setMessage('notice', 'URL shortened!<br>'
					+ '<a target="_blank" href="' + response.link + '">'
					+ response.link + '</a>');
				urlshortener_form.url.value = '';
				update_history();
				break;
			case 202:
				response = JSON.parse(this.responseText);
				setMessage('error warning',
					response.message.replace(/(?:\r\n|\r|\n)/g, '<br>'));
				break;
			default:
				setMessage('error fatal', 'URL Shortening Failed');
				break;
		}
		window.scrollTo(0, 0);
	};
	xhr.send(data);
});
/* Settings */
var settings_form = document.getElementById('settings_form');
settings_form.addEventListener('submit', function(event) {
	event.preventDefault();

	var data = new FormData();

	var settings = form(settings_form);
	var current_password = settings.current_password;
	delete settings.current_password;

	if (settings.login.password == '')
	{
		delete settings.login.password;
	}

	var headers = {
		action: 'edit_settings',
		token: token,
		password: current_password,
		settings: settings
	};

	data.append('headers', new Blob([ JSON.stringify(headers) ],
		{ type: 'application/json' }));

	var xhr = new XMLHttpRequest();
	xhr.open('POST', settings_form.action, true);
	xhr.onload = function(r) {
		switch (this.status)
		{
			case 200:
				setMessage('notice', 'Settings successfully updated. '
					+ 'You should refresh the page.', true);
				settings_form.current_password.value = '';
				break;
			case 202:
				response = JSON.parse(this.responseText);
				setMessage('error warning',
					response.message.replace(/(?:\r\n|\r|\n)/g, '<br>'));
				break;
			default:
				setMessage('error fatal', 'Could not update settings.');
				break;
		}
		window.scrollTo(0, 0);
	};
	xhr.send(data);
});
/* History */
var link_history = document.getElementById('link_history');
function update_history()
{
	var data = new FormData();

	var headers = {
		action: 'get_last_n_links',
		token: token,
		n: link_history.getAttribute('data-length')
	};

	data.append('headers', new Blob([ JSON.stringify(headers) ],
		{ type: 'application/json' }));

	var xhr = new XMLHttpRequest();
	xhr.open('POST', link_history.getAttribute('data-api'), true);
	xhr.onload = function(r) {
		switch (this.status)
		{
			case 200:
				var response = JSON.parse(this.responseText);
				link_history.innerHTML = '';
				[].forEach.call(response.links, function(item) {
					link_history.insertAdjacentHTML('beforeend',
						'<li><div class="history_item closebutton" data-uid="'
						+ item.uid + '" data-name="' + item.name + '"></div>'
						+ '<a class="historyitem link" href="' + baseurl
						+ item.uid + ((show_extension) ? '.' + item.ext : '')
						+ '" title="' + item.mime + '" target="_blank">'
						+ item.name + '</a> (<i>' + item.mime + '</i>)</li>');
				});
				update_history_listeners();
				break;
			default:
				setMessage('error warning', 'Could not update history.');
				break;
		}
	};
	xhr.send(data);
}
function update_history_listeners()
{
	var closebuttons = document.getElementsByClassName(
		'history_item closebutton');

	if (!closebuttons.length)
	{
		link_history.innerHTML = '<li class="center"><i>Empty</i></li>';
		return;
	}

	[].forEach.call(closebuttons, function (element) {
		element.addEventListener('click', function (event) {
			if (deletion_confirmation)
			{
				var confirmed = window.confirm(
					'Are you sure you want to delete "'
					+ element.getAttribute('data-name') + '"?');
				if (!confirmed)
				{
					return false;
				}
			}

			var data = new FormData();

			var headers = {
				action: 'delete',
				token: token,
				uid: element.getAttribute('data-uid')
			};

			data.append('headers', new Blob([ JSON.stringify(headers) ],
				{ type: 'application/json' }));

			var xhr = new XMLHttpRequest();
			xhr.open('POST', link_history.getAttribute('data-api'), true);
			xhr.onload = function(r) {
				switch (this.status)
				{
					case 200:
						setMessage('notice', 'Item successfully deleted.');
						update_history();
						break;
					default:
						setMessage('error fatal',
							'Could not delete item.');
						break;
				}
			};
			xhr.send(data);
		});
	});
}
function prune_unused()
{
	var confirmed = window.confirm('Are you sure you want to prune all '
		+ 'unused links?');
	if (!confirmed)
	{
		return;
	}

	var data = new FormData();

	var headers = {
		action: 'prune_unused',
		token: token,
	};

	data.append('headers', new Blob([ JSON.stringify(headers) ],
		{ type: 'application/json' }));

	var xhr = new XMLHttpRequest();
	xhr.open('POST', api, true);
	xhr.onload = function(r) {
		switch (this.status)
		{
			case 200:
				var response = JSON.parse(this.responseText);
				setMessage('notice', 'Pruned ' + response.pruned + ' link'
					+ ((response.pruned != 1) ? 's' : '') + '.');
				update_history();
				window.scrollTo(0, 0);
				break;
			default:
				setMessage('error warning', 'Could not prune links.');
				break;
		}
	};
	xhr.send(data);
}
update_history();

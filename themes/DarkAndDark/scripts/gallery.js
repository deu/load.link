var gallery = document.getElementById('gallery');
var gallery_total = document.getElementById('gallery_total');
var gallery_displayed = document.getElementById('gallery_displayed');
var gallery_count = 0;
var gallery_offset = 0;
function update_count(n)
{
	var data = new FormData();

	var headers = {
		action: 'count',
		token: token,
	};

	data.append('headers', new Blob([ JSON.stringify(headers) ],
		{ type: 'application/json' }));

	var xhr = new XMLHttpRequest();
	xhr.open('POST', api, true);
	xhr.onload = function() {
		switch (this.status)
		{
			case 200:
				var response = JSON.parse(this.responseText);
				gallery_total.innerHTML = gallery_count = response.count;
				break;
			default:
				setMessage('error warning', 'Could not count links.');
				break;
		}
	};
	xhr.send(data);
}
function add_to_gallery(n)
{
	var data = new FormData();

	var headers = {
		action: 'get_links',
		token: token,
		limit: n,
	};

	if (gallery_offset > 0)
	{
		headers.offset = gallery_offset;
	}
	else
	{
		gallery.innerHTML = '';
	}

	data.append('headers', new Blob([ JSON.stringify(headers) ],
		{ type: 'application/json' }));

	var xhr = new XMLHttpRequest();
	xhr.open('POST', api, true);
	xhr.onload = function() {
		switch (this.status)
		{
			case 200:
				var response = JSON.parse(this.responseText);
				[].forEach.call(response.links, function(item) {
					gallery.insertAdjacentHTML('beforeend', '<div '
						+	'class="gallery item" '
						+	'data-uid="' + item.uid + '" '
						+	'data-name="' + item.name + '" '
						+	'data-mime="' + item.mime + '" '
						+	'data-ext="' + item.ext + '">');
					gallery_offset++;
					gallery_displayed.innerHTML++;
				});
				update_gallery_listeners();
				[].forEach.call(response.links, function(item) {
					gallery_register(item);
				});
				break;
			default:
				setMessage('error warning', 'Could not update the gallery.');
				break;
		}
	};
	xhr.send(data);
}
function gallery_load_more()
{
	if (gallery_offset >= gallery_count)
	{
		return;
	}

	add_to_gallery(gallery_limit);
}
function generate_item_content(uid, name, mime, ext, thumbnail)
{
	var src, icon, width, height;
	if (thumbnail)
	{
		src = 'data:' + thumbnail.mime + ';base64,' + thumbnail.data;
		width = thumbnail.width;
		height = thumbnail.height;
	}
	else
	{
		icon = mime.replace('/', '-') + '.png';
		if (faenzaicons.indexOf(icon) < 0)
		{
			icon = (mime.startsWith('video/')) ? 'video-x-generic.png'
				: 'none.png';
		}
		src = baseroute + 'static/faenzaicons/' + icon;
		width = height = 96;
	}

	return '<div class="thumbnail">'
		+	'<a href="' + baseroute
		+		uid + ((show_extension) ? '.' + ext : '') + '" '
		+	'title="' + name + ' (' + mime + ')" target="_blank">'
		+		'<img '
		+		'width="' + width + '" height="' + height + '" '
		+		'src="' + src + '" '
		+		'alt="' + mime + '">'
		+	'</a>'
		+ '</div>'
		+ '<div class="gallery closebutton"></div>';
}
function attach_closebutton(item, closebutton)
{
    closebutton.addEventListener('click', function (event) {
        if (deletion_confirmation)
        {
            var confirmed = window.confirm(
                'Are you sure you want to delete "'
                + item.getAttribute('data-name') + '"?');
            if (!confirmed)
            {
                return false;
            }
        }

        var data = new FormData();

        var headers = {
            action: 'delete',
            token: token,
            uid: item.getAttribute('data-uid')
        };

        data.append('headers', new Blob([ JSON.stringify(headers) ],
            { type: 'application/json' }));

        var xhr = new XMLHttpRequest();
        xhr.open('POST', api, true);
        xhr.onload = function() {
            switch (this.status)
            {
                case 200:
                    setMessage('notice', 'Item successfully deleted.');
					item.style.display = 'none';
					update_count();
					gallery_displayed.innerHTML--;
                    break;
                default:
                    setMessage('error fatal',
                        'Could not delete item.');
                    break;
            }
        };
        xhr.send(data);
    });
}
var gallery_registered = [];
function gallery_register(item)
{
	gallery_registered.push(item.uid);
}
function update_gallery_listeners()
{
	var gallery_items = document.getElementsByClassName('gallery item');

	if (!gallery_items.length)
	{
		gallery.innerHTML = 'Empty';
	}

	[].forEach.call(gallery_items, function (item) {
		var uid = item.getAttribute('data-uid');
		var name = item.getAttribute('data-name');
		var mime = item.getAttribute('data-mime');
		var ext = item.getAttribute('data-ext');

		if (gallery_registered.indexOf(uid) > -1)
		{
			return;
		}

		if ([ 'image/jpeg', 'image/png', 'image/gif' ].indexOf(mime) < 0)
		{
			item.insertAdjacentHTML('beforeend',
				generate_item_content(uid, name, mime, ext));
            var closebutton = item.getElementsByClassName(
                'gallery closebutton')[0];
            attach_closebutton(item, closebutton);
			return;
		}

		var data = new FormData();

		var headers = {
			action: 'get_thumbnail',
			token: token,
			uid: uid
		};

		data.append('headers', new Blob([ JSON.stringify(headers) ],
			{ type: 'application/json' }));

		var xhr = new XMLHttpRequest();
		xhr.open('POST', api, true);
		xhr.onload = function() {
			switch (this.status)
			{
				case 200:
					var response = JSON.parse(this.response);
					var thumbnail = {
                        data: response.data,
                        width: response.width,
                        height: response.height,
                        mime: response.mime
                    };
					item.insertAdjacentHTML('beforeend',
						generate_item_content(uid, name, mime, ext, thumbnail));
                    var closebutton = item.getElementsByClassName(
                        'gallery closebutton')[0];
                    attach_closebutton(item, closebutton);
					return;
				default:
					return null;
			}
		};
		xhr.send(data);
	});
}
update_count();
add_to_gallery(gallery_limit);

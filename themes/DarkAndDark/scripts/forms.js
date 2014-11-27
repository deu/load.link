function form(form)
{
	obj = {};

	[].forEach.call(form.elements, function(element) {
		if (element.name == '')
		{
			return;
		}
		switch (element.nodeName)
		{
			case 'INPUT':
				switch (element.type)
				{
					case 'checkbox':
					case 'radio':
						if (element.checked)
						{
							obj[element.name] = (element.hasClass('boolean')) ?
								true : encodeURIComponent(element.value);
						}
						break;
					default:
						obj[element.name] = encodeURIComponent(element.value);
						break;
				}
				break;
			case 'TEXTAREA':
				obj[element.name] = encodeURIComponent(element.value);
				break;
			case 'SELECT':
				switch (element.type)
				{
					case 'select-multiple':
						[].forEach.call(element.options, function(option) {
							if (option.selected)
							{
								obj[element.name] = encodeURIComponent(
									option.value);
							}
						});
						break;
					case 'select-one':
					default:
						if (element.classList.contains('boolean'))
						{
							obj[element.name] = (
								element.value.toLowerCase() == 'true') ?
									true : false;
						}
						else
						{
							obj[element.name] = encodeURIComponent(
								element.value);
						}
						break;
				}
				break;
		}

		arr = element.name.match(/^(.+?)\[(.+?)\]$/);
		if (arr && arr[1] && arr[2])
		{
			if (!obj[arr[1]])
			{
				obj[arr[1]] = {};
			}
			obj[arr[1]][arr[2]] = obj[element.name];
			delete obj[element.name];
		}
	});

	return obj;
}

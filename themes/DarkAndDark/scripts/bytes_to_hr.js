function bytes_to_hr(bytes)
{
    if (bytes == 0)
	{
		return '0 Bytes';
	}

	if (bytes == 1)
	{
		return '1 Byte';
	}

	var units = [ 'Bytes', 'KiB', 'MiB', 'GiB', 'TiB',
		'PiB', 'EiB', 'ZiB', 'YiB' ];
	var magnitude = Math.pow(2, 10);

    var stage = Math.floor(Math.log(bytes) / Math.log(magnitude));
    var result = (bytes / Math.pow(magnitude, stage));

	return result.toFixed((stage == 0) ? 0 : 2) + ' ' + units[stage];
}

function refresh()
{
	var here = window.location;
	window.location = here.protocol + '//' + here.host + here.pathname;
}

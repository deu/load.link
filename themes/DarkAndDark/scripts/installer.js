var database_type = document.getElementById('database_type');
var database_name = document.getElementById('database_name');
var database_conf = document.getElementById('database_conf');
var database_default_names = {
	'sqlite': '.db.sqlite',
	'default': 'loadlink'
};
var routing_mode = document.getElementById('routing_mode');
var routing_conf = document.getElementById('routing_conf');
database_type.addEventListener('change', function(event) {
	if (database_type.value == 'sqlite')
	{
		database_conf.style.display = 'none';
		if (database_name.value == database_default_names['default'])
		{
			database_name.value = database_default_names['sqlite'];
		}
	}
	else
	{
		database_conf.style.display = 'block';
		if (database_name.value == database_default_names['sqlite'])
		{
			database_name.value = database_default_names['default'];
		}
	}
});
routing_mode.addEventListener('change', function(event) {
	if (routing_mode.value == 'path')
	{
		routing_conf.style.display = 'block';
	}
	else
	{
		routing_conf.style.display = 'none';
	}
});

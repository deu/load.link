var message_popup = document.getElementById('message');
var message_closebutton = document.getElementById('message_closebutton');
var message_content = document.getElementById('message_content');
var message_refresh_button = document.getElementById('message_refresh_button');
var message_timeout_function = null;
function setMessage(classes, message, refresh_button)
{
	message_popup.className = 'message ' + classes;
	message_content.innerHTML = message;
	if (refresh_button)
	{
		message_refresh_button.style.display = 'block';
	}
	else
	{
		message_refresh_button.style.display = 'none';
		if (message_timeout > 0)
		{
			window.clearTimeout(message_timeout_function);
			message_timeout_function =window.setTimeout(function() {
				message_popup.style.display = 'none'
			}, message_timeout);
		}
	}
	message_popup.style.display = 'block';
}
message_closebutton.addEventListener('click', function(event) {
	message_popup.style.display = 'none';
});

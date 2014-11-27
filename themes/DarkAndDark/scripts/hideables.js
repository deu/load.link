[].forEach.call(document.getElementsByClassName('hideable'),
	function (element) {
	[].forEach.call(element.getElementsByClassName('hider'),
		function (hider) {
		hider.addEventListener('click', function (event) {
			if (element.classList.contains('hidden'))
			{
				element.classList.remove("hidden");
			}
			else
			{
				element.classList.add("hidden");
			}
		});
	});
});

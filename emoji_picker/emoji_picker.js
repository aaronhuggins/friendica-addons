window.addEventListener('load', function () {
	const isComposer = location.pathname.endsWith('/compose');
	const button = document.getElementById('profile-emoji_picker-button');
	const controller = window.picmoPopup.createPopup({}, {
		className: 'emoji_picker',
		triggerElement: button,
		referenceElement: button,
		hideOnEmojiSelect: false,
		position: isComposer ? 'auto' : 'bottom',
	});

	controller.picker.addEventListener('emoji:select', function (selection) {
		const textarea = isComposer
			? document.getElementById('comment-edit-text-0')
			: document.getElementById('profile-jot-text');
		if (textarea) textarea.value += selection.emoji;
	});
	button?.addEventListener('click', function () {
		controller.toggle();
	});
})

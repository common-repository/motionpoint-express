( function( $ ) {

var scope;

$(function onDomReady() {
  const observer = new MutationObserver(onMenuChanged);

	var customizerThemeControlsEl = document.getElementById('customize-theme-controls');
	if ( customizerThemeControlsEl ) {
		scope = customizerThemeControlsEl;
	  observer.observe(customizerThemeControlsEl, {
	      subtree: true,
	      childList: true,
	  });
	}

	var menuEditorForm = document.getElementById('update-nav-menu');
	if ( menuEditorForm ) {
		scope = menuEditorForm;
	  observer.observe(menuEditorForm, {
	      subtree: true,
	      childList: true,
	  });
	}
});

function onMenuChanged() {
	if (!scope) return;

	$('.field-url .edit-menu-item-url').each( function( index, item ) {
		if ( '#motionpointexpress-language-selector' === $(item).val() ) {
			$(item).closest('.field-url').addClass('hidden');
		}
	});
}

})( jQuery );
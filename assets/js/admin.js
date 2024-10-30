( function( $ ) {

$(function onDomReady() {
	$('#MotionPointExpressSettingsForm').on( 'change', '#MotionPointExpressLocation, #MotionPointExpressProjectCode', function( e ) {
		updateLocationUrl();
		updateCustomLocationVisibility();
	} );
	$('#MotionPointExpressSettingsForm').on( 'input', '#MotionPointExpressCustomLocation', function( e ) {
		updateLocationUrl();
	} );
	updateLocationUrl();
	updateCustomLocationVisibility();

	var $debugInfo = $('#MotionPointExpressDebugInfo');
	var $debugInfoBtn = $('#MotionPointExpressDebugToggleBtn');
	$debugInfoBtn.on( 'click', function( e ) {
		e.preventDefault();
		$debugInfo.slideToggle();
		$debugInfoBtn.toggleClass('active');
	} );
});

function updateCustomLocationVisibility() {
	var locationHost = $('#MotionPointExpressLocation').val();
	locationHost = $.trim( locationHost );
	if (locationHost === 'custom') {
		$('#MotionPointExpressCustomLocationWrapper').addClass('active');
	} else {
		$('#MotionPointExpressCustomLocationWrapper').removeClass('active');
	}
}

function updateLocationUrl() {
	var projectCode = $('#MotionPointExpressProjectCode').val();
	var locationHost = $('#MotionPointExpressLocation').val();
	var customLocationHost = $('#MotionPointExpressCustomLocation').val();

	projectCode = $.trim( projectCode );
	locationHost = $.trim( locationHost );
	customLocationHost = $.trim( customLocationHost );
	locationHost = locationHost === 'custom' ? customLocationHost : locationHost;

	if ( ! locationHost ||  ! projectCode ) {
		$('a[data-motionpointpress-login-link]').attr( 'href', '#' );
		$('a[data-motionpointpress-login-link]').attr( 'onclick', 'return false' );
		return;
	}

	var url = 'https://' + locationHost;

	url += '/_el/dashboard/project/' + projectCode + '/settings';
	$('a[data-motionpointpress-login-link]').attr( 'href', url );
	$('a[data-motionpointpress-login-link]').removeAttr( 'onclick' );
}

})( jQuery );
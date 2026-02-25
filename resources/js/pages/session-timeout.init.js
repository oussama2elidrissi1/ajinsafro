/*
Template Name: Qovex - Admin & Dashboard Template
Author: AJINSAFRO
Website: https://AJINSAFRO.com/
Contact: AJINSAFRO@gmail.com
File: Session time 
*/

$.sessionTimeout({
	keepAliveUrl: 'pages-starter',
	logoutButton:'Logout',
	logoutUrl: 'pages-login',
	redirUrl: 'pages-lock-screen',
	warnAfter: 3000,
	redirAfter: 30000,
	countdownMessage: 'Redirecting in {timer} seconds.'
});

$('#session-timeout-dialog  [data-dismiss=modal]').attr("data-bs-dismiss", "modal");
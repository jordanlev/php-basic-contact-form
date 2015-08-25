<?php

$akismet_api_key = ''; //sign up for akismet at https://akismet.com/plans/ (click the blue "Get Started" button under the "Basic" plan)

$mandrill_api_key = ''; //sign up for mandrill at https://mandrill.com/signup/

$notification_email_to = ''; //your email address

$notification_email_subject = 'Website Contact Form Submission';

$redirect_to_confirmation_page = 'thanks.html'; //can be a full url, or an absolute or relative path


/*** YOU CAN IGNORE EVERYTHING BELOW HERE ************************************/

if (empty($akismet_api_key)) {
	die('ERROR: Missing $akismet_api_key');
}

if (empty($mandrill_api_key)) {
	die('ERROR: Missing $mandrill_api_key');
}

if (empty($akismet_api_key)) {
	die('ERROR: Missing $notification_email_to');
}

if (empty($notification_email_to)) {
	die('ERROR: Missing $notification_email_subject');
}

if (empty($redirect_to_confirmation_page)) {
	die('ERROR: Missing $redirect_to_confirmation_page');
}

$errors = array();

$submitter_name = empty($_POST['name']) ? '' : trim($_POST['name']);
$submitter_email = empty($_POST['email']) ? '' : trim($_POST['email']);
$submitter_message = empty($_POST['message']) ? '' : trim($_POST['message']);

if (empty($submitter_name)) {
	$errors[] = 'Name is required';
}

if (empty($submitter_email)) {
	$errors[] = 'Email address is required';
} else if (!filter_var($submitter_email, FILTER_VALIDATE_EMAIL)) {
	$errors[] = 'Email address is not valid';
}

if (empty($submitter_message)) {
	$errors[] = 'A message is required';
}

if (empty($errors)) {
	if (akismetSpamCheck($akismet_api_key, $submitter_name, $submitter_email, $submitter_message)) {
		$errors[] = 'Could not process your message'; //give an intentionally confusing message becasue we don't want to make things obvious for spammers!
	}
}

if (empty($errors)) {
	$body = "The following information was just submitted to the contact form on your website:\n\n";
	foreach ($_POST as $field_name => $field_value) {
		$body .= "{$field_name}: {$field_value}\n";
	}

	$error = sendMandrillEmail($mandrill_api_key, $notification_email_to, $submitter_email, $submitter_name, $notification_email_subject, $body);
	if ($error) {
		$errors[] = 'Error sending notification email: ' . $error;
	}
}

if (empty($errors)) {
	if (substr($redirect_to_confirmation_page, 0, 4 ) === 'http') {
		$redirect_to_url = $redirect_to_confirmation_page;
	} else if (substr($redirect_to_confirmation_page, 0, 1) === '/') {
		$redirect_to_url = 'http://' . $_SERVER['HTTP_HOST'] . $redirect_to_confirmation_page;
	} else {
		$redirect_to_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $redirect_to_confirmation_page;
	}
	header("Location: $redirect_to_url");
	exit;
}

//returns true if spam (or error), false if not spam
function akismetSpamCheck($api_key, $submitter_name, $submitter_email, $submitter_message) {
	$post = http_build_query(array(
		'blog' => 'http://' . $_SERVER['HTTP_HOST'],
		'user_ip' => $_SERVER['REMOTE_ADDR'],
		'user_agent' => $_SERVER['HTTP_USER_AGENT'],
		'referrer' => $_SERVER['HTTP_REFERER'],
		'permalink' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'comment_type' => 'contact-form',
		'comment_author' => $submitter_name,
		'comment_author_email' => $submitter_email,
		'comment_content' => $submitter_message,
	));
	
	$url = "https://$api_key.rest.akismet.com/1.1/comment-check";

	$result = curlHttpsPost($url, $post);

	return ($result !== 'false'); //akismet api returns string "false" if not spam
}

//returns null upon success, or a string error message upon failure.
function sendMandrillEmail($api_key, $to_email, $from_email, $from_name, $subject, $body) {
	$post = json_encode(array(
		'key' => $api_key,
		'message' => array(
			'text' => $body,
			'subject' => $subject,
			'from_email' => $from_email,
			'from_name' => $from_name,
			'to' => array(
				array(
					'email' => $to_email,
				),
			),
		),
		'async' => false,
	));

	$url = 'https://mandrillapp.com/api/1.0/messages/send.json';

	$result = curlHttpsPost($url, $post);

	$result = json_decode($result);
	
	if (!empty($result->status) && ($result->status == 'error')) {
		return $result->message;
	} else if (empty($result[0]->status) || ($result[0]->status !== 'sent')) {
		return 'Could not deliver notification email to site administrator';
	}
}

function curlHttpsPost($url, $post) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	return curl_exec($ch);
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Contact Form Error</title>
</head>
<body>
	<p>
		The form could not be submitted due to the following error(s):
		<ul>
			<?php foreach ($errors as $error) { ?>
				<li><?php echo $error; ?></li>
			<?php } ?>
		</ul>
		Please <a href="javascript:window.history.go(-1);">go back</a> and try again.
	</p>
</body>
</html>
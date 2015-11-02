# PHP Basic Contact Form
A basic contact form handler that uses Akismet to block spam and Mandrill to send you emails when someone fills out the form. This script is ideal for people who are new to HTML.

To get this file, click the "Download Zip" button (probably over on the right sidebar, about halfway down the page), unzip the downloaded file, then move the `process_form.php` file over to wherever you're working on your html. (That is the only file you will need from the download.)

## Instructions for use
1. Sign up for [Akismet](https://akismet.com/plans/) (basic plan is free for personal/non-commercial use) and [Mandrill](https://mandrill.com/signup/) (free for 2,000 email sends). Once your accounts are created, get an "API Key" for each one.
2. Create an html page that displays a confirmation message (what people will see after they've submitted the form -- e.g. "thanks, we'll be in touch shortly!"). Upload that to your server.
3. Open the 'process_form.php' file in a text editor and fill out the settings at the top (where to send the notification emails to, your akismet and mandrill API keys from step 1, and the location of the confirmation page you created in step 2). Save and close that file, then upload it to your server.
4. Add a form wherever you want in your html. The form must have the following:
    * "action" must be the location of the `process_form.php` file
    * "method" must be "post"
    * must contain the following 3 fields:
        1. "name" (e.g. `<input type="text" name="name" required>`)
        2. "email" (e.g. `<input type="email" name="email" required>`)
        3. "message" (e.g. `<textarea name="message" required></textarea>`)
    * since those 3 fields are required, you should put the "required" attribute on them (otherwise form errors are displayed on an unstyled page that might confuse your visitors)
    * If you want more fields on the form in addition to the 3 listed above, go ahead and add them to the form html. You can have as many other fields as you want, with whatever names you want, required or not required, and of any input type except for "file" (because this script does not handle file uploads). All form fields will be included in the notification email that gets sent to you when someone fills out the form.

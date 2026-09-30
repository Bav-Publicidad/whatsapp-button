=== Chat Lead Button – Lead capture for WhatsApp ===
Contributors: desarrolladorbavit
Tags: whatsapp, lead capture, click to chat, contact form, google tag manager
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Floating chat button with a lead form: capture name, email and message before opening WhatsApp, get leads by email and track them in GTM.

== Description ==

Chat Lead Button adds a floating button to your site. When visitors click it, a short form asks for their details before opening a WhatsApp conversation with your business. Every lead is saved and emailed to you, even if the visitor never sends the message.

**Features**

* Floating button with a pop-up form (name, message, optional email and phone).
* Customizable form title, description and WhatsApp message template with `{name}`, `{email}`, `{phone}` and `{message}` placeholders.
* Message field as free text or a dropdown with your own options.
* Email notification with Reply-To set to the lead, traffic source (UTM parameters, gclid, fbclid, msclkid, landing page, referrer), date and device.
* Every lead is stored in the WordPress admin (Leads menu), even if the email fails, with CSV export.
* Google Tag Manager ready: pushes a `whatsapp_lead` event to the `dataLayer` (no personal data).
* Optional consent checkbox with a link to your privacy policy.
* Spam protection: nonce, honeypot field and rate limiting per IP.

This plugin is not affiliated with, endorsed by or sponsored by WhatsApp or Meta Platforms, Inc.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install it from the Plugins screen.
2. Activate the plugin.
3. Go to **Settings → WhatsApp Button** and enter your WhatsApp number with country code (digits only, e.g. `573001234567`).
4. Adjust the form fields, texts and the email address that receives leads.

== Frequently Asked Questions ==

= How do I track leads in Google Tag Manager? =

Create a **Custom Event** trigger with the event name `whatsapp_lead` and use it in your GA4, Google Ads or Meta tags. The event includes `lead_source`, `page_location` and, when the message is a dropdown, `lead_topic`. Names and emails are never sent to the `dataLayer`.

= I don't receive the lead emails =

Delivery depends on your server's email configuration. We recommend an SMTP plugin. Leads are always saved in the admin **Leads** menu, so none are lost.

= Does it work with page caching? =

Yes, but keep the page cache lifespan under 10 hours so the form's security token (nonce) does not expire.

= Are leads deleted when I uninstall the plugin? =

No. Settings are removed, but saved leads are kept on purpose. Delete them from the Leads menu before uninstalling if you want them gone.

== External services ==

When a visitor submits the form, the plugin opens a `https://wa.me/` link (a WhatsApp service provided by Meta Platforms, Inc.) in a new tab. The link contains your business phone number and the message composed from the visitor's input (name, message and, if enabled, email and phone). No data is sent to WhatsApp until the visitor submits the form.

* WhatsApp Terms of Service: https://www.whatsapp.com/legal/terms-of-service
* WhatsApp Privacy Policy: https://www.whatsapp.com/legal/privacy-policy

== Changelog ==

= 1.3.0 =
* New: leads are stored in the admin with CSV export.
* New: Google Tag Manager `dataLayer` event.
* New: richer lead email (Reply-To, UTM parameters, referrer, device).
* New: optional phone field, optional email field, customizable form title and description.
* New: consent checkbox.
* Security: AJAX endpoint with nonce, honeypot and rate limiting; sanitized settings.
* Improved: accessibility and mobile layout.

== Upgrade Notice ==

= 1.3.0 =
Leads are now saved in the admin and the form includes a consent checkbox enabled by default.

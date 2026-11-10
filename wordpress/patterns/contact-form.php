<?php
/**
 * Title: Contact — Split Form
 * Slug: cc-modern/contact-form
 * Categories: cc-sections
 * Description: A two-column closing block: heading and reassurance on the left, form on the right. Replace the form markup with your Gravity Forms or HubSpot shortcode — the wrapper keeps the styling.
 * Keywords: contact, form, consultation, lead, enquiry
 * Viewport Width: 1400
 *
 * @package cc-modern
 */

?>
<!-- wp:group {"tagName":"section","anchor":"consultation","className":"cc-section cc-consultation","templateLock":"contentOnly","backgroundColor":"cc-light","layout":{"type":"default"}} -->
<section id="consultation" class="wp-block-group cc-section cc-consultation has-cc-light-background-color has-background">
	<!-- wp:group {"className":"cc-container","layout":{"type":"default"}} -->
	<div class="wp-block-group cc-container">
		<!-- wp:group {"className":"cc-grid cc-grid--prose cc-reveal","layout":{"type":"default"}} -->
		<div class="wp-block-group cc-grid cc-grid--prose cc-reveal">

			<!-- wp:group {"layout":{"type":"default"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"className":"cc-eyebrow"} -->
				<p class="cc-eyebrow"><?php esc_html_e( 'Get Started', 'cc-modern' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":2,"fontSize":"h2"} -->
				<h2 class="wp-block-heading has-h-2-font-size"><?php esc_html_e( 'Talk With an Expert', 'cc-modern' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"cc-body","style":{"spacing":{"margin":{"top":"32px"}}}} -->
				<p class="cc-body" style="margin-top:32px"><?php esc_html_e( 'Complete Controller · Free Consultation', 'cc-modern' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"cc-form","layout":{"type":"default"}} -->
			<div class="wp-block-group cc-form">
				<!-- wp:paragraph {"className":"cc-body"} -->
				<p class="cc-body"><?php esc_html_e( 'Request your free financial consultation. Our team will respond within 24 hours.', 'cc-modern' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:html -->
				<form class="cc-consultation-form" data-cc-consultation novalidate>
					<div class="cc-form-response" role="status" aria-live="polite" hidden></div>
					<div class="cc-form-row">
						<label><span>Full name *</span><input type="text" name="name" placeholder="Jane Doe" autocomplete="name" required></label>
						<label><span>Email *</span><input type="email" name="email" placeholder="you@company.com" autocomplete="email" required></label>
					</div>
					<div class="cc-form-row">
						<label><span>Phone *</span><input type="tel" name="phone" placeholder="(555) 123-4567" autocomplete="tel" required></label>
						<label><span>Company</span><input type="text" name="company" placeholder="Your Business Name" autocomplete="organization"></label>
					</div>
					<label><span>Service interest</span><select name="serviceType"><option>Bookkeeping Services</option><option>Document Storage</option><option>Worker's Comp Insurance</option><option>Payroll Services</option><option>Turnkey CAS department</option></select></label>
					<label><span>Message (optional)</span><textarea name="message" rows="3" placeholder="Tell us about your business..."></textarea></label>
					<button type="submit">Request Consultation</button>
				</form>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

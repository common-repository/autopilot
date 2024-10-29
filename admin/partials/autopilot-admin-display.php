<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Autopilot
 * @subpackage Autopilot/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <div class="dashboard-widgets-wrap">
        <h2>Ortto Settings</h2>

		<?php if ( isset( $this->options['instance'] ) ) { ?>
            <a href="https://ortto.app/<?php echo $this->options['instance']; ?>" target="_blank" rel="noopener noreferrer" class="button--launch-app button-primary"
               type="button">Launch app</a>
		<?php } ?>

        <p><?php settings_errors(); ?></p>

        <form method="post" action="options.php">
			<?php settings_fields( 'ap3_settings_group' ); ?>


            <div class="section section--white">
                <div class="section__col">
                    <h2>Setup</h2>

                    <p>
                        To complete the setup of this plugin you will need an existing <a target="_blank" rel="noopener noreferrer" href="https://ortto.app/signup">Ortto account</a>.
                        If you do not have an account you can <a target="_blank" rel="noopener noreferrer" href="https://ortto.app/signup">sign up free</a>. Even if you have already connected
                        the WooCommerce data source to Ortto you must complete the steps below to finish setup.
                        <br>You can learn more about correct setup by reading our <a target="_blank" rel="noopener noreferrer"
                                                                                     href="https://help.ortto.com/user/latest/data-sources/configuring-a-new-data-source/e-commerce-integrations/woocommerce.html">documentation</a>.
                    </p>
                </div>
            </div>

            <div class="section section--white">
                <div class="section__col">
                    <h2>Step 1: Connect your account</h2>

                    <input type="hidden" name="ap3_options[code]" id="code" value="<?php echo( $this->options['code'] ?? '' ) ?>"/>
                    <input type="hidden" name="ap3_options[tracking_key]" id="tracking_key" value="<?php echo( $this->options['tracking_key'] ?? '' ) ?>"/>
                    <input type="hidden" name="ap3_options[company_name]" id="company_name" value="<?php echo( $this->options['company_name'] ?? '' ) ?>"/>
                    <input type="hidden" name="ap3_options[instance]" id="instance" value="<?php echo( $this->options['instance'] ?? '' ) ?>"/>

					<?php if ( isset( $this->options['company_name'] ) ) { ?>
                        <div class="connect">
                            <div class="connect__image"></div>
                            <div class="connect__content">
                                <p>Connected to <strong>
                                        <a href="https://ortto.app/<?php echo $this->options['instance']; ?>" target="_blank"
                                           rel="noopener noreferrer"><?php echo $this->options['company_name']; ?></a>
                                    </strong>
                                </p>
                                <div>
                                    <a href="<?php echo $this->connect_url; ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary"
                                       type="button">Reconnect</a>
                                </div>
                            </div>
                        </div>
					<?php } else { ?>
                        <a href="<?php echo $this->connect_url; ?>" target="_blank" rel="noopener noreferrer" class="button button-primary"
                           type="button">Connect account</a>
                        <p>
                            To complete setup you need to connect your Ortto account with WordPress.
                        </p>
					<?php } ?>
                </div>
            </div>

            <div class="section section--consent">
                <div class="section__col">
                    <h2>Step 2: Connect to enable website tracking script</h2>
                    <p>You must consent to enable the tracking script or the following features will not work:</p>

					<?php if ( $this->is_wc ) { ?>
                        <ul>
                            <li>Website session tracking</li>
                            <li>Back in stock notifications</li>
                            <li>Abandoned cart recovery</li>
                            <li>Displaying capture widgets</li>
                        </ul>
					<?php } else { ?>
                        <ul>
                            <li>Website session tracking</li>
                            <li>Displaying capture widgets</li>
                            <li>Collecting subscribers with forms</li>
                        </ul>
					<?php } ?>

                    <div class="form">
                        <h3>Consent</h3>

                        <div class="form__input">
                            <input type="checkbox" name="ap3_options[enable_tracking]"
                                   id="enable_tracking" <?php echo ( $this->options['enable_tracking'] ?? false ) ? 'checked="checked"' : '' ?>/>
                            <label for="enable_tracking">Enable tracking script</label>
                        </div>
						<?php if ( $this->is_wc ) { ?>
                            <div class="form__input">
                                <input type="checkbox" name="ap3_options[sms_checkout]"
                                       id="sms_checkout" <?php echo ( $this->options['sms_checkout'] ?? false ) ? 'checked="checked"' : '' ?>/>
                                <label for="sms_checkout">Enable SMS checkout</label>
                            </div>
						<?php } ?>

                    </div>
                </div>
                <div class="section__col">
                </div>
            </div>

            <div class="section section--ghost">
                <div class="section__col">
                    <div class="form__input">
                        <input type="checkbox" name="ap3_options[disable_alert]"
                               id="disable_alert" <?php echo ( $this->options['disable_alert'] ?? false ) ? 'checked="checked"' : '' ?>/>
                        <label for="disable_alert">Disable configuration alert</label>
                    </div>
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Complete setup"/>
                </div>
            </div>

        </form>
    </div>
</div>

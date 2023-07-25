<?php

include_once('VueContactForm_LifeCycle.php');

class VueContactForm_Plugin extends VueContactForm_LifeCycle 
{
    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'PublicKey' => array(__('Public Key', 'vue-contact-form-plugin')),
            'PrivateKey' => array(__('Private Key', 'vue-contact-form-plugin')),
            'EMail' => array(__('EMail', 'vue-contact-form-plugin')),
            'SubjectSuffix' => array(__('Subject Suffix', 'vue-contact-form-plugin')),
            'Debug' => array(__('Show Vue JSON and other debug info.', 'vue-contact-form-plugin'), 'false', 'true'),
            'CanDoSomething' => array(__('Which user role can do something', 'vue-contact-form-plugin'),
                                        'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'Vue Contact Form';
    }

    protected function getMainPluginFileName() {
        return 'vue-contact-form.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));

        wp_enqueue_style('vue-contact-form-style', plugins_url('/css/style.css', __FILE__));
        // wp_enqueue_script('vuejs', 'https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.16/vue.min.js');
        // wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js');
        // wp_enqueue_script('vue-contact-form-script', plugins_url('/js/index.js', __FILE__));
    
        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39
        add_shortcode('vue-contact-form', array($this, 'doContactForm'));

        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41
        add_action('wp_ajax_vue_contact_form_send_mail', array(&$this, 'ajaxSendMail'));        // logged-in users
        add_action('wp_ajax_nopriv_vue_contact_form_send_mail', array(&$this, 'ajaxSendMail')); // non-logged-in users

        // For adding meta tags.
        // add_action('wp_head', array(&$this, 'addMeta'));
    }

    public function doContactForm() {
        ob_start();
        $user = wp_get_current_user();
        $logged_in = $user->exists();
        ?>
    <div id="vue_contact_form_app">
        <div class="basic-message" v-bind:class="{ 'error-message' : status == 'error', 'success-message' : status == 'sent' || status == 'sending' }" v-on:click="closeMessage">
                <p v-show="status != 'clean'">{{ actionMessage }}</p>
        </div>
        <form class="vue-form" @submit.prevent="submit">
            <fieldset>
                <div>
                    <label class="label" for="name">Your Name:</label>
                    <input type="text" name="name" id="name" placeholder="Your Name" required="" maxlength="80" v-model="name" value="<?php echo $logged_in ? $user->display_name : ""?>">
                </div>
                <div>
                    <label class="label" for="email">Your Email:</label>
                    <input type="email" name="email" id="email" placeholder="name@domain.com" required="" maxlength="40" :class="{ email , error: !email.valid }" v-model="email.value" value="<?php echo $logged_in ? $user->user_email : ""?>">
                </div>
                <div>
                    <label class="label" for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject" placeholder="An iteresting idea." required="" maxlength="120" v-model="subject">
                </div>
                <div>
                    <label class="label" for="textarea">Message:</label>
                    <textarea class="message" name="textarea" id="textarea" placeholder="Dear Mr. Dmitry, ..." required="" v-model="message.text" :maxlength="message.maxlength"></textarea>
                    <span class="counter">{{ message.text.length }} / {{ message.maxlength }}</span>
                </div>
                <div class="g-recaptcha" data-sitekey="<?php echo $this->getOption('PublicKey');?>"></div>
                <div>
                    <input type="submit" value="Send">
                </div>
            </fieldset>
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.16/vue.min.js"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
    <script src="<?php echo plugins_url('/js/vue-contact-form.js', __FILE__);?>"></script>
    <script type="text/javascript">
        var compatibleBrowser = typeof createVueContactForm === 'function';
        if (compatibleBrowser) {
            createVueContactForm("<?php echo $this->getAjaxUrl('vue_contact_form_send_mail'); ?>");
            <?php
            if (filter_var($this->getOption('Debug'), FILTER_VALIDATE_BOOLEAN))
            {
                ?>
                Vue.config.devtools = true;
                <?php
            }
            ?>
        } else {
            var elementId = 'vue_contact_form_app';
            var element = document.getElementById(elementId);
            var parent = element.parentNode;
            parent.removeChild(element);

            var newNode = document.createElement('div');
            newNode.className = 'basic-message unsupported-message error-message';
            newNode.innerHTML = '<p>You are using an old browser with which the contact form does not work. Please use the modern one.</p>';
            parent.appendChild(newNode);
        }
    </script>
        <?php
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    public function ajaxSendMail() {
        // Don't let IE cache this request
        header("Pragma: no-cache");
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-type: application/json");
     
        $in = json_decode(file_get_contents('php://input'));

        if ($in) {
            $captcha_code = $in->captcha_code;
            $ip = $_SERVER['REMOTE_ADDR'];
            $secret_key = $this->getOption('PrivateKey');
        
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_code}&remoteip={$ip}");
        
            if (!$response) {
                $out->status = "error";
                $out->message = "Not a valid response from Google Recaptcha";
            }
            else {
                $result = json_decode($response);
                $out->result = $result;
                if ($result->success) {
                    $to = $this->getOption('EMail');
                    $suffix = $this->getOption('SubjectSuffix');
                    if (empty($suffix)) { $suffix = get_bloginfo('name'); }
                    $subject = "{$in->subject} [{$suffix}]";
                    $message = $in->message;
                    //The sender address should be d-ef@yandex.ru, otherwize Yandex does not send the mail from "mail.ru" and
                    //reports the error 'Failed to authorize the sender', so in "From:" we use $to address but $in->name.
                    $headers = "From: {$in->name} <{$to}>\r\n" .
                        "Reply-To: {$in->name} <{$in->email}>\r\n" .
                        'X-Mailer: PHP/' . phpversion();
        
                    if (wp_mail($to, $subject, $message, $headers)) {
                        $out->status = "sent";
                    }
                    else {
                        $out->status = "error";
                        $out->message = "Error sending email.";
                    }
                }
                else {
                    $out->status = "error";
                    $out->message = "Wrong captcha try again please.";
                }
            }
        }
        else {
            $json_error = json_last_error();
            $out->status = "error";
            $out->message = "Cannot decode PHP input. JSON error number: {$json_error}.";
        }
        
        echo json_encode($out);
        
        die();
    }

    //Adding user-scalable=no makes 'fixed' work on mobile, but prevents the scaling.
    //<meta name="viewport" content="height=device-height, width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, target-densitydpi=device-dpi">
    public function addMeta() {
        //A typical mobile-optimized site contains something like the following.
        //But on mobile Chrome it does not help and make the sidebar overlap the contact form.
        //Tried to add 'height=device-height' but it does not take an effect.
        ?>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php
    }
}

function createVueContactForm(sendUrl) {
    new Vue({
        // root node
        el: "#vue_contact_form_app",
        // the instance state
        data: function () {
            return {
                name: "",
                email: {
                    value: "",
                    valid: true
                },
                subject: "",
                message: {
                    text: "",
                    maxlength: 64 * 1024
                },
                status: "clean", //error, sending, sent
                actionMessage: "",
            }
        },
        
        computed: {
            hasMessage: function () {
                return this.status != "clean";
            }
        },
        
        methods: {
            validateEmail: function (email) {
                //var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                // Regular expression from W3C HTML5.2 input specification:
                // https://www.w3.org/TR/html/sec-forms.html#email-state-typeemail
                var re = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
                return re.test(email);
            },
            // submit form handler
            submit: function () {
                this.email.valid = this.validateEmail(this.email.value);

                if (!this.email.valid) {
                    this.actionMessage = "Please enter a valid email address.";
                    this.status = "error";
                }

                if (grecaptcha === undefined) {
                    this.actionMessage = "An internal error with Recaptcha: grecaptcha is not defined.";
                    this.status = "error";
                    return;
                }

                var captcha_response = grecaptcha.getResponse();

                if (!captcha_response) {
                    this.actionMessage = "Please confirm you a not a robot.";
                    this.status = "error";
                    return;
                }

                this.actionMessage = "Sending your message...";
                this.status = "sending";
                grecaptcha.reset();

                fetch(sendUrl, {
                    method: "post",
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.name,
                        subject: this.subject,
                        email: this.email.value,
                        message: this.message.text,
                        captcha_code: captcha_response
                    })
                }).then(response => {
                    if (response.status == 200) {
                        return response.json();
                    }
                    else {
                        throw { message: `There was an error communicating with the server: ${response.status} ${response.statusText}` };
                    }
                }).then(data => {
                    if (data.status == "sent") {
                        this.message.text = "";
                        this.actionMessage = "Your message has been sent!";
                        this.status = "sent";
                    }
                    else {
                        throw { message: data.message ? data.message : "Unknown error at the server side." };
                    }
                }).catch(error => {
                    console.log(error);
                    if (error.hasOwnProperty('message')) {
                        this.actionMessage = error.message;
                    } else {
                        this.actionMessage = "Something went wrong. See the console output for details.";
                    }
                    this.status = "error";
                });
            },
            resetIfSent: function () {
                if (this.status == "sent")
                    this.status = "clean";
            },
            closeMessage: function () {
                this.status = "clean";
            }
        },
        
        watch: {
            "name": function (value) {
                //this.resetIfSent();
            },
            "email.value": function (value) {
                //this.resetIfSent();
            },
            "message.text": function (value) {
                //this.resetIfSent();
            },
        }
    });
}


{{#component_html}}
    {{{ component_html }}}
{{/component_html}}

{{^component_html}}
<!-- HTML dashboard -->
<section class="dashboard-welcome section">

    <div class="wrapperfaq">

        <img src="./assets/icons/faq.svg" alt="">

        <div class="container">
            <div class="question">
                Comment fonctionne mon agenda ?
            </div>
            <div class="answercont">
                <div class="answer">
                    Click the link in the verification email from verify@codepen.io (be sure to check your spam folder
                    or other email tabs if it's not in your inbox).

                    Or, send an email with the subject "Verify" to verify@codepen.io from the email address you use for
                    your CodePen account.<br><br>
                    <a
                        href="https://blog.codepen.io/documentation/features/email-verification/#how-do-i-verify-my-email-2">How
                        to Verify Email Docs</a>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="question">
                Comment gérer mes utilisateurs ?
            </div>
            <div class="answercont">
                <div class="answer">
                    It's likely an infinite loop in JavaScript that we could not catch. To fix, add ?turn_off_js=true to
                    the end of the URL (on any page, like the Pen or Project editor, your Profile page, or the
                    Dashboard) to temporarily turn off JavaScript. When you're ready to run the JavaScript again, remove
                    ?turn_off_js=true and refresh the page.<br><br>

                    <a href="https://blog.codepen.io/documentation/features/turn-off-javascript-in-previews/">How to
                        Disable JavaScript Docs</a>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="question">
                Comment fonctionne mes articles ?
            </div>
            <div class="answercont">
                <div class="answer">
                    You can leave a comment on any public Pen. Click the "Comments" link in the Pen editor view, or
                    visit the Details page.<br><br>

                    <a href="https://blog.codepen.io/documentation/faq/how-do-i-contact-the-creator-of-a-pen/">How to
                        Contact Creator of a Pen Docs</a>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="question">
                Comment fonctionne ma galerie ?
            </div>
            <div class="answercont">
                <div class="answer">
                    We have our current list of library versions <a href="https://codepen.io/versions">here</a>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="question">
                Comment gérer mon profil ?
            </div>
            <div class="answercont">
                <div class="answer">
                    A fork is a complete copy of a Pen or Project that you can save to your own account and modify. Your
                    forked copy comes with everything the original author wrote, including all of the code and any
                    dependencies.<br><br>
                    <a href="https://blog.codepen.io/documentation/features/forks/">Learn More About Forks</a>
                </div>
            </div>
        </div>

    </div>

</section>

<script></script>
{{/component_html}}
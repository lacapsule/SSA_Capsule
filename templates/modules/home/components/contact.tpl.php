<section id="contact" class="contact section">
  <div class="contain">

    <div class="title">
      <div class="section-title padd-15">
        <h2>{{str.contact_title}}</h2>
        <p>{{str.contact_intro}}</p>
      </div>
    </div>

    <div class="coord">
      <div class="section-title padd-15">
        <h3>{{str.contact_coords_title}}</h3>
        <p>
          <strong>{{str.contact_address_label}}</strong>
          Le Buzuk, 39 bellevue de la madeleine, 29600 Morlaix
        </p>
        <p>
          <strong>{{str.contact_phone_label}}</strong>
          <a href="tel:+33615068208">06 15 06 82 08</a>
        </p>
        <p>
          <strong>{{str.contact_email_label}}</strong>
          <a href="mailto:ssapaysdemorlaix@mailo.com">ssapaysdemorlaix@mailo.com</a>
        </p>
      </div>
    </div>

    <img src="/assets/img/contact.svg" alt="img_contact">

    <div class="contact-form">
      <h3>{{str.contact_form_title}}</h3>

      {{#flash_success}}
      <p class="notice notice--success">{{.}}</p>
      {{/flash_success}}
      {{#flash_error}}
      <p class="notice notice--error">{{.}}</p>
      {{/flash_error}}
      {{#contact_errors._global}}
      <p class="notice notice--error">{{.}}</p>
      {{/contact_errors._global}}

      <form class="mb-5" method="post" id="contactForm" name="contactForm" action="{{contact_action}}">
        {{{csrf_input}}}
        <div class="honeypot" aria-hidden="true" style="position:absolute;left:-9999px;">
          <label for="website">Site web</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>
        <div class="honeypot-checkbox" aria-hidden="true" style="display:none;">
          <label for="confirm_robot">Ne pas cocher</label>
          <input type="checkbox" id="confirm_robot" name="confirm_robot" tabindex="-1">
        </div>
        <div class="row">
          <div class="col-md-12 form-group">
            <input type="text" class="form-control" name="name" id="name" value="{{contact_old.name}}" placeholder="{{str.contact_form_name}}" required>
            {{#contact_errors.name}}<p class="field-error">{{.}}</p>{{/contact_errors.name}}
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 form-group">
            <input type="email" class="form-control" name="email" id="email" value="{{contact_old.email}}" placeholder="{{str.contact_form_email}}" required>
            {{#contact_errors.email}}<p class="field-error">{{.}}</p>{{/contact_errors.email}}
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 form-group">
            <input type="text" class="form-control" name="subject" id="subject" value="{{contact_old.subject}}" placeholder="{{str.contact_form_subject}}">
            {{#contact_errors.subject}}<p class="field-error">{{.}}</p>{{/contact_errors.subject}}
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 form-group">
            <textarea class="form-control" name="message" id="message" cols="30" rows="7"
              placeholder="{{str.contact_form_message}}" required>{{contact_old.message}}</textarea>
            {{#contact_errors.message}}<p class="field-error">{{.}}</p>{{/contact_errors.message}}
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <input type="submit" value="{{str.contact_form_submit}}" class="btn-style-one">
            <span class="submitting"></span>
          </div>
        </div>
      </form>
    </div>

  </div>
</section>
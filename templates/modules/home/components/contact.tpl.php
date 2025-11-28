<section id="contact" class="contact section" aria-labelledby="contact-title">
  <div class="contain">

    <div class="title">
      <div class="section-title padd-15">
        <h2 id="contact-title">{{str.contact_title}}</h2>
        <p>{{str.contact_intro}}</p>
      </div>
    </div>

    <address class="coord" itemscope itemtype="https://schema.org/Organization">
      <div class="section-title padd-15">
        <h3>{{str.contact_coords_title}}</h3>
        <p itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
          <strong>{{str.contact_address_label}}</strong>
          <span itemprop="streetAddress">Le Buzuk, 39 bellevue de la madeleine</span>, 
          <span itemprop="postalCode">29600</span> <span itemprop="addressLocality">Morlaix</span>
        </p>
        <p>
          <strong>{{str.contact_phone_label}}</strong>
          <a href="tel:+33615068208" itemprop="telephone">06 15 06 82 08</a>
        </p>
        <p>
          <strong>{{str.contact_email_label}}</strong>
          <a href="mailto:ssapaysdemorlaix@mailo.com" itemprop="email">ssapaysdemorlaix@mailo.com</a>
        </p>
      </div>
    </address>

    <img src="/assets/img/contact.svg" alt="Illustration de contact" role="presentation">

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

      <form class="mb-5" method="post" id="contactForm" name="contactForm" action="{{contact_action}}" novalidate aria-label="Formulaire de contact">
        {{{csrf_input}}}
        <div class="honeypot" aria-hidden="true" style="position:absolute;left:-9999px;">
          <label for="website">Site web</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        </div>
        <div class="honeypot-checkbox" aria-hidden="true" style="display:none;">
          <label for="confirm_robot">Ne pas cocher</label>
          <input type="checkbox" id="confirm_robot" name="confirm_robot" tabindex="-1" aria-hidden="true">
        </div>
        <div class="row">
          <div class="col-md-12 form-group">
            <label for="name" class="visually-hidden">{{str.contact_form_name}}</label>
            <input type="text" class="form-control" name="name" id="name" value="{{contact_old.name}}" placeholder="{{str.contact_form_name}}" required aria-required="true" aria-invalid="{{#contact_errors.name}}true{{/contact_errors.name}}{{^contact_errors.name}}false{{/contact_errors.name}}">
            {{#contact_errors.name}}<p class="field-error" role="alert" aria-live="polite">{{.}}</p>{{/contact_errors.name}}
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 form-group">
            <label for="email" class="visually-hidden">{{str.contact_form_email}}</label>
            <input type="email" class="form-control" name="email" id="email" value="{{contact_old.email}}" placeholder="{{str.contact_form_email}}" required aria-required="true" aria-invalid="{{#contact_errors.email}}true{{/contact_errors.email}}{{^contact_errors.email}}false{{/contact_errors.email}}">
            {{#contact_errors.email}}<p class="field-error" role="alert" aria-live="polite">{{.}}</p>{{/contact_errors.email}}
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 form-group">
            <label for="subject" class="visually-hidden">{{str.contact_form_subject}}</label>
            <input type="text" class="form-control" name="subject" id="subject" value="{{contact_old.subject}}" placeholder="{{str.contact_form_subject}}" aria-invalid="{{#contact_errors.subject}}true{{/contact_errors.subject}}{{^contact_errors.subject}}false{{/contact_errors.subject}}">
            {{#contact_errors.subject}}<p class="field-error" role="alert" aria-live="polite">{{.}}</p>{{/contact_errors.subject}}
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 form-group">
            <label for="message" class="visually-hidden">{{str.contact_form_message}}</label>
            <textarea class="form-control" name="message" id="message" cols="30" rows="7"
              placeholder="{{str.contact_form_message}}" required aria-required="true" aria-invalid="{{#contact_errors.message}}true{{/contact_errors.message}}{{^contact_errors.message}}false{{/contact_errors.message}}">{{contact_old.message}}</textarea>
            {{#contact_errors.message}}<p class="field-error" role="alert" aria-live="polite">{{.}}</p>{{/contact_errors.message}}
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <input type="submit" value="{{str.contact_form_submit}}" class="btn-style-one" aria-label="Envoyer le formulaire de contact">
            <span class="submitting" aria-live="polite" aria-atomic="true"></span>
          </div>
        </div>
      </form>
    </div>

  </div>
</section>
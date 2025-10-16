<div class="background">
  <section id="contact" class="contact">
    <h2>{{str.contact_title}}</h2>
    <p>{{str.contact_intro}}</p>

    <div class="infos">
      <div class="contact-info">
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

      <div class="contact-form">
        <h3>{{str.contact_form_title}}</h3>

        <form id="contact-form" method="post" action="{{contact_action}}">
          <label for="name">{{str.contact_form_name}}</label>
          <input type="text" id="name" name="name" required>

          <label for="email">{{str.contact_form_email}}</label>
          <input type="email" id="email" name="email" required>

          <label for="message">{{str.contact_form_message}}</label>
          <textarea id="message" name="message" rows="5" required></textarea>

          <button type="submit" class="btn primary">{{str.contact_form_submit}}</button>
        </form>
      </div>
    </div>
  </section>
</div>

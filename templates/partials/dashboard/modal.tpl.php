<?php
/**
 * Universal Modal Component
 * 
 * Usage:
 * {{> partial:dashboard/modal 
 *    id="my-modal"
 *    title="Modal Title"
 *    content="{{{modalContent}}}"
 *    submitText="Valider"
 *    submitUrl="/path/to/submit"
 *    cancelText="Annuler"
 * }}
 */
?>

<dialog id="{{modalId}}" class="universal-modal" data-modal-id="{{modalId}}">
  <div class="modal-overlay">
    <div class="modal-content">
      <!-- Header -->
      <div class="modal-header">
        <h2>{{modalTitle}}</h2>
        <button type="button" class="modal-close-btn" aria-label="Fermer la modal">
          <span>&times;</span>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        {{{modalBody}}}
      </div>

      <!-- Footer -->
      {{#showFooter}}
      <div class="modal-footer">
        <button type="button" class="modal-cancel-btn btn btn-secondary">
          {{cancelText}}
        </button>
        <button type="submit" form="{{formId}}" class="modal-submit-btn btn btn-primary">
          {{submitText}}
        </button>
      </div>
      {{/showFooter}}
    </div>
  </div>
</dialog>

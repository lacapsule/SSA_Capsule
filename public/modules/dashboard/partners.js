const previewSelectors = 'form input[type="file"][name="logo"]';

function bindPreview(input) {
  const form = input.closest('form');
  if (!form) return;

  const preview = form.querySelector('.js-logo-preview');
  if (!preview) return;

  input.addEventListener('change', () => {
    const file = input.files && input.files[0];
    if (!file) {
      preview.innerHTML = '';
      preview.classList.remove('has-image');
      return;
    }

    if (!file.type.startsWith('image/')) {
      preview.innerHTML = '<p class="field-error">Format non supporté.</p>';
      preview.classList.remove('has-image');
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      preview.innerHTML = `<img src="${reader.result}" alt="Prévisualisation du logo">`;
      preview.classList.add('has-image');
    };
    reader.readAsDataURL(file);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll(previewSelectors).forEach(bindPreview);
});


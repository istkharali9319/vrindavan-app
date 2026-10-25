<script>
  document.querySelectorAll('[data-image-input]').forEach((input) => {
    input.addEventListener('change', () => {
      const [file] = input.files;
      const preview = document.querySelector(`[data-image-preview="${input.dataset.imageInput}"]`);
      if (file && preview) {
        preview.src = URL.createObjectURL(file);
      }
    });
  });
</script>

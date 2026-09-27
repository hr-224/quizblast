/* Binds every form[data-confirm] on the page so its submit needs a confirm() first.
   Replaces scattered onsubmit="return confirm('...')" attributes. */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form[data-confirm]');
    for (var i = 0; i < forms.length; i++) {
      forms[i].addEventListener('submit', function (e) {
        if (!window.confirm(this.dataset.confirm)) e.preventDefault();
      });
    }
  });
})();

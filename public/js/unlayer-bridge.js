(function () {
  function parseJson(value, fallback) {
    if (!value) return fallback;
    try {
      return JSON.parse(value);
    } catch (e) {
      return fallback;
    }
  }

  window.MailmanagerUnlayer = {
    init: function (options) {
      if (typeof unlayer === 'undefined') {
        console.warn('Unlayer CDN not loaded; using textarea fallback.');
        return;
      }

      var containerId = options.containerId || 'mailmanager-unlayer';
      var projectId = options.projectId;
      if (!projectId) {
        return;
      }

      unlayer.init({
        id: containerId,
        projectId: projectId,
        displayMode: options.displayMode || 'email',
        locale: options.locale || 'en-US',
      });

      var design = parseJson(options.designJson, null);
      if (design) {
        unlayer.loadDesign(design);
      }

      var form = document.getElementById(options.formId || 'mailmanager-template-form');
      if (!form) return;

      form.addEventListener('submit', function (event) {
        if (form.dataset.unlayerReady === '1') {
          return;
        }
        event.preventDefault();

        unlayer.saveDesign(function (designData) {
          var designInput = document.getElementById(options.designInputId || 'design_json');
          if (designInput) {
            designInput.value = JSON.stringify(designData || {});
          }

          unlayer.exportHtml(function (data) {
            var htmlInput = document.getElementById(options.htmlInputId || 'html_content');
            if (htmlInput && data && data.html) {
              htmlInput.value = data.html;
            }
            form.dataset.unlayerReady = '1';
            form.requestSubmit();
          });
        });
      });
    },
  };
})();

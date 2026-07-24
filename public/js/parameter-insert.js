(function () {
  window.MailmanagerParameters = {
    insert: function (targetId, token) {
      var el = document.getElementById(targetId);
      if (!el) return;
      var start = el.selectionStart || 0;
      var end = el.selectionEnd || 0;
      var value = el.value || '';
      el.value = value.slice(0, start) + token + value.slice(end);
      el.focus();
      var pos = start + token.length;
      if (typeof el.setSelectionRange === 'function') {
        el.setSelectionRange(pos, pos);
      }
    },
  };
})();

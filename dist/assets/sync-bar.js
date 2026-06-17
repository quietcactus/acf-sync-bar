(function () {
  'use strict';

  if (typeof window.ACF_SYNC_BAR === 'undefined') {
    return;
  }

  var cfg = window.ACF_SYNC_BAR;

  function nodeKeyFor(el) {
    var li = el.closest('li');
    if (!li || !li.id) {
      return null;
    }
    if (li.id === cfg.allNode) {
      return '__all__';
    }
    if (cfg.keys && cfg.keys[li.id]) {
      return cfg.keys[li.id];
    }
    return null;
  }

  function setBusy(el, busy) {
    var link = el.tagName === 'A' ? el : el.querySelector('a');
    if (!link) {
      return;
    }
    if (busy) {
      link.dataset.originalText = link.innerHTML;
      link.innerHTML = cfg.syncingLabel;
      link.style.pointerEvents = 'none';
      link.style.opacity = '0.6';
    } else if (link.dataset.originalText) {
      link.innerHTML = link.dataset.originalText;
      link.style.pointerEvents = '';
      link.style.opacity = '';
    }
  }

  function removeNodeById(id) {
    var el = document.getElementById(id);
    if (el && el.parentNode) {
      el.parentNode.removeChild(el);
    }
  }

  function updateParentCount() {
    var parent = document.getElementById(cfg.parent);
    if (!parent) {
      return;
    }
    var items = parent.querySelectorAll('li.acf-sync-bar-item');
    var count = items.length;
    var label = parent.querySelector('a .ab-label, a');
    if (count === 0) {
      parent.parentNode.removeChild(parent);
      return;
    }
    if (label) {
      label.firstChild && label.firstChild.nodeType === 3
        ? (label.firstChild.nodeValue = 'ACF Sync (' + count + ') ')
        : (label.textContent = 'ACF Sync (' + count + ')');
    }
    if (count <= 1) {
      removeNodeById(cfg.allNode);
    } else {
      var allLink = document.querySelector('#' + cfg.allNode + ' a strong');
      if (allLink) {
        allLink.textContent = 'Sync All (' + count + ')';
      }
    }
  }

  function performSync(li, key) {
    setBusy(li, true);

    var body = new URLSearchParams();
    body.append('action', 'acf_sync_bar_sync');
    body.append('nonce', cfg.nonce);
    body.append('key', key);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json || !json.success) {
          var msg = (json && json.data && json.data.message) ? json.data.message : 'Sync failed.';
          window.alert('ACF Sync Bar: ' + msg);
          setBusy(li, false);
          return;
        }
        var synced = (json.data && json.data.synced) || [];
        synced.forEach(function (syncedKey) {
          for (var nodeId in cfg.keys) {
            if (cfg.keys[nodeId] === syncedKey) {
              removeNodeById(nodeId);
              delete cfg.keys[nodeId];
            }
          }
        });
        updateParentCount();
      })
      .catch(function () {
        window.alert('ACF Sync Bar: network error.');
        setBusy(li, false);
      });
  }

  document.addEventListener('click', function (e) {
    var li = e.target.closest('li.acf-sync-bar-action');
    if (!li) {
      return;
    }
    e.preventDefault();
    e.stopPropagation();

    var key = nodeKeyFor(li);
    if (!key) {
      return;
    }

    if (!window.confirm(cfg.confirm)) {
      return;
    }

    performSync(li, key);
  });
}());

/**
 * Admin Mercure subscriber — live toasts, notification bell, and list refresh (no page reload).
 */
(function () {
  const REALTIME_EVENTS = new Set([
    'order.created',
    'order.updated',
    'booking.created',
    'booking.updated',
    'payment.created',
  ]);

  const ROUTES = {
    orders: '/admin/orders',
    bookings: '/admin/bookings',
    payments: '/admin/payments',
    dashboard: '/admin',
  };

  const LIST_ENDPOINTS = {
    orders: '/admin/realtime/orders',
    bookings: '/admin/realtime/bookings',
    payments: '/admin/realtime/payments',
  };

  const EVENT_LIST_KIND = {
    'order.created': 'orders',
    'order.updated': 'orders',
    'booking.created': 'bookings',
    'booking.updated': 'bookings',
    'payment.created': 'payments',
  };

  let eventSource = null;
  let reconnectTimer = null;
  let pollTimer = null;
  let connectWatchdog = null;
  let connected = false;
  let pollMode = false;
  let lastEventId = 0;
  const seenIds = new Set();
  const MAX_NOTIFICATIONS = 40;

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatMoney(amount) {
    const n = Number(amount);
    if (Number.isNaN(n)) {
      return '—';
    }
    return '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function setLiveStatus(state, title) {
    const pill = document.getElementById('adminLivePill');
    if (!pill) {
      return;
    }
    pill.classList.remove('is-live', 'is-offline', 'is-connecting');
    pill.classList.add(
      state === 'live' ? 'is-live' : state === 'offline' ? 'is-offline' : 'is-connecting'
    );
    pill.title = title || '';
    const label = pill.querySelector('.admin-live-label');
    if (label) {
      label.textContent = state === 'live' ? 'Live' : state === 'offline' ? 'Offline' : 'Connecting…';
    }
  }

  function showToast(message) {
    var toast = document.createElement('div');
    toast.className = 'admin-realtime-toast';
    toast.setAttribute('role', 'status');
    toast.textContent = message;
    document.body.appendChild(toast);
    window.requestAnimationFrame(function () {
      toast.classList.add('visible');
    });
    window.setTimeout(function () {
      toast.classList.remove('visible');
      window.setTimeout(function () {
        toast.remove();
      }, 300);
    }, 4200);
  }

  function eventLabel(type, payload) {
    switch (type) {
      case 'order.created':
        return 'New order #' + (payload.orderId || '—') + ' from ' + (payload.customerName || 'customer');
      case 'order.updated':
        return 'Order #' + (payload.orderId || '—') + ' → ' + (payload.status || 'updated');
      case 'booking.created':
        return 'New booking #' + (payload.bookingId || '—') + ' — ' + (payload.productName || 'service');
      case 'booking.updated':
        return 'Booking #' + (payload.bookingId || '—') + ' → ' + (payload.status || 'updated');
      case 'payment.created':
        return 'Payment ' + formatMoney(payload.amount) + ' for order #' + (payload.orderId || '—');
      default:
        return 'Live update received';
    }
  }

  function notificationLink(type) {
    if (type.startsWith('order.')) {
      return ROUTES.orders;
    }
    if (type.startsWith('booking.')) {
      return ROUTES.bookings;
    }
    if (type.startsWith('payment.')) {
      return ROUTES.payments;
    }
    return ROUTES.dashboard;
  }

  function notificationAvatar(type) {
    if (type.startsWith('order.')) {
      return 'OR';
    }
    if (type.startsWith('booking.')) {
      return 'BK';
    }
    if (type.startsWith('payment.')) {
      return '₱';
    }
    return '•';
  }

  function notificationSubtitle(type, payload) {
    if (type.startsWith('order.')) {
      return (payload.productName || 'Service') + ' · ' + (payload.customerName || '');
    }
    if (type.startsWith('booking.')) {
      return (payload.productName || 'Service') + ' · ' + (payload.customerName || '');
    }
    if (type.startsWith('payment.')) {
      return (payload.customerName || 'Customer') + ' · ' + formatMoney(payload.amount);
    }
    return '';
  }

  function dedupeKey(type, payload) {
    const id =
      payload.orderId || payload.bookingId || payload.paymentId || payload.at || Date.now();
    return type + ':' + id;
  }

  function prependNotification(type, payload) {
    const list = document.getElementById('notificationList');
    const empty = document.getElementById('notificationEmpty');
    const badge = document.getElementById('notificationCount');
    if (!list) {
      return;
    }

    const key = dedupeKey(type, payload);
    if (seenIds.has(key)) {
      return;
    }

    if (empty) {
      empty.style.display = 'none';
    }

    const item = document.createElement('a');
    item.href = notificationLink(type);
    item.className = 'notification-item unread';
    item.dataset.realtimeKey = key;
    item.innerHTML =
      '<div class="notification-avatar">' +
      escapeHtml(notificationAvatar(type)) +
      '</div>' +
      '<div class="notification-content">' +
      '<div class="notification-title">' +
      escapeHtml(eventLabel(type, payload)) +
      '</div>' +
      '<div class="notification-text">' +
      escapeHtml(notificationSubtitle(type, payload)) +
      '</div>' +
      '</div>';

    list.insertBefore(item, list.firstChild);

    const items = list.querySelectorAll('.notification-item');
    if (items.length > MAX_NOTIFICATIONS) {
      items[items.length - 1].remove();
    }

    updateBadge();

    item.addEventListener('click', function () {
      item.classList.remove('unread');
      updateBadge();
    });
  }

  function updateBadge() {
    const badge = document.getElementById('notificationCount');
    const unread = document.querySelectorAll('.notification-item.unread').length;
    if (!badge) {
      return;
    }
    if (unread > 0) {
      badge.textContent = unread > 99 ? '99+' : String(unread);
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  function refreshCounts() {
    return fetch('/admin/realtime/counts', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(function (r) {
        return r.ok ? r.json() : null;
      })
      .then(function (body) {
        if (!body || !body.data) {
          return;
        }
        const d = body.data;
        document.querySelectorAll('[data-realtime-count="pendingBookings"]').forEach(function (el) {
          el.textContent = d.pendingBookings + ' pending';
        });
        document.querySelectorAll('[data-realtime-count="pendingOrders"]').forEach(function (el) {
          el.textContent = d.pendingOrders + ' unpaid';
        });
        document.querySelectorAll('[data-hub-count="totalBookings"]').forEach(function (el) {
          el.dataset.count = d.totalBookings;
          el.textContent = d.totalBookings;
        });
        document.querySelectorAll('[data-hub-count="totalOrders"]').forEach(function (el) {
          el.dataset.count = d.totalOrders;
          el.textContent = d.totalOrders;
        });
        document.querySelectorAll('[data-hub-count="totalPayments"]').forEach(function (el) {
          el.dataset.count = d.totalPayments;
          el.textContent = d.totalPayments;
        });
        document.querySelectorAll('[data-hub-count="totalRevenue"]').forEach(function (el) {
          el.dataset.count = d.totalRevenue;
          el.textContent = '₱' + Number(d.totalRevenue).toLocaleString(undefined, { minimumFractionDigits: 2 });
        });
      })
      .catch(function () {});
  }

  function statusClass(status) {
    return 'status-' + String(status || '').toLowerCase();
  }

  function renderOrdersTable(rows) {
    const tbody = document.querySelector('[data-realtime-table="orders"] tbody');
    const countEl = document.querySelector('[data-realtime-table="orders"]')?.closest('.list-card')?.querySelector('.list-count');
    if (!tbody) {
      return;
    }
    if (!rows.length) {
      tbody.innerHTML =
        '<tr><td colspan="8" class="empty-cell">No orders yet. Orders are created from the mobile app.</td></tr>';
      if (countEl) {
        countEl.textContent = '0 total';
      }
      return;
    }
    tbody.innerHTML = rows
      .map(function (row) {
        const actions =
          row.status === 'PENDING'
            ? '<form method="post" action="/admin/orders/' +
              row.id +
              '/approve" class="inline-form">' +
              '<input type="hidden" name="_token" value="' +
              escapeHtml(row.csrfToken) +
              '">' +
              '<button type="submit" class="btn-approve">Approve</button></form>' +
              '<form method="post" action="/admin/orders/' +
              row.id +
              '/reject" class="inline-form">' +
              '<input type="hidden" name="_token" value="' +
              escapeHtml(row.csrfToken) +
              '">' +
              '<button type="submit" class="btn-reject">Reject</button></form>'
            : '<span class="muted-action">—</span>';
        return (
          '<tr data-order-id="' +
          row.id +
          '">' +
          '<td><strong>' +
          row.id +
          '</strong></td>' +
          '<td>' +
          escapeHtml(row.customerName) +
          '<br><small>' +
          escapeHtml(row.customerEmail) +
          '</small></td>' +
          '<td>' +
          escapeHtml(row.productName) +
          '</td>' +
          '<td>' +
          row.quantity +
          '</td>' +
          '<td>' +
          formatMoney(row.totalAmount) +
          '</td>' +
          '<td><span class="status-pill ' +
          statusClass(row.status) +
          '">' +
          escapeHtml(row.status) +
          '</span></td>' +
          '<td>' +
          escapeHtml(row.createdAt) +
          '</td>' +
          '<td class="order-actions">' +
          actions +
          '</td>' +
          '</tr>'
        );
      })
      .join('');
    if (countEl) {
      countEl.textContent = rows.length + ' total';
    }
  }

  function renderBookingsTable(rows) {
    const tbody = document.querySelector('[data-realtime-table="bookings"] tbody');
    const countEl = document.querySelector('[data-realtime-table="bookings"]')?.closest('.list-card')?.querySelector('.list-count');
    if (!tbody) {
      return;
    }
    if (!rows.length) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="empty-cell">No bookings yet. Customers can book services from the mobile app.</td></tr>';
      if (countEl) {
        countEl.textContent = '0 total';
      }
      return;
    }
    tbody.innerHTML = rows
      .map(function (row) {
        return (
          '<tr data-booking-id="' +
          row.id +
          '">' +
          '<td><strong>' +
          row.id +
          '</strong></td>' +
          '<td>' +
          escapeHtml(row.customerName) +
          '<br><small>' +
          escapeHtml(row.customerEmail) +
          '</small></td>' +
          '<td>' +
          escapeHtml(row.productName) +
          '</td>' +
          '<td>' +
          escapeHtml(row.scheduledAt) +
          '</td>' +
          '<td><span class="status-pill ' +
          statusClass(row.status) +
          '">' +
          escapeHtml(row.status) +
          '</span></td>' +
          '<td>' +
          escapeHtml(row.createdAt) +
          '</td>' +
          '</tr>'
        );
      })
      .join('');
    if (countEl) {
      countEl.textContent = rows.length + ' total';
    }
  }

  function renderPaymentsTable(rows) {
    const tbody = document.querySelector('[data-realtime-table="payments"] tbody');
    const countEl = document.querySelector('[data-realtime-table="payments"]')?.closest('.list-card')?.querySelector('.list-count');
    if (!tbody) {
      return;
    }
    if (!rows.length) {
      tbody.innerHTML =
        '<tr><td colspan="7" class="empty-cell">No payments yet. Customers pay for orders in the mobile app.</td></tr>';
      if (countEl) {
        countEl.textContent = '0 total';
      }
      return;
    }
    tbody.innerHTML = rows
      .map(function (row) {
        return (
          '<tr data-payment-id="' +
          row.id +
          '">' +
          '<td><strong>' +
          row.id +
          '</strong></td>' +
          '<td>' +
          escapeHtml(row.customerName) +
          '<br><small>' +
          escapeHtml(row.customerEmail) +
          '</small></td>' +
          '<td>#' +
          row.orderId +
          ' — ' +
          escapeHtml(row.productName) +
          '</td>' +
          '<td><strong>' +
          formatMoney(row.amount) +
          '</strong></td>' +
          '<td>' +
          escapeHtml(String(row.method || '').charAt(0).toUpperCase() + String(row.method || '').slice(1)) +
          '</td>' +
          '<td><span class="status-pill ' +
          statusClass(row.status) +
          '">' +
          escapeHtml(row.status) +
          '</span></td>' +
          '<td>' +
          escapeHtml(row.paidAt || '—') +
          '</td>' +
          '</tr>'
        );
      })
      .join('');
    if (countEl) {
      countEl.textContent = rows.length + ' total';
    }
  }

  function refreshList(kind) {
    const url = LIST_ENDPOINTS[kind];
    if (!url || !document.querySelector('[data-realtime-table="' + kind + '"]')) {
      return Promise.resolve();
    }
    return fetch(url, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(function (r) {
        return r.ok ? r.json() : null;
      })
      .then(function (body) {
        if (!body || !Array.isArray(body.data)) {
          return;
        }
        if (kind === 'orders') {
          renderOrdersTable(body.data);
        } else if (kind === 'bookings') {
          renderBookingsTable(body.data);
        } else if (kind === 'payments') {
          renderPaymentsTable(body.data);
        }
      })
      .catch(function () {});
  }

  function markLive(mode) {
    connected = true;
    pollMode = mode === 'poll';
    setLiveStatus('live', pollMode
      ? 'Live updates active (polling)'
      : 'Receiving live updates from the mobile app');
  }

  function processParsedEvent(parsed) {
    if (!parsed || !REALTIME_EVENTS.has(parsed.type)) {
      return;
    }

    const payload = parsed.payload || {};
    const key = dedupeKey(parsed.type, payload);
    if (seenIds.has(key)) {
      return;
    }
    seenIds.add(key);
    if (seenIds.size > 200) {
      seenIds.clear();
    }

    const message = eventLabel(parsed.type, payload);

    showToast(message);
    prependNotification(parsed.type, payload);
    refreshCounts();

    const listKind = EVENT_LIST_KIND[parsed.type];
    if (listKind) {
      refreshList(listKind);
    }

    window.dispatchEvent(
      new CustomEvent('admin-realtime', {
        detail: { type: parsed.type, payload: payload, at: parsed.at },
      })
    );
  }

  function handleEvent(raw) {
    var parsed;
    try {
      parsed = JSON.parse(raw);
    } catch (e) {
      return;
    }
    processParsedEvent(parsed);
  }

  function pollEvents() {
    fetch('/admin/realtime/poll?since=' + encodeURIComponent(String(lastEventId)), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Poll failed');
        }
        return response.json();
      })
      .then(function (body) {
        if (!body || !Array.isArray(body.data)) {
          return;
        }
        markLive('poll');
        body.data.forEach(function (event) {
          if (event && event.id) {
            lastEventId = Math.max(lastEventId, event.id);
          }
          processParsedEvent(event);
        });
      })
      .catch(function () {
        if (!connected) {
          setLiveStatus('offline', 'Live updates unavailable — check Mercure and refresh the page');
        }
      });
  }

  function startPolling() {
    if (pollTimer) {
      return;
    }
    pollEvents();
    pollTimer = window.setInterval(pollEvents, 2500);
  }

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  function armConnectWatchdog() {
    if (connectWatchdog) {
      clearTimeout(connectWatchdog);
    }
    connectWatchdog = window.setTimeout(function () {
      connectWatchdog = null;
      if (!connected) {
        startPolling();
      }
    }, 4000);
  }

  function connect(streamUrl) {
    if (eventSource) {
      eventSource.close();
      eventSource = null;
    }

    eventSource = new EventSource(streamUrl, { withCredentials: true });

    eventSource.onopen = function () {
      markLive('stream');
      stopPolling();
    };

    eventSource.onmessage = function (event) {
      handleEvent(event.data);
    };

    eventSource.onerror = function () {
      if (eventSource && eventSource.readyState === EventSource.CONNECTING) {
        setLiveStatus('connecting', 'Connecting to live updates…');
        return;
      }

      connected = false;
      pollMode = false;
      setLiveStatus('offline', 'Live connection lost — retrying…');
      if (eventSource) {
        eventSource.close();
        eventSource = null;
      }
      scheduleReconnect();
    };
  }

  function scheduleReconnect() {
    if (reconnectTimer) {
      return;
    }
    reconnectTimer = window.setTimeout(function () {
      reconnectTimer = null;
      bootstrap();
    }, 3000);
  }

  function bootstrap() {
    setLiveStatus('connecting', 'Connecting to live updates…');
    armConnectWatchdog();

    fetch('/admin/realtime/token', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Realtime unavailable (HTTP ' + response.status + ')');
        }
        return response.json();
      })
      .then(function (body) {
        var data = body && body.data;
        var streamUrl = (data && data.streamUrl) || '/admin/realtime/events';
        connect(streamUrl);
      })
      .catch(function () {
        connect('/admin/realtime/events');
      });
  }

  function initNotificationDropdown() {
    const btn = document.getElementById('notificationBtn');
    const dropdown = document.getElementById('notificationDropdown');
    const markRead = document.getElementById('markAllRead');
    if (!btn || !dropdown) {
      return;
    }

    document.body.appendChild(dropdown);
    dropdown.style.position = 'fixed';
    dropdown.style.zIndex = '2147483647';

    function positionDropdown() {
      const rect = btn.getBoundingClientRect();
      dropdown.style.top = rect.bottom + 10 + 'px';
      dropdown.style.right = window.innerWidth - rect.right + 'px';
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const open = !dropdown.classList.contains('active');
      if (open) {
        positionDropdown();
      }
      dropdown.classList.toggle('active');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    window.addEventListener('resize', function () {
      if (dropdown.classList.contains('active')) {
        positionDropdown();
      }
    });

    document.addEventListener('click', function (e) {
      if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.classList.remove('active');
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    if (markRead) {
      markRead.addEventListener('click', function (e) {
        e.preventDefault();
        dropdown.querySelectorAll('.notification-item.unread').forEach(function (item) {
          item.classList.remove('unread');
        });
        updateBadge();
      });
    }
  }

  function init() {
    initNotificationDropdown();
    refreshCounts();
    bootstrap();
    startPolling();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.addEventListener('beforeunload', function () {
    if (eventSource) {
      eventSource.close();
    }
    if (reconnectTimer) {
      clearTimeout(reconnectTimer);
    }
    if (connectWatchdog) {
      clearTimeout(connectWatchdog);
    }
    stopPolling();
  });
})();

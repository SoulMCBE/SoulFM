/**
 * SoulFM Admin Panel JavaScript
 */
(function () {
  'use strict';

  /* ===================== SIDEBAR TOGGLE ===================== */
  const sidebarToggle  = document.getElementById('sidebar-toggle');
  const sidebar        = document.getElementById('admin-sidebar');
  const sidebarOverlay = document.getElementById('sidebar-overlay');

  function openSidebar() {
    sidebar?.classList.add('open');
    sidebarOverlay?.classList.add('open');
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    sidebarOverlay?.classList.remove('open');
  }

  sidebarToggle?.addEventListener('click', () => {
    sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  sidebarOverlay?.addEventListener('click', closeSidebar);

  /* ===================== MODALS ===================== */
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.modalOpen;
      openModal(id);
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.modalClose || btn.closest('.modal-overlay')?.id;
      if (id) closeModal(id);
    });
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  window.openModal = function (id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
      // Focus first input
      setTimeout(() => modal.querySelector('input, select, textarea')?.focus(), 100);
    }
  };

  window.closeModal = function (id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
    }
  };

  // ESC to close modals
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => {
        closeModal(m.id);
      });
    }
  });

  /* ===================== REQUEST STATUS UPDATES ===================== */
  document.querySelectorAll('[data-update-status]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id     = btn.dataset.id;
      const status = btn.dataset.updateStatus;
      const row    = document.getElementById('request-row-' + id);

      btn.disabled = true;
      btn.style.opacity = '0.5';

      try {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('status', status);
        fd.append('csrf_token', document.querySelector('[name="csrf_token"]')?.value || '');

        const res  = await fetch('', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          if (status === 'played' || status === 'rejected') {
            // Update badge
            const badge = row?.querySelector('.badge');
            if (badge) {
              badge.className = 'badge badge-' + status;
              badge.textContent = status === 'played' ? 'Gespeeld' : 'Afgewezen';
            }
            // Disable action buttons
            row?.querySelectorAll('[data-update-status]').forEach(b => {
              b.disabled = true;
              b.style.opacity = '0.3';
            });
          }
          showAdminToast(data.message || 'Status bijgewerkt', 'success');
        } else {
          showAdminToast(data.message || 'Fout bij bijwerken', 'error');
          btn.disabled = false;
          btn.style.opacity = '1';
        }
      } catch {
        showAdminToast('Verbindingsfout', 'error');
        btn.disabled = false;
        btn.style.opacity = '1';
      }
    });
  });

  /* ===================== DELETE CONFIRMATIONS ===================== */
  document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
    btn.addEventListener('click', e => {
      const msg = btn.dataset.confirmDelete || 'Weet je zeker dat je dit wilt verwijderen?';
      if (!confirm(msg)) {
        e.preventDefault();
        e.stopImmediatePropagation();
      }
    });
  });

  /* ===================== AUTO SLUG FROM TITLE ===================== */
  const titleInput = document.getElementById('news-title');
  const slugInput  = document.getElementById('news-slug');

  if (titleInput && slugInput && !slugInput.value) {
    titleInput.addEventListener('input', () => {
      slugInput.value = generateSlug(titleInput.value);
    });
  }

  function generateSlug(text) {
    return text
      .toLowerCase()
      .replace(/[àáâãäå]/g, 'a')
      .replace(/[èéêë]/g, 'e')
      .replace(/[ìíîï]/g, 'i')
      .replace(/[òóôõö]/g, 'o')
      .replace(/[ùúûü]/g, 'u')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/[\s-]+/g, '-')
      .replace(/^-|-$/g, '');
  }

  /* ===================== FILTER BUTTONS ===================== */
  document.querySelectorAll('.filter-btn[data-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.filterGroup || 'default';
      document.querySelectorAll(`.filter-btn[data-filter-group="${group}"]`).forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.dataset.filter;
      const rows   = document.querySelectorAll('[data-status]');

      rows.forEach(row => {
        row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
      });
    });
  });

  /* ===================== COLOR PREVIEW ===================== */
  document.querySelectorAll('[data-color-preview]').forEach(input => {
    const previewId = input.dataset.colorPreview;
    const preview   = document.getElementById(previewId);
    if (preview) {
      preview.style.background = input.value;
      input.addEventListener('input', () => {
        preview.style.background = input.value;
      });
    }
  });

  /* ===================== SCHEDULE EDIT MODAL ===================== */
  document.querySelectorAll('[data-edit-schedule]').forEach(btn => {
    btn.addEventListener('click', () => {
      const data = JSON.parse(btn.dataset.editSchedule);
      const modal = document.getElementById('schedule-modal');
      if (!modal) return;

      modal.querySelector('[name="id"]').value           = data.id || '';
      modal.querySelector('[name="day_of_week"]').value  = data.day_of_week || '';
      modal.querySelector('[name="start_time"]').value   = data.start_time || '';
      modal.querySelector('[name="end_time"]').value     = data.end_time || '';
      modal.querySelector('[name="program_name"]').value = data.program_name || '';
      modal.querySelector('[name="dj_name"]').value      = data.dj_name || '';
      modal.querySelector('[name="dj_bio"]').value       = data.dj_bio || '';
      modal.querySelector('[name="genre"]').value        = data.genre || '';

      const title = modal.querySelector('.modal-title');
      if (title) title.textContent = data.id ? 'Programma bewerken' : 'Programma toevoegen';

      openModal('schedule-modal');
    });
  });

  /* ===================== USER ROLE INLINE UPDATE ===================== */
  document.querySelectorAll('[data-role-update]').forEach(select => {
    select.addEventListener('change', async () => {
      const userId = select.dataset.roleUpdate;
      const newRole = select.value;
      const csrf = document.querySelector('[name="csrf_token"]')?.value || '';

      const fd = new FormData();
      fd.append('action', 'update_role');
      fd.append('user_id', userId);
      fd.append('role', newRole);
      fd.append('csrf_token', csrf);

      try {
        const res  = await fetch('', { method: 'POST', body: fd });
        const data = await res.json();
        showAdminToast(data.message || 'Rol bijgewerkt', data.success ? 'success' : 'error');
      } catch {
        showAdminToast('Verbindingsfout', 'error');
      }
    });
  });

  /* ===================== ADMIN TOAST ===================== */
  window.showAdminToast = function (message, type = 'success') {
    let container = document.querySelector('.admin-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'admin-toast-container';
      container.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.75rem;pointer-events:none;';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `padding:.8rem 1.2rem;border-radius:8px;font-size:.88rem;font-weight:500;box-shadow:0 4px 20px rgba(0,0,0,0.4);pointer-events:all;animation:slideIn .3s ease;max-width:320px;`;
    const colors = {
      success: 'background:#065f46;border:1px solid #059669;color:#a7f3d0;',
      error:   'background:#7f1d1d;border:1px solid #dc2626;color:#fca5a5;',
      info:    'background:#1e3a5f;border:1px solid #0096c7;color:#48cae4;',
    };
    toast.style.cssText += (colors[type] || colors.info);
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'slideOut .3s ease forwards';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  };

  /* ===================== TABLE SEARCH ===================== */
  document.querySelectorAll('[data-search-table]').forEach(input => {
    const tableId = input.dataset.searchTable;
    const table   = document.getElementById(tableId);
    if (!table) return;

    input.addEventListener('input', () => {
      const term = input.value.toLowerCase();
      table.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    });
  });

})();

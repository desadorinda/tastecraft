<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/database.php';

$db = new Database();
$pdo = $db->getConnection();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error = null;

// Secure actions via POST (mark read, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new RuntimeException('Invalid CSRF token.');
        }
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['message_id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Invalid message ID.');
        }

        if ($action === 'mark_read') {
            $stmt = $pdo->prepare("UPDATE messages SET status='read' WHERE id=?");
            $stmt->execute([$id]);
            header("Location: " . basename(__FILE__) . "?updated=1");
            exit;
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id=?");
            $stmt->execute([$id]);
            header("Location: " . basename(__FILE__) . "?deleted=1");
            exit;
        } else {
            throw new RuntimeException('Unknown action.');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Fetch messages
$messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE status='unread'")->fetchColumn();

$pageTitle = "Messages - TasteCraft";
$current_page = "messages.php";

ob_start();
?>
<style>
  :root {
    --primary-dark: #000000ff;
    --danger-dark: #bd1907ff;
    --danger: red;
    --primary: #000000ff;
    --secondary: #f59e0b;
    --secondary-light: #fcd34d;
    --accent: #b45309;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --card-bg: rgba(0, 0, 0, 0.8);
    --hover-bg: rgba(245, 158, 11, 0.1);
    --border-soft: rgba(0, 0, 0, 0.25);
  }

  .page-head { color: var(--text-primary); }
  .subtext { color: var(--text-secondary); }

  .card {
    background: var(--card-bg);
    border: 1px solid var(--border-soft);
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(2, 6, 23, 0.35);
    backdrop-filter: blur(6px);
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .card:hover { transform: translateY(-1px); box-shadow: 0 12px 28px rgba(2,6,23,.45); }

  .btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: .5rem; padding: .3rem .8rem; border-radius: 10px;
    font-weight: 800; border: 1px solid transparent; cursor: pointer;
    transition: all .15s ease;
  }
  .btn-primary { background: var(--secondary); color: #111827; }
  .btn-primary:hover { background: var(--secondary-light); }
  .btn-outline { background: transparent; color: var(--text-secondary); border-color: rgba(148,163,184,.25); }
  .btn-outline:hover { background: rgba(148,163,184,.08); color: var(--text-primary); }
  .btn-danger { background: var(--danger); color: white; }
  .btn-danger:hover { background: var(--danger-dark); }
  .btn-muted {
    background: rgba(2, 6, 23, 0.35); color: var(--text-secondary); border: 1px solid var(--border-soft);
  }
  .btn-muted[disabled] { opacity: .6; cursor: not-allowed; }

  .badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .55rem; border-radius: 9999px; font-size: .72rem; font-weight: 800;
    border: 1px solid rgba(148,163,184,.35);
  }
  .badge-unread { background: rgba(245, 158, 11, .15); color: var(--secondary-light); border-color: rgba(245, 158, 11, .45); }
  .badge-read { background: rgba(148,163,184,.12); color: var(--text-secondary); }

  .search-box {
    display: flex; align-items: center; gap: .5rem;
    background: rgba(2, 6, 23, 0.35);
    border: 1px solid rgba(148,163,184,.2);
    padding: .6rem .75rem; border-radius: 10px; width: 100%; max-width: 360px;
    color: var(--text-primary);
  }
  .search-box input {
    background: transparent; border: none; outline: none; color: var(--text-primary); width: 100%;
  }
  .search-box input::placeholder { color: var(--text-secondary); }

  .select {
    background: rgba(2, 6, 23, 0.35);
    color: var(--text-primary);
    border: 1px solid rgba(148,163,184,.25);
    border-radius: 10px;
    padding: .55rem .75rem;
  }

  .table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: separate; border-spacing: 0; color: var(--text-primary); }
  thead th {
    background: rgba(15, 23, 42, 0.75);
    color: var(--text-secondary);
    font-weight: 800; text-align: left; padding: .9rem 1rem;
    border-bottom: 1px solid rgba(148,163,184,.2);
    white-space: nowrap; position: sticky; top: 0; backdrop-filter: blur(4px);
  }
  tbody td {
    padding: 1rem; border-bottom: 1px solid rgba(148,163,184,.12);
    vertical-align: top;
  }
  tbody tr:hover { background: var(--hover-bg); }
  .snippet { color: var(--text-secondary); max-width: 480px; }
  .email { color: var(--secondary-light); }

  .alert {
    padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem;
    display: flex; align-items: center; gap: 0.5rem; font-weight: 600;
  }
  .alert-success { background: rgba(16,185,129,.12); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
  .alert-danger { background: rgba(239,68,68,.12); color: #f87171; border: 1px solid rgba(239,68,68,.35); }

  /* Modal */
  .modal { display: none; position: fixed; inset: 0; z-index: 1000; align-items: center; justify-content: center; }
  .modal.show { display: flex; }
  .modal-backdrop { position:absolute; inset:0; background: rgba(2,6,23,.65); }
  .modal-content {
    position: relative; background: var(--card-bg); color: var(--text-primary);
    border: 1px solid rgba(148,163,184,.25); border-radius: 14px;
    width: 95%; max-width: 720px; padding: 1.25rem; box-shadow: 0 18px 40px rgba(2, 6, 23, 0.55);
  }
  .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
  .modal-title { font-weight: 900; color: var(--secondary-light); display: flex; align-items: center; gap: .5rem; }
  .icon-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 2rem; border-radius: 10px; border: 1px solid rgba(148,163,184,.25);
    color: var(--text-secondary); background: rgba(2, 6, 23, 0.35);
  }
  .icon-btn:hover { background: rgba(148,163,184,.12); }

  .prewrap { white-space: pre-wrap; }
</style>

<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl md:text-3xl font-extrabold page-head mb-1">User Messages</h1>
    <p class="subtext">Manage incoming messages from your contact form.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="badge badge-unread"><i class="fas fa-inbox"></i> Unread: <?= (int)$unreadCount ?></span>
  </div>
</div>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
  <div class="search-box">
    <i class="fas fa-search" style="color: var(--text-secondary);"></i>
    <input id="searchMessages" type="text" placeholder="Search by name, email, or subject...">
  </div>
  <div class="flex items-center gap-2">
    <label for="statusFilter" class="subtext text-sm">Status:</label>
    <select id="statusFilter" class="select">
      <option value="all">All</option>
      <option value="unread">Unread</option>
      <option value="read">Read</option>
    </select>
  </div>
</div>

<?php if (isset($_GET['updated'])): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> Message marked as read.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> Message deleted successfully.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card overflow-hidden">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th class="p-3">ID</th>
          <th class="p-3">From</th>
          <th class="p-3">Subject</th>
          <th class="p-3">Message</th>
          <th class="p-3">Status</th>
          <th class="p-3">Date</th>
          <th class="p-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody id="messagesTable">
        <?php if (empty($messages)): ?>
          <tr>
            <td colspan="7" class="p-8 text-center" style="color: var(--text-secondary);">
              <i class="fas fa-inbox text-3xl mb-2 opacity-60"></i><br>
              No messages yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($messages as $msg): ?>
            <?php
              $isUnread = strtolower($msg['status'] ?? '') !== 'read';
              $snippet = mb_substr($msg['message'] ?? '', 0, 120) . (mb_strlen($msg['message'] ?? '') > 120 ? '…' : '');
            ?>
            <tr class="hover:bg-gray-700"
                data-name="<?= htmlspecialchars(mb_strtolower($msg['name'] ?? ''), ENT_QUOTES) ?>"
                data-email="<?= htmlspecialchars(mb_strtolower($msg['email'] ?? ''), ENT_QUOTES) ?>"
                data-subject="<?= htmlspecialchars(mb_strtolower($msg['subject'] ?? ''), ENT_QUOTES) ?>"
                data-status="<?= htmlspecialchars(mb_strtolower($msg['status'] ?? ''), ENT_QUOTES) ?>">
              <td class="p-3"><?= (int)$msg['id'] ?></td>
              <td class="p-3">
                <div class="font-semibold"><?= htmlspecialchars($msg['name']) ?></div>
                <div class="email text-sm"><?= htmlspecialchars($msg['email']) ?></div>
              </td>
              <td class="p-3"><?= htmlspecialchars($msg['subject']) ?></td>
              <td class="p-3 snippet"><?= htmlspecialchars($snippet) ?></td>
              <td class="p-3">
                <span class="badge <?= $isUnread ? 'badge-unread' : 'badge-read' ?>">
                  <i class="fas fa-envelope<?= $isUnread ? '' : '-open' ?>"></i>
                  <?= $isUnread ? 'Unread' : 'Read' ?>
                </span>
              </td>
              <td class="p-3" style="color: var(--text-secondary);"><?= htmlspecialchars($msg['created_at']) ?></td>
              <td class="p-3">
                <div class="flex justify-end gap-2">
                  <button
                    class="btn btn-outline text-sm"
                    data-id="<?= (int)$msg['id'] ?>"
                    data-name="<?= htmlspecialchars($msg['name'], ENT_QUOTES) ?>"
                    data-email="<?= htmlspecialchars($msg['email'], ENT_QUOTES) ?>"
                    data-subject="<?= htmlspecialchars($msg['subject'], ENT_QUOTES) ?>"
                    data-message="<?= htmlspecialchars($msg['message'], ENT_QUOTES) ?>"
                    data-date="<?= htmlspecialchars($msg['created_at'], ENT_QUOTES) ?>"
                    data-status="<?= htmlspecialchars($msg['status'], ENT_QUOTES) ?>"
                    onclick="openViewModal(this)">
                    <i class="fas fa-eye"></i> View
                  </button>

                  <button
                    class="btn btn-primary text-sm flex"
                    <?= $isUnread ? '' : 'disabled' ?>
                    data-id="<?= (int)$msg['id'] ?>"
                    onclick="submitAction('mark_read', this.getAttribute('data-id'))">
                    <i class="fas fa-envelope-open"></i> Mark Read
                  </button>

                  <button
                    class="btn btn-danger text-sm"
                    data-id="<?= (int)$msg['id'] ?>"
                    data-subject="<?= htmlspecialchars($msg['subject'], ENT_QUOTES) ?>"
                    onclick="openDeleteModal(this)">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Hidden unified action form -->
<form id="actionForm" method="POST" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <input type="hidden" name="action" id="actionInput">
  <input type="hidden" name="message_id" id="messageIdInput">
</form>

<!-- View Message Modal -->
<div id="viewModal" class="modal" aria-hidden="true">
  <div class="modal-backdrop" onclick="closeModal('viewModal')"></div>
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-envelope-open-text"></i> Message Details</div>
      <button class="icon-btn" onclick="closeModal('viewModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
      <div>
        <div class="subtext text-xs">From</div>
        <div id="vm_from" class="font-semibold"></div>
        <div id="vm_email" class="email"></div>
      </div>
      <div>
        <div class="subtext text-xs">Date</div>
        <div id="vm_date" class="font-semibold"></div>
      </div>
      <div class="md:col-span-2">
        <div class="subtext text-xs">Subject</div>
        <div id="vm_subject" class="font-semibold"></div>
      </div>
      <div class="md:col-span-2">
        <div class="subtext text-xs">Message</div>
        <div id="vm_message" class="prewrap"></div>
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <!-- <a id="vm_reply" href="#" class="btn btn-outline" target="_blank" rel="noopener">
        <i class="fas fa-reply"></i> Reply
      </a> -->
      <button id="vm_markread" class="btn btn-primary" onclick="markReadFromModal()" type="button">
        <i class="fas fa-envelope-open"></i> Mark Read
      </button>
      <button class="btn btn-danger" onclick="openDeleteFromModal()" type="button">
        <i class="fas fa-trash"></i> Delete
      </button>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" aria-hidden="true">
  <div class="modal-backdrop" onclick="closeModal('deleteModal')"></div>
  <div class="modal-content" style="max-width:520px;">
    <div class="modal-header">
      <div class="modal-title" style="color:#fca5a5;"><i class="fas fa-trash"></i> Delete Message</div>
      <button class="icon-btn" onclick="closeModal('deleteModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <p class="subtext mb-2">Are you sure you want to delete this message?</p>
    <p id="del_subject" class="font-bold mb-4" style="color: var(--text-primary);"></p>
    <div class="flex justify-end gap-2">
      <button class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
      <button class="btn btn-danger" onclick="confirmDelete()"><i class="fas fa-trash"></i> Delete</button>
    </div>
  </div>
</div>

<script>
  // Auto-hide alerts
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => {
      el.style.opacity = '0';
      el.style.transition = 'opacity .35s ease';
      setTimeout(() => el.remove(), 350);
    });
  }, 4000);

  // Modal helpers
  function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('show');
  }
  function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('show');
  }
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
    }
  });

  // Search + filter
  const searchInput = document.getElementById('searchMessages');
  const statusFilter = document.getElementById('statusFilter');
  const tbody = document.getElementById('messagesTable');

  function filterRows() {
    const q = (searchInput.value || '').trim().toLowerCase();
    const stat = statusFilter.value;
    let visible = 0;

    tbody.querySelectorAll('tr').forEach(tr => {
      const name = tr.getAttribute('data-name') || '';
      const email = tr.getAttribute('data-email') || '';
      const subject = tr.getAttribute('data-subject') || '';
      const status = tr.getAttribute('data-status') || '';
      const qMatch = !q || name.includes(q) || email.includes(q) || subject.includes(q);
      const sMatch = (stat === 'all') || (status === stat);
      const show = qMatch && sMatch;
      tr.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    if (visible === 0) {
      if (!document.getElementById('noResultsRow')) {
        const row = document.createElement('tr');
        row.id = 'noResultsRow';
        row.innerHTML = '<td colspan="7" class="p-6 text-center" style="color: var(--text-secondary);">No matching results.</td>';
        tbody.appendChild(row);
      }
    } else {
      const nr = document.getElementById('noResultsRow');
      if (nr) nr.remove();
    }
  }

  if (searchInput) searchInput.addEventListener('input', filterRows);
  if (statusFilter) statusFilter.addEventListener('change', filterRows);

  // Action form submit
  function submitAction(action, id) {
    const form = document.getElementById('actionForm');
    document.getElementById('actionInput').value = action;
    document.getElementById('messageIdInput').value = id;
    form.submit();
  }

  // View modal populate
  let currentViewId = null;
  function openViewModal(btn) {
    const id = btn.getAttribute('data-id');
    currentViewId = id;

    const name = btn.getAttribute('data-name') || '';
    const email = btn.getAttribute('data-email') || '';
    const subject = btn.getAttribute('data-subject') || '';
    const message = btn.getAttribute('data-message') || '';
    const date = btn.getAttribute('data-date') || '';
    const status = (btn.getAttribute('data-status') || '').toLowerCase();

    document.getElementById('vm_from').textContent = name;
    document.getElementById('vm_email').textContent = email;
    document.getElementById('vm_subject').textContent = subject;
    document.getElementById('vm_message').textContent = message;
    document.getElementById('vm_date').textContent = date;

    // const reply = document.getElementById('vm_reply');
    // reply.href = `mailto:${encodeURIComponent(email)}?subject=${encodeURIComponent('Re: ' + subject)}`;

    const mr = document.getElementById('vm_markread');
    if (status === 'read') {
      mr.setAttribute('disabled', 'disabled');
      mr.classList.add('btn-muted');
      mr.classList.remove('btn-primary');
    } else {
      mr.removeAttribute('disabled');
      mr.classList.remove('btn-muted');
      mr.classList.add('btn-primary');
    }

    openModal('viewModal');
  }

  function markReadFromModal() {
    if (currentViewId) {
      submitAction('mark_read', currentViewId);
    }
  }

  // Delete flow
  let deleteId = null;
  function openDeleteModal(btn) {
    deleteId = btn.getAttribute('data-id');
    const subj = btn.getAttribute('data-subject') || '';
    document.getElementById('del_subject').textContent = subj;
    openModal('deleteModal');
  }
  function openDeleteFromModal() {
    deleteId = currentViewId;
    document.getElementById('del_subject').textContent = document.getElementById('vm_subject').textContent || '';
    closeModal('viewModal');
    openModal('deleteModal');
  }
  function confirmDelete() {
    if (deleteId) {
      submitAction('delete', deleteId);
    }
  }
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>

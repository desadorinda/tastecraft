<?php
// Start session for CSRF/auth
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Create PDO connection using Database class
require_once __DIR__ . '/../includes/database.php';
$db = new Database();
$pdo = $db->getConnection();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error = null;
$currentUserId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);

// Handle POST actions securely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Invalid user ID.');
        }

        // Load target user
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id=?");
        $stmt->execute([$id]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) {
            throw new RuntimeException('User not found.');
        }

        if ($action === 'promote') {
            // Promote to admin
            $pdo->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([$id]);
            header("Location: users.php?updated=1");
            exit;
        }

        if ($action === 'demote') {
            // Prevent demoting yourself
            if ($id === (int)$currentUserId) {
                throw new RuntimeException('You cannot change your own role.');
            }
            // Avoid removing the last admin
            if ($target['role'] === 'admin') {
                $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role='admin'");
                $countAdmins = (int)$stmt->fetchColumn();
                if ($countAdmins <= 1) {
                    throw new RuntimeException('Cannot demote the last admin.');
                }
            }
            $pdo->prepare("UPDATE users SET role='user' WHERE id=?")->execute([$id]);
            header("Location: users.php?updated=1");
            exit;
        }

        if ($action === 'delete') {
            // Prevent deleting yourself
            if ($id === (int)$currentUserId) {
                throw new RuntimeException('You cannot delete your own account.');
            }
            // Do not delete last admin
            if ($target['role'] === 'admin') {
                $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role='admin'");
                $countAdmins = (int)$stmt->fetchColumn();
                if ($countAdmins <= 1) {
                    throw new RuntimeException('Cannot delete the last admin account.');
                }
            }

            // Clean up user's recipes (ingredients, instructions, images)
            $stmt = $pdo->prepare("SELECT id, image FROM recipes WHERE user_id=?");
            $stmt->execute([$id]);
            $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($recipes as $r) {
                $rid = (int)$r['id'];

                $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id=?")->execute([$rid]);
                $pdo->prepare("DELETE FROM recipe_instructions WHERE recipe_id=?")->execute([$rid]);

                if (!empty($r['image']) && file_exists(__DIR__ . '/../' . $r['image'])) {
                    @unlink(__DIR__ . '/../' . $r['image']);
                }

                $pdo->prepare("DELETE FROM recipes WHERE id=?")->execute([$rid]);
            }

            // Delete the user
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);

            header("Location: users.php?deleted=1");
            exit;
        }

        throw new RuntimeException('Unknown action.');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Fetch all users with recipe counts
$users = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM recipes r WHERE r.user_id = u.id) AS recipes_count
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

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
  }

  .page-wrap {
    /* background: radial-gradient(1200px 600px at top left, rgba(245, 158, 11, 0.07), transparent),
                linear-gradient(180deg, var(--primary-dark), var(--primary)); */
    min-height: 100%;
    color: var(--text-primary);
  }

  .card {
    background: var(--card-bg);
    border: 1px solid rgba(148, 163, 184, 0.15);
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(2, 6, 23, 0.35);
    backdrop-filter: blur(6px);
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .card:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(2, 6, 23, 0.45);
  }

  .btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: .5rem; padding: .6rem 1rem; border-radius: 10px;
    font-weight: 700; border: 1px solid transparent; cursor: pointer;
    transition: all .15s ease;
  }
  .btn-primary { background: var(--secondary); color: #111827; }
  .btn-primary:hover { background: var(--secondary-light); }
  .btn-outline { background: transparent; color: var(--text-secondary); border-color: rgba(148,163,184,.25); }
  .btn-outline:hover { background: rgba(148,163,184,.08); }
  .btn-danger { background: var(--danger); color: white; }
  .btn-danger:hover { background: var(--danger-dark); }

  .badge {
    display: inline-block; padding: .2rem .55rem; border-radius: 9999px;
    font-size: .75rem; font-weight: 800;
    border: 1px solid rgba(148,163,184,.35);
  }
  .badge-admin {
    background: rgba(239, 68, 68, 0.15); color: #fecaca; border-color: rgba(239,68,68,.45);
  }
  .badge-user {
    background: rgba(245, 158, 11, 0.15); color: var(--secondary-light); border-color: rgba(245, 158, 11, 0.45);
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
  }
  tbody tr:hover { background: var(--hover-bg); }

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

  .modal {
    display: none; position: fixed; inset: 0;
    background: rgba(2, 6, 23, 0.65); z-index: 1000;
    align-items: center; justify-content: center; padding: 1rem;
  }
  .modal.show { display: flex; }
  .modal-content {
    background: var(--card-bg);
    border: 1px solid rgba(148,163,184,.2);
    border-radius: 14px; width: 95%; max-width: 560px;
    padding: 1.25rem; box-shadow: 0 18px 40px rgba(2, 6, 23, 0.55);
    color: var(--text-primary);
  }
  .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .75rem; }
  .modal-title { font-weight: 900; color: var(--secondary-light); display: flex; align-items: center; gap: .5rem; }
  .icon-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 2rem; border-radius: 10px; border: 1px solid rgba(148,163,184,.25);
    color: var(--text-secondary); background: rgba(2, 6, 23, 0.35);
  }
  .icon-btn:hover { background: rgba(148,163,184,.12); }

  .alert {
    padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem;
    display: flex; align-items: center; gap: 0.5rem; font-weight: 600;
  }
  .alert-success { background: rgba(16,185,129,.12); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
  .alert-danger { background: rgba(239,68,68,.12); color: #f87171; border: 1px solid rgba(239,68,68,.35); }

  @media (max-width: 640px) {
    .mobile-hidden { display: none; }
  }
</style>

<div class="page-wrap">
  <div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-3xl font-extrabold" style="color: var(--text-primary);">Manage Users</h1>
        <p class="text-sm" style="color: var(--text-secondary);">Promote, demote, or remove users securely.</p>
      </div>
      <div class="flex items-center gap-2">
        <a href="../index.php" class="btn btn-outline"><i class="fas fa-home"></i> Back to Site</a>
      </div>
    </div>

    <div class="flex items-center justify-between mb-4">
      <div class="search-box">
        <i class="fas fa-search" style="color: var(--text-secondary);"></i>
        <input id="tableSearch" type="text" placeholder="Search by name, email, or role...">
      </div>

      <div class="flex flex-col gap-2 items-end">
        <?php if (isset($_GET['updated'])): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> User role updated successfully!
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> User deleted successfully!
          </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger">
            <i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card overflow-hidden">
      <div class="px-6 py-4 border-b" style="border-color: rgba(148,163,184,.15);">
        <h2 class="text-xl font-bold" style="color: var(--secondary-light);">
          <i class="fas fa-users mr-2"></i> All Users
        </h2>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="p-3">Name</th>
              <th class="p-3">Email</th>
              <th class="p-3">Role</th>
              <th class="p-3 mobile-hidden">Recipes</th>
              <th class="p-3">Joined</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody id="usersTableBody">
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="6" class="p-8 text-center" style="color: var(--text-secondary);">
                  <i class="fas fa-inbox text-3xl mb-2 opacity-60"></i><br>
                  No users found.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-700"
                    data-name="<?= htmlspecialchars(mb_strtolower($u['name'] ?? ''), ENT_QUOTES) ?>"
                    data-email="<?= htmlspecialchars(mb_strtolower($u['email'] ?? ''), ENT_QUOTES) ?>"
                    data-role="<?= htmlspecialchars(mb_strtolower($u['role'] ?? ''), ENT_QUOTES) ?>">
                  <td class="p-3 font-semibold" style="color: var(--text-primary);"><?= htmlspecialchars($u['name']) ?></td>
                  <td class="p-3" style="color: var(--text-secondary);"><?= htmlspecialchars($u['email']) ?></td>
                  <td class="p-3">
                    <span class="badge <?= $u['role']==='admin' ? 'badge-admin' : 'badge-user' ?>">
                      <?= htmlspecialchars(ucfirst($u['role'])) ?>
                    </span>
                  </td>
                  <td class="p-3 mobile-hidden" style="color: var(--text-secondary);">
                    <?= (int)($u['recipes_count'] ?? 0) ?>
                  </td>
                  <td class="p-3" style="color: var(--text-secondary);">
                    <?= htmlspecialchars($u['created_at']) ?>
                  </td>
                  <td class="p-3">
                    <div class="flex justify-end gap-2">
                      <?php if ($u['role'] === 'user'): ?>
                        <button class="btn btn-primary text-sm"
                                data-user-id="<?= (int)$u['id'] ?>"
                                data-user-name="<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>"
                                data-target-role="admin"
                                onclick="openRoleModal(this)">
                          <i class="fas fa-arrow-up"></i> Make Admin
                        </button>
                      <?php else: ?>
                        <button class="btn btn-outline text-sm"
                                data-user-id="<?= (int)$u['id'] ?>"
                                data-user-name="<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>"
                                data-target-role="user"
                                onclick="openRoleModal(this)">
                          <i class="fas fa-arrow-down"></i> Make User
                        </button>
                      <?php endif; ?>
                      <button class="btn btn-danger text-sm"
                              data-user-id="<?= (int)$u['id'] ?>"
                              data-user-name="<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>"
                              data-user-role="<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>"
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
  </div>
</div>

<!-- Change Role Modal -->
<div id="roleModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-user-shield"></i> Change Role</div>
      <button class="icon-btn" onclick="closeModal('roleModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <p id="role_modal_text" class="mb-4" style="color: var(--text-secondary);"></p>
    <form id="roleForm" method="POST" class="flex justify-end gap-2">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" id="role_action">
      <input type="hidden" name="user_id" id="role_user_id">
      <button type="button" class="btn btn-outline" onclick="closeModal('roleModal')">Cancel</button>
      <button type="submit" class="btn btn-primary" id="role_submit_btn">
        <i class="fas fa-check"></i> Confirm
      </button>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteUserModal" class="modal" aria-hidden="true">
  <div class="modal-content" style="max-width: 560px;">
    <div class="modal-header">
      <div class="modal-title" style="color: #fca5a5;"><i class="fas fa-user-times"></i> Delete User</div>
      <button class="icon-btn" onclick="closeModal('deleteUserModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <p class="mb-2" style="color: var(--text-secondary);">
      Are you sure you want to delete this user? This will permanently remove the user and all their recipes.
    </p>
    <p class="font-bold mb-4" id="delete_user_title" style="color: var(--text-primary);"></p>
    <form id="deleteForm" method="POST" class="flex justify-end gap-2">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="user_id" id="delete_user_id">
      <button type="button" class="btn btn-outline" onclick="closeModal('deleteUserModal')">Cancel</button>
      <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
    </form>
  </div>
</div>

<script>
  // Search filter
  const searchInput = document.getElementById('tableSearch');
  const tbody = document.getElementById('usersTableBody');
  function filterRows() {
    const q = (searchInput.value || '').trim().toLowerCase();
    let visible = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
      const name = tr.getAttribute('data-name') || '';
      const email = tr.getAttribute('data-email') || '';
      const role = tr.getAttribute('data-role') || '';
      const match = !q || name.includes(q) || email.includes(q) || role.includes(q);
      tr.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    if (visible === 0) {
      if (!document.getElementById('noResultsRow')) {
        const row = document.createElement('tr');
        row.id = 'noResultsRow';
        row.innerHTML = '<td colspan="6" class="p-6 text-center" style="color: var(--text-secondary);">No matching results.</td>';
        tbody.appendChild(row);
      }
    } else {
      const nr = document.getElementById('noResultsRow');
      if (nr) nr.remove();
    }
  }
  if (searchInput) {
    searchInput.addEventListener('input', filterRows);
  }

  // Alerts auto-hide
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity .4s ease';
      setTimeout(() => alert.remove(), 400);
    });
  }, 4000);

  // Modal helpers
  function openModal(id) {
    const m = document.getElementById(id);
    if (m) {
      m.classList.add('show');
      m.setAttribute('aria-hidden', 'false');
    }
  }
  function closeModal(id) {
    const m = document.getElementById(id);
    if (m) {
      m.classList.remove('show');
      m.setAttribute('aria-hidden', 'true');
    }
  }
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
    }
  });
  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => {
      if (e.target === m) closeModal(m.id);
    });
  });

  // Role modal
  function openRoleModal(btn) {
    const id = btn.getAttribute('data-user-id');
    const name = btn.getAttribute('data-user-name') || 'this user';
    const targetRole = btn.getAttribute('data-target-role'); // 'admin' or 'user'

    const action = (targetRole === 'admin') ? 'promote' : 'demote';
    document.getElementById('role_action').value = action;
    document.getElementById('role_user_id').value = id;
    document.getElementById('role_modal_text').textContent =
      `Are you sure you want to set ${name}'s role to ${targetRole}?`;
    document.getElementById('role_submit_btn').innerHTML =
      `<i class="fas fa-check"></i> Confirm ${targetRole === 'admin' ? 'Promotion' : 'Demotion'}`;

    openModal('roleModal');
  }

  // Delete modal
  function openDeleteModal(btn) {
    const id = btn.getAttribute('data-user-id');
    const name = btn.getAttribute('data-user-name') || '';
    document.getElementById('delete_user_id').value = id;
    document.getElementById('delete_user_title').textContent = name;
    openModal('deleteUserModal');
  }
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>

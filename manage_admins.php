<?php if ($_SESSION['admin']['role'] === 'super'): ?>
<h2>Admin Management (Super Admin Only)</h2>

<table>
<tr>
    <th>Email</th>
    <th>Role</th>
    <th>Created</th>
    <th>Actions</th>
</tr>

<?php foreach ($admins as $adm): ?>
<tr>
    <td><?= htmlspecialchars($adm['email']) ?></td>

    <td>
        <?php if ($adm['role'] === 'super'): ?>
            <span class="badge super">Super Admin</span>
        <?php else: ?>
            <span class="badge approved">Admin</span>
        <?php endif; ?>
    </td>

    <td><?= htmlspecialchars($adm['created_at'] ?? '-') ?></td>

    <td>
        <?php if ($adm['role'] !== 'super'): ?>

            <!-- Reset Password -->
            <form method="post" action="reset_admin_password.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= intval($adm['id']) ?>">
                <button class="manage-btn">Reset Password</button>
            </form>

            <!-- Delete Admin -->
            <form method="post" action="delete_admin.php" style="display:inline;"
                  onsubmit="return confirm('Delete admin: <?= htmlspecialchars($adm['email']) ?>?');">
                <input type="hidden" name="id" value="<?= intval($adm['id']) ?>">
                <button class="delete-btn manage-btn">Delete</button>
            </form>

        <?php else: ?>
            <em>Protected</em>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<hr>

<h3>Create New Admin</h3>
<form method="post" action="create_admin.php">
    <input type="email" name="email" placeholder="Admin Email" required>
    <input type="password" name="password" placeholder="Temporary Password" required>
    <button class="approve-btn">Create Admin</button>
</form>

<?php endif; ?>

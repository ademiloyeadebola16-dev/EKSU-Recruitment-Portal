<?php if ($is_super): ?>
<h2>Admin Management (Super-Admin Only)</h2>

<table>
<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Status</th>
    <th>Role</th>
    <th>Actions</th>
</tr>

<?php foreach ($admins as $i => $adm): ?>
<tr>
    <td><?= htmlspecialchars($adm['name']) ?></td>
    <td><?= htmlspecialchars($adm['email']) ?></td>

    <td>
        <?php if ($adm['approved']): ?>
            <span class="badge approved">Approved</span>
        <?php else: ?>
            <span class="badge pending">Pending</span>
        <?php endif; ?>
    </td>

    <td>
        <?php if ($adm['is_super']): ?>
            <span class="badge super">Super Admin</span>
        <?php else: ?>
            Admin
        <?php endif; ?>
    </td>

    <td>
        <?php if (!$adm['approved']): ?>
            <!-- Approve -->
            <a href="approve_admin.php?id=<?= intval($adm['id']) ?>" onclick="return confirm('Approve admin: <?= htmlspecialchars($adm['email']) ?>?');">
                <button class="approve-btn manage-btn">Approve</button>
            </a>

            <!-- Reject -->
            <form action="reject_admin.php" method="POST" style="display:inline;" onsubmit="return confirm('Reject and remove admin: <?= htmlspecialchars($adm['email']) ?>?');">
                <input type="hidden" name="id" value="<?= intval($adm['id']) ?>">
                <button type="submit" class="delete-btn manage-btn">Reject</button>
            </form>
        <?php endif; ?>

        <?php if (!$adm['is_super']): ?>
            <!-- Promote -->
            <a href="promote_admin.php?id=<?= intval($adm['id']) ?>" onclick="return confirm('Promote admin: <?= htmlspecialchars($adm['email']) ?> to Super Admin?');">
                <button class="promote-btn manage-btn">Promote</button>
            </a>
        <?php else: ?>
            <!-- Optional: Demote -->
            <a href="demote_admin.php?id=<?= intval($adm['id']) ?>" onclick="return confirm('Demote Super Admin: <?= htmlspecialchars($adm['email']) ?> to regular admin?');">
                <button class="promote-btn manage-btn" style="background:#ff6600;">Demote</button>
            </a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

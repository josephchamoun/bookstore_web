<div class="sidebar">
    <div class="sidebar-logo">📚 <span>Book</span>Store</div>
    <nav class="nav">
        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="books.php" class="<?= basename($_SERVER['PHP_SELF']) === 'books.php' ? 'active' : '' ?>">
            <span class="icon">📚</span> Books
        </a>
        <a href="categories.php" class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
            <span class="icon">🏷️</span> Categories
        </a>
        <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
            <span class="icon">📦</span> Orders
        </a>
        <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
            <span class="icon">👥</span> Users
        </a>
    </nav>
    <div class="logout">
        <a href="#" onclick="logout()">Logout</a>
    </div>
    <script>
function logout() {
    localStorage.removeItem('admin_token');
    window.location.href = '/bookstore_api/admin/login.php';
}
</script>
</div>
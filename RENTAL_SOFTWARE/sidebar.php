<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'];
?>
<div class="sidebar">
    <div class="logo">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div class="logo-text">
            <span class="panga">Panga</span><span class="rms">Rms</span>
        </div>
    </div>

    <div class="nav-section">
        <div class="nav-title">MENU</div>
        <a href="<?php echo ($role === 'caretaker') ? 'caretaker_dashboard.php' : 'dashboard.php'; ?>" class="nav-item <?php echo in_array($current_page, ['dashboard.php', 'caretaker_dashboard.php']) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
            <span>Dashboard</span>
        </a>
        <a href="notifications.php" class="nav-item <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span>Notifications</span>
        </a>
        <?php if ($role === 'caretaker'): ?>
        <a href="tenants.php" class="nav-item <?php echo ($current_page == 'tenants.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="17" y1="11" x2="23" y2="11"/>
            </svg>
            <span>Tenants</span>
        </a>
        <a href="caretaker_announcements.php" class="nav-item <?php echo ($current_page == 'caretaker_announcements.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                <line x1="4" y1="22" x2="4" y2="15"></line>
            </svg>
            <span>Announcements</span>
        </a>
        <a href="register_tenant.php" class="nav-item <?php echo ($current_page == 'register_tenant.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="17" y1="11" x2="23" y2="11"/>
            </svg>
            <span>Register Tenant</span>
        </a>
        <?php elseif ($role === 'tenant'): ?>
        <a href="rent.php" class="nav-item <?php echo ($current_page == 'rent.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
            <span>Pay Rent</span>
        </a>
        <?php endif; ?>
        <a href="issues.php" class="nav-item <?php echo in_array($current_page, ['issues.php', 'view_issue.php']) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span>Issues</span>
        </a>
    </div>

    <div class="nav-separator"></div>

    <div class="nav-section">
        <div class="nav-title">GENERAL</div>
        <a href="<?php echo ($role === 'tenant') ? 'tenant_settings.php' : (($role === 'caretaker') ? 'caretaker_settings.php' : 'settings.php'); ?>" class="nav-item <?php echo in_array($current_page, ['settings.php', 'tenant_settings.php', 'caretaker_settings.php']) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            <span>Settings</span>
        </a>
        <a href="help.php" class="nav-item <?php echo ($current_page == 'help.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-help-circle">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>Help</span>
        </a>
        <a href="logout.php" class="nav-item">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-out">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Logout</span>
        </a>
    </div>
</div>

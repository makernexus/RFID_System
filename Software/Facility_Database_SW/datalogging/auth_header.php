<!-- Authentication Header - Include this in protected pages -->
<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
// Use $AUTH_BASE_PATH if set, otherwise default to empty string (for root-level pages)
if (!isset($AUTH_BASE_PATH)) {
    $AUTH_BASE_PATH = '';
}
?>
<div style="background-color: #f0f0f0; padding: 10px; margin-bottom: 20px; border-bottom: 2px solid #ccc; overflow: hidden;">
    <div style="float: left;">
        <span style="color: #666;">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
    </div>
    <div style="float: right;">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="<?php echo $AUTH_BASE_PATH; ?>admin_dashboard.php" style="background-color: #4CAF50; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-right: 10px;<?php if($current_page === 'admin_dashboard.php') echo ' opacity: 0.6; cursor: not-allowed; pointer-events: none;'; ?>">Admin Dashboard</a>
            <a href="<?php echo $AUTH_BASE_PATH; ?>rfidhome.php" style="background-color: #667eea; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-right: 10px;<?php if($current_page === 'rfidhome.php') echo ' opacity: 0.6; cursor: not-allowed; pointer-events: none;'; ?>">RFID Reports</a>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
            <a href="<?php echo $AUTH_BASE_PATH; ?>rfidhome.php" style="background-color: #667eea; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-right: 10px;<?php if($current_page === 'rfidhome.php') echo ' opacity: 0.6; cursor: not-allowed; pointer-events: none;'; ?>">RFID Reports</a>
        <?php else: ?>
            <a href="<?php echo $AUTH_BASE_PATH; ?>rfidhome.php" style="background-color: #008CBA; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-right: 10px;<?php if($current_page === 'rfidhome.php') echo ' opacity: 0.6; cursor: not-allowed; pointer-events: none;'; ?>">Home</a>
        <?php endif; ?>
        <a href="<?php echo $AUTH_BASE_PATH; ?>logout.php" style="background-color: #f44336; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold;">Logout</a>
    </div>
    <div style="clear: both;"></div>
</div>

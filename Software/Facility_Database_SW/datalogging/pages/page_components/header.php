<div class="mn-header">
    <div class="mn-logo">
        <img alt='Maker Nexus Logo' src='../../static/images/mn_logo.png' />
    </div>
    <div class="mn-header-text"><?php print $header_title; ?></div>
    <?php if($page == 'member_dashboard') : ?>
        <div class="mn-header-legend">
            <span>Badged: </span>
            <span class="mn-header-legend-item member">Member</span>
            <span class="mn-header-legend-item staff">Staff</span>
        </div>
    <?php elseif($page == 'machine_calendar') : ?>
        <div class="mn-header-legend">
            <span>Event Types: </span>
            <span class="mn-header-legend-item booking">Reservation</span>
            <span class="mn-header-legend-item class">Class</span>
            <span class="mn-header-legend-item admin">Maintenance</span>
        </div>
    <?php endif; ?>
</div>
